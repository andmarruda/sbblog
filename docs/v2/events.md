# SBBlog v2 — Publishing events

The console never renders public pages. Every change a reader can see is written to the blog's
**event log** (`outbox_events`), and frontends rebuild their own read store from it. This document
is the contract for `@sbblog/core`.

The JSON Schemas are the source of truth and live in this repo:

| File | What it describes |
|---|---|
| [`resources/schemas/body.schema.json`](../../resources/schemas/body.schema.json) | The article body: a Tiptap document limited to the nodes and marks below |
| [`resources/schemas/events/envelope.schema.json`](../../resources/schemas/events/envelope.schema.json) | One event as returned by the sync API |
| `resources/schemas/events/{type}.v{version}.schema.json` | The `payload` of each event type |
| [`resources/schemas/events/common.schema.json`](../../resources/schemas/events/common.schema.json) | Shared shapes: article, author, category, tag, media, blog, comment, integration |

Schema IDs are under `https://schemas.sbblog.dev/`. The console validates its own payloads against
these files in CI, so a schema change is always a visible diff.

## The log

- **One log per blog.** `sequence` starts at 1 and has no gaps: the blog row is locked while the
  next number is taken, in the same transaction as the content write. A rolled-back write leaves
  no event behind.
- **Events are full snapshots**, never patches. `article.published` carries everything needed to
  render the page, so a frontend never calls the console back.
- **Apply in order, idempotently.** Applying the same event twice must give the same result; on
  restart, resume after the last sequence you stored.
- **Unknown types are skipped**, so the console can add types without breaking older frontends.
- **`schema_version`** changes only for breaking payload changes. A new version gets a new schema
  file; the old one stays.

## Envelope

```json
{
  "sequence": 42,
  "type": "article.published",
  "schema_version": 1,
  "aggregate_type": "article",
  "aggregate_id": "01a1…",
  "occurred_at": "2026-11-03T14:00:00+00:00",
  "payload": { "article": { … } }
}
```

## Event types (v1)

| Type | Payload | Frontend action |
|---|---|---|
| `article.published` | `{ article }` | Upsert the article (first publish or a new live revision) |
| `article.unpublished` | `{ id, translation_group_id }` | Remove the article from the public site (unpublished, archived, rescheduled or deleted) |
| `category.upserted` / `category.deleted` | `{ category }` / `{ id }` | Upsert / delete; articles of a deleted category lose it |
| `tag.upserted` / `tag.deleted` | `{ tag }` / `{ id }` | Upsert / delete |
| `author.upserted` / `author.deleted` | `{ author }` / `{ id }` | Upsert / delete (author pages) |
| `blog.updated` | `{ blog }` | Replace blog-level settings: title, languages, brand, social links, comment policy |
| `comment.approved` / `comment.removed` | `{ comment }` / `{ id, article_id }` | Show / hide a comment |
| `integrations.updated` | `{ scope, author_profile_id, article_id, integrations }` | Replace the integration list of that scope |

Translations of an article share `translation_group_id`; build `hreflang` links from the articles
you hold with the same group. Media appear inside the payloads that use them (cover, body images,
avatars, brand), with absolute URLs and their variants (`webp`, `thumb`).

## Article body

`article.body` is a Tiptap (ProseMirror) JSON document. Allowed nodes: `paragraph`, `heading`
(levels 2–4), `bulletList`, `orderedList`, `listItem`, `blockquote`, `codeBlock`,
`horizontalRule`, `image` (by `mediaId`), `embed` (`youtube` or `vimeo` by `videoId`), `table`,
`tableRow`, `tableHeader`, `tableCell`, `text`, `hardBreak`. Allowed marks: `bold`, `italic`,
`underline`, `strike`, `code`, `subscript`, `superscript`, `link` (`http(s)`, `mailto`, relative
or `#` only).

Each `article` also carries `html`, rendered and escaped by the console, for frontends that don't
want to render the JSON themselves, plus `excerpt` (plain text) and `media` (every image the body
references).

## Getting events

`GET /api/sync?after={sequence}` with the frontend's sync token. New frontends first load the
latest snapshot, then pull events after its `up_to_sequence`. Push transports (webhook, Kafka,
NATS, Redis) deliver the same envelopes.
