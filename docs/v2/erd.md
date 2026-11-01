# SBBlog v2 — Console ERD

This is the **write-side** schema: the console database (PostgreSQL). The read-side store used by
frontends (SQLite / SQLCipher / D1 / R2) is defined by the event schema, not by this ERD.

## Design rules

1. **PostgreSQL only.** v1 already depends on `jsonb` and `timestamptz`.
2. **UUIDv7 primary keys** for every entity that leaves the console (articles, media, authors…).
   The same ID is used in events, the API and every frontend store. Exception: `outbox_events`
   uses a `bigint` identity.
3. **Every tenant table carries `blog_id`** (or `workspace_id`). Queries are always scoped and
   nothing references across blogs.
4. **Content is versioned.** `articles` holds identity and state, `article_revisions` holds the
   content and is immutable. Edits create revisions, and suggestions point to revisions.
5. **No change without an event.** Each publish-relevant write inserts into `outbox_events` in the
   **same transaction**.
6. **All times are `timestamptz`.** Soft deletes only where restore is a product feature
   (articles, categories, comments, blogs, users).
7. **No raw IPs at rest.** Visitors are identified by a daily-salted hash (LGPD/GDPR).

## Domains

```mermaid
flowchart LR
  ID[Identity & Security<br/>users · 2FA · devices · audit]
  TEN[Tenancy<br/>workspaces · blogs]
  ACL[Access control<br/>roles · permissions · members]
  CON[Content<br/>articles · revisions · suggestions<br/>categories · tags · media · authors]
  INT[Integrations<br/>analytics: blog / author / article]
  PUB[Publishing<br/>outbox · transports · frontends · snapshots]
  APP[Apps & API<br/>apps · installations · tokens]
  ENG[Engagement<br/>comments · page views · stats]
  THM[Themes<br/>catalog · licenses]

  ID --> TEN --> ACL
  TEN --> CON
  CON --> INT
  CON --> PUB
  CON --> ENG
  APP --> CON
  THM --> TEN
```

---

## 1. Identity & security

```mermaid
erDiagram
  users ||--o{ webauthn_credentials : registers
  users ||--o{ trusted_devices : trusts
  users ||--o{ audit_logs : "acts in"
  languages ||--o{ users : prefers

  users {
    uuid id PK
    string name
    string email UK
    string password
    uuid language_id FK
    text two_factor_secret "encrypted, Fortify"
    text two_factor_recovery_codes "encrypted, Fortify"
    timestamptz two_factor_confirmed_at
    timestamptz email_verified_at
    timestamptz last_login_at
    timestamptz deleted_at
  }
  webauthn_credentials {
    uuid id PK
    uuid user_id FK
    string name
    text credential_id UK
    text public_key
    bigint sign_count
    timestamptz last_used_at
  }
  trusted_devices {
    uuid id PK
    uuid user_id FK
    string token_hash UK
    string label
    timestamptz expires_at
  }
  audit_logs {
    uuid id PK
    uuid workspace_id FK
    uuid blog_id FK "nullable"
    uuid actor_user_id FK "nullable"
    uuid actor_app_installation_id FK "nullable"
    string action "e.g. integration.updated"
    string subject_type
    uuid subject_id
    jsonb metadata
    string ip_hash
    timestamptz created_at
  }
  languages {
    uuid id PK
    string code UK "pt-BR, en-US"
    string label
  }
```

- Phase 1 includes the Fortify 2FA columns, but 2FA stays **unused** until it ships.
- `webauthn_credentials` (passkeys) and `trusted_devices` are for a later phase. They are included
  here so authentication has a single home from the start.

## 2. Tenancy & access control

```mermaid
erDiagram
  workspaces ||--o{ workspace_members : has
  users ||--o{ workspace_members : joins
  workspaces ||--o{ blogs : owns
  workspaces ||--o{ roles : "defines custom"
  roles ||--o{ role_permissions : grants
  permissions ||--o{ role_permissions : "granted by"
  blogs ||--o{ blog_members : has
  users ||--o{ blog_members : "member of"
  roles ||--o{ blog_members : "assigned to"
  blogs ||--o{ blog_languages : supports
  languages ||--o{ blog_languages : ""

  workspaces {
    uuid id PK
    string name
    string slug UK
    uuid owner_user_id FK
    string two_factor_policy "off | optional | required_by_role | required_all"
    string plan "community | pro | ..."
    timestamptz deleted_at
  }
  workspace_members {
    uuid workspace_id PK,FK
    uuid user_id PK,FK
    string workspace_role "owner | admin | member"
    timestamptz joined_at
  }
  blogs {
    uuid id PK
    uuid workspace_id FK
    string name
    string slug "UK per workspace"
    string canonical_url
    string title
    string slogan
    string description
    uuid brand_media_id FK
    uuid default_language_id FK
    string comment_policy "disabled | moderated | open"
    boolean autoconvert_webp
    bigint event_sequence "last emitted seq"
    timestamptz deleted_at
  }
  blog_languages {
    uuid blog_id PK,FK
    uuid language_id PK,FK
  }
  permissions {
    string key PK "articles.publish, integrations.manage..."
    string group
    string scope "workspace | blog"
    boolean requires_two_factor "e.g. scripts.raw, members.manage"
    string description
  }
  roles {
    uuid id PK
    uuid workspace_id FK "null = built-in"
    string key "owner, admin, editor, author, reviewer, designer, viewer"
    string name
    boolean is_system
    boolean requires_two_factor
  }
  role_permissions {
    uuid role_id PK,FK
    string permission_key PK,FK
  }
  blog_members {
    uuid blog_id PK,FK
    uuid user_id PK,FK
    uuid role_id FK
    uuid invited_by FK
    timestamptz created_at
  }
```

- **2FA is enforced if any of these is true:** the workspace policy is `required_all`, the user's
  role has `requires_two_factor`, or the action's permission has `requires_two_factor`.
  The permission flag applies even when the workspace policy is `off`.
- Built-in roles have `workspace_id = null` and can't be edited. A custom role is a workspace-owned
  set of permissions (a paid feature).

## 3. Content

```mermaid
erDiagram
  blogs ||--o{ author_profiles : has
  users |o--o{ author_profiles : "may be"
  blogs ||--o{ categories : has
  categories |o--o{ categories : parent
  blogs ||--o{ articles : has
  author_profiles ||--o{ articles : writes
  categories ||--o{ articles : groups
  languages ||--o{ articles : "written in"
  articles ||--o{ article_revisions : "versioned by"
  articles ||--o{ article_suggestions : receives
  article_revisions ||--o| article_suggestions : "proposed as"
  article_suggestions ||--o{ suggestion_comments : discussed
  blogs ||--o{ tags : has
  articles ||--o{ article_tag : ""
  tags ||--o{ article_tag : ""
  blogs ||--o{ media : stores
  social_networks ||--o{ social_links : ""
  blogs ||--o{ social_links : ""
  author_profiles ||--o{ social_links : ""

  author_profiles {
    uuid id PK
    uuid blog_id FK
    uuid user_id FK "nullable: guest author"
    string display_name
    string slug "UK per blog"
    text bio
    uuid avatar_media_id FK
  }
  categories {
    uuid id PK
    uuid blog_id FK
    uuid parent_id FK
    string name
    string slug "UK per blog"
    string bgcolor
    int position
    timestamptz deleted_at
  }
  articles {
    uuid id PK
    uuid blog_id FK
    uuid author_profile_id FK
    uuid category_id FK
    uuid language_id FK
    uuid translation_group_id "same article, other languages"
    string slug "UK per blog+language"
    string status "draft | in_review | scheduled | published | archived"
    uuid current_revision_id FK "latest draft"
    uuid published_revision_id FK "live content"
    string bgcolor
    timestamptz scheduled_at
    timestamptz published_at
    uuid created_by FK
    timestamptz deleted_at
  }
  article_revisions {
    uuid id PK
    uuid article_id FK
    int number "UK per article"
    string title
    string description "meta description"
    jsonb body "block document"
    uuid cover_media_id FK
    uuid created_by_user_id FK
    uuid created_by_app_installation_id FK
    timestamptz created_at "immutable row"
  }
  article_suggestions {
    uuid id PK
    uuid article_id FK
    uuid base_revision_id FK
    uuid proposed_revision_id FK
    string status "pending | accepted | rejected | superseded"
    string source "human | app | ai"
    uuid requested_by_user_id FK
    uuid requested_by_app_installation_id FK
    uuid reviewed_by FK
    timestamptz reviewed_at
    text note
  }
  suggestion_comments {
    uuid id PK
    uuid suggestion_id FK
    uuid user_id FK
    text body
    jsonb anchor "range in revision body"
  }
  tags {
    uuid id PK
    uuid blog_id FK
    string name
    string slug "UK per blog"
  }
  article_tag {
    uuid article_id PK,FK
    uuid tag_id PK,FK
  }
  media {
    uuid id PK
    uuid blog_id FK
    uuid uploaded_by FK
    string disk "local | s3 | r2"
    string path
    string mime_type
    bigint size_bytes
    int width
    int height
    string alt_text
    jsonb variants "webp, thumbs"
    string checksum
  }
  social_networks {
    uuid id PK
    string key UK
    string name
    string icon
  }
  social_links {
    uuid id PK
    uuid blog_id FK
    uuid author_profile_id FK "null = blog link"
    uuid social_network_id FK
    string url
  }
```

- **Authors are `author_profiles`, not users.** This allows guest authors without accounts and
  per-blog author pages, and gives author-scope analytics a clear owner.
- **Publishing:** set `published_revision_id = current_revision_id`, set `status = published`, and
  emit `article.published` with the full rendered snapshot. A scheduled article is published by a
  job at `scheduled_at`.
- **Accepting a suggestion** creates a new revision on top of the latest one. If `base_revision_id`
  is no longer current, the suggestion must be rebased or marked `superseded`.
- AI and external apps can only create `article_suggestions` or draft revisions. They never set
  `published_revision_id`.

## 4. Integrations (analytics, ads, structured scripts)

```mermaid
erDiagram
  blogs ||--o{ integrations : "global scope"
  author_profiles ||--o{ integrations : "author scope"
  articles ||--o{ integrations : "article scope"

  integrations {
    uuid id PK
    uuid blog_id FK "always set"
    string scope "blog | author | article"
    uuid author_profile_id FK "set when scope=author"
    uuid article_id FK "set when scope=article"
    string provider "ga4 | google_ads | gtm | meta_pixel | clarity | plausible | custom"
    jsonb config "validated per provider: measurement_id, conversion_id/label..."
    string placement "head | body_start | body_end"
    string consent_category "analytics | ads | functional"
    jsonb page_rule "optional: article pages only, etc."
    boolean enabled
    uuid created_by FK
    timestamptz updated_at
  }
```

Check constraints:

```sql
CHECK (
  (scope = 'blog'    AND author_profile_id IS NULL AND article_id IS NULL) OR
  (scope = 'author'  AND author_profile_id IS NOT NULL AND article_id IS NULL) OR
  (scope = 'article' AND article_id IS NOT NULL AND author_profile_id IS NULL)
)
CHECK (provider <> 'custom' OR scope = 'blog')   -- raw scripts: global only
```

- `custom` (raw script) needs the `scripts.raw` permission, which has `requires_two_factor = true`.
- **Effective scripts for a page** = blog + that author's + that article's integrations,
  deduplicated by `(provider, config id)`. A single gtag loader serves every GA4/Ads ID.
- Any change emits `integrations.updated` for its scope. Frontends resolve the scripts at render
  time.

## 5. Publishing pipeline

```mermaid
erDiagram
  blogs ||--o{ outbox_events : emits
  blogs ||--o{ event_transports : "fans out via"
  blogs ||--o{ frontends : "consumed by"
  blogs ||--o{ snapshots : "exports"

  outbox_events {
    bigint id PK "identity"
    uuid blog_id FK
    bigint sequence "UK per blog, gapless"
    string type "article.published, media.updated..."
    string aggregate_type
    uuid aggregate_id
    int schema_version
    jsonb payload "full snapshot"
    timestamptz occurred_at
  }
  event_transports {
    uuid id PK
    uuid blog_id FK
    string driver "http_pull | webhook | kafka | nats | redis"
    text config "encrypted"
    boolean enabled
    bigint last_dispatched_sequence
    timestamptz last_error_at
    text last_error
  }
  frontends {
    uuid id PK
    uuid blog_id FK
    string name
    string base_url
    string store_adapter "sqlite | sqlcipher | d1 | r2 | custom"
    string sync_token_hash UK
    bigint last_acked_sequence
    timestamptz last_sync_at
    string core_version
  }
  snapshots {
    uuid id PK
    uuid blog_id FK
    bigint up_to_sequence
    string storage_path
    bigint size_bytes
    timestamptz created_at
  }
```

- `sequence` comes from `blogs.event_sequence`, read with `SELECT … FOR UPDATE` in the same
  transaction as the content write. This keeps it gapless and ordered per blog.
- `http_pull` is always available: `GET /sync?after={sequence}`, authenticated by the frontend's
  sync token. Other transports are push copies of the same log.
- A new frontend loads the latest `snapshot`, then pulls events after `up_to_sequence`. Old events
  can be pruned after a snapshot is taken.
- `frontends.last_acked_sequence` drives the "frontend X is behind" view in the console.

## 6. Apps & API

```mermaid
erDiagram
  workspaces |o--o{ apps : publishes
  apps ||--o{ app_installations : "installed as"
  blogs ||--o{ app_installations : installs
  app_installations ||--o{ personal_access_tokens : "authenticates with"
  users ||--o{ personal_access_tokens : "authenticates with"

  apps {
    uuid id PK
    uuid workspace_id FK "null = marketplace / first-party"
    string name
    string slug UK
    boolean is_first_party "Beseenly, AI writer..."
    string client_id UK
    string client_secret_hash
    jsonb redirect_uris
    jsonb requested_scopes
  }
  app_installations {
    uuid id PK
    uuid app_id FK
    uuid blog_id FK
    jsonb granted_scopes
    uuid installed_by FK
    timestamptz revoked_at
  }
  personal_access_tokens {
    bigint id PK
    string tokenable_type "User | AppInstallation"
    uuid tokenable_id
    string token UK
    jsonb abilities
    timestamptz expires_at
    timestamptz last_used_at
  }
```

- `personal_access_tokens` is Laravel Sanctum's table, made polymorphic over users and app
  installations.
- Each app installation is **scoped to one blog**. App writes are recorded in
  `article_revisions.created_by_app_installation_id` and `audit_logs`.

## 7. Engagement (written back from frontends)

```mermaid
erDiagram
  articles ||--o{ comments : receives
  comments |o--o{ comments : replies
  articles ||--o{ page_views : "viewed in"
  articles ||--o{ article_daily_stats : "rolled up"
  author_profiles ||--o{ article_daily_stats : "rolled up"

  comments {
    uuid id PK
    uuid blog_id FK
    uuid article_id FK
    uuid parent_id FK
    string author_name
    string author_email_hash
    text body
    string status "pending | approved | rejected | spam"
    boolean is_public
    string ip_hash
    uuid moderated_by FK
    timestamptz moderated_at
    timestamptz deleted_at
  }
  page_views {
    bigint id PK
    uuid blog_id FK
    uuid article_id FK
    uuid author_profile_id FK
    uuid frontend_id FK
    string visitor_hash "daily-salted"
    string referrer_host
    string country
    string device
    int duration_ms
    timestamptz viewed_at "monthly partitions"
  }
  article_daily_stats {
    uuid article_id PK,FK
    date day PK
    uuid blog_id FK
    uuid author_profile_id FK
    int views
    int unique_visitors
    int avg_duration_ms
  }
```

- Frontends are read-only, so comments and views are **POSTed to the console API**. An approved
  comment emits `comment.approved` and reaches the frontend through the normal event flow.
- `page_views` is raw and partitioned by month, with short retention. `article_daily_stats` is
  the rollup used by the blog and per-author dashboards.

## 8. Themes

```mermaid
erDiagram
  themes ||--o{ theme_licenses : "licensed by"
  workspaces ||--o{ theme_licenses : holds
  themes ||--o{ blog_themes : "used by"
  blogs ||--o{ blog_themes : uses

  themes {
    uuid id PK
    string slug UK
    string name
    string package_name "@sbblog/theme-x"
    string latest_version
    boolean is_premium
    int price_cents
    string currency
  }
  theme_licenses {
    uuid id PK
    uuid theme_id FK
    uuid workspace_id FK
    string license_key_hash
    timestamptz valid_until
  }
  blog_themes {
    uuid id PK
    uuid blog_id FK
    uuid theme_id FK
    string version
    jsonb config "colors, layout, header options"
    boolean is_active "one active per blog"
  }
```

---

## v1 → v2 mapping

| v1 | v2 | Notes |
|---|---|---|
| `users` | `users` + `workspace_members` + `blog_members` + `author_profiles` | Each v1 user becomes an Admin of the migrated blog and gets an author profile |
| `generals` | `blogs` | title, slogan, description, brand image → `brand_media_id`, `autoconvert_webp` |
| `generals.google_analytics` | `integrations` (ga4, scope blog) | |
| `generals.google_ads_script` | `integrations` (google_ads or custom, scope blog) | Parse into a structured config where possible |
| `generals.google_optimize_script` | dropped | Google Optimize was shut down in 2023 |
| `comment_configs` | `blogs.comment_policy` | The lookup table becomes an enum |
| `categories` | `categories` | + `blog_id`, `slug`, `parent_id` |
| `articles` | `articles` + `article_revisions` | title, description and body move to revision #1. `url_friendly` → `slug`, `premiere_date` → `scheduled_at` / `published_at`, `cover_path` → `media` |
| `article_tags` (free text) | `tags` + `article_tag` | Normalized and unique per blog |
| `article_comments` | `comments` | `authorized_public` → `status`; raw IP → `ip_hash` |
| `article_visits` | `page_views` + `article_daily_stats` | Raw IPs and user agents are dropped |
| `languages` | `languages` + `blog_languages` | |
| `social_networks`, `social_network_urls` | `social_networks`, `social_links` | Links can also belong to authors |

## Open questions

1. **Article body format:** a JSON block document (recommended, since any frontend can render it)
   or HTML as in v1? This decides how much rendering moves into `@sbblog/core`.
2. **Cross-workspace authors:** can one person author in two workspaces? The model allows it
   (one user, many `author_profiles`), but should billing count them once or per workspace?
3. **Analytics hosting:** should `page_views` live in Postgres, or go to a separate store
   (ClickHouse / Tinybird) for larger installs, with only the rollup kept here?
