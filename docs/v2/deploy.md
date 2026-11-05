# SBBlog v2: deploy and v1 migration runbook

This is for whoever puts the v2 console on the Sysborg VPS and moves the v1 blog into it. The
pipeline is ready in the repository but **switched off** until the VPS steps below are done.

## What is in the repository

| File | Purpose |
|---|---|
| `Dockerfile` | Production image: nginx + PHP-FPM 8.4 (`serversideup/php`, pinned by digest) on port 8080, running as `www-data`. Assets are built in the image, config/route/view caches are built at start from the runtime `.env`, and migrations never run at start. |
| `docker/dev.Dockerfile` | The local development image, used by `docker-compose.yml`, now on PHP 8.4 |
| `.github/workflows/deploy.yml` | On a push to `main`: tests → image `ghcr.io/andmarruda/sbblog:<sha>` → `deploy.sh` over SSH → external check of `/up`. The deploy job runs only when the repository variable `DEPLOY_ENABLED` is `true`. |
| `deploy/deploy.sh` | Copy for `/home/deploy/sbblog/deploy.sh`. It takes a lock, makes a `pg_dump` backup, runs `migrate --force` in a throwaway container, swaps the containers, checks health and rolls back on failure. After a release it runs `snapshots:build`. |
| `deploy/docker-compose.yml` | Reference for `/home/deploy/sbblog/docker-compose.yml`. It defines `sbblog-app`, `sbblog-queue` and `sbblog-scheduler` on the same image, plus a volume for `storage/app`. |

## VPS checklist (by hand, once)

Follow the `sysborg-vps-environment` skill first. That covers the folder, database and role,
nginx vhost and certificate. Then:

1. **`.env`** in `/home/deploy/sbblog`, `chmod 600`. It needs:
   - `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL=https://<domain>`;
   - `DB_*` pointing at `postgres:5432`, plus `DB_NAME`, `DB_USER` and `DB_PASSWORD` for the backup;
   - `SESSION_DRIVER=database`, `CACHE_STORE=database` and `QUEUE_CONNECTION=database`;
   - `MAIL_*`;
   - `IMAGE_TAG=`.

   The session cookie is `Secure` automatically in production.
2. **nginx vhost:**
   - Proxy to `http://sbblog-app:8080` and send `X-Forwarded-Proto`, `X-Forwarded-For` and `Host`. The app trusts the proxy because the container port is never published.
   - Set `client_max_body_size 20m` for uploads.
   - Set `proxy_buffer_size 16k` and `proxy_buffers 8 16k`. Console responses carry about 3.5 KB of headers (CSP, cookies and preload links), close to nginx's 4 KB default.
3. **Compose:** copy `deploy/docker-compose.yml`, then run `docker compose up -d` once with a first tag.
4. **`deploy.sh`:** copy it from `deploy/`, then run `chown deploy:deploy`, `chmod 750` and `bash -n deploy.sh`.
5. **CI key:** create it with a forced command in `authorized_keys`. Store the GitHub secrets `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` and `VPS_KNOWN_HOSTS`, after checking the host fingerprint. Then set the variables `DEPLOY_URL=https://<domain>` and `DEPLOY_ENABLED=true`.
6. **GHCR:** if the package is private, log in to `ghcr.io` on the VPS as `deploy`, with a `read:packages` token.

Migrations must stay backward compatible (expand, then contract), because a rollback only
restores the previous image.

## First administrator

A fresh install (no accounts yet) sends visitors to `/setup`, which creates the instance
administrator and then closes for good. After that, people join only through invitations.

An install that already has accounts (for example after the v1 import below) never shows
`/setup`. Promote an existing account instead:

```
php artisan sbblog:admin you@example.com            # grant
php artisan sbblog:admin someone@example.com --revoke
```

Administrators manage accounts at `/admin/users`.

## Moving the v1 blog (M-01, M-02)

1. Add the v1 database to the console's `.env`:
   - `V1_DB_HOST`, `V1_DB_PORT`, `V1_DB_DATABASE`, `V1_DB_USERNAME`, `V1_DB_PASSWORD`;
   - `V1_DB_SCHEMA` if v1 does not use `public`.

   Also mount v1's `storage/app/public` folder read-only into the container.
2. Seed the reference data once:
   ```
   php artisan db:seed --class=LanguageSeeder
   php artisan db:seed --class=PermissionSeeder
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=SocialNetworkSeeder
   php artisan db:seed --class=ThemeSeeder
   ```
3. Do a dry run and read the warnings:
   ```
   php artisan sbblog:import-v1 v1 --workspace=sysborg --blog=blog \
       --owner=you@example.com --language=pt-BR --storage=/v1-storage --dry-run
   ```
4. Import by running the same command without `--dry-run`. It is safe to repeat: rows that are already imported are skipped, so a second run picks up comments written after the first.
5. Verify:
   ```
   php artisan sbblog:verify-v1 v1 --workspace=sysborg --blog=blog --sample=10
   ```
   It compares row counts, the title and text of every article (95% similarity or more), and renders a sample of articles. It exits with an error when something is missing.

### What the import does

- **Users:** each user keeps their password (v1 already used bcrypt) and becomes an Admin of the blog. Deleted users become authors without an account.
- **Article bodies:** Quill HTML is converted to Tiptap JSON and validated against `body.schema.json`.
  - Headings `h1` become level 2, and `h5` and `h6` become level 4.
  - Quill's indented items become nested lists.
  - YouTube and Vimeo frames become embeds.
  - Pasted images (data URIs) and files from v1 storage become media.
  - Colors, fonts and formulas become plain text.
  - Remote images and unsafe links are dropped, and each one is listed as a warning.
- **Article status:** a future `premiere_date` makes the article scheduled. Deleted articles are archived and moved to the trash.
- **Tags:** normalized to one tag per slug.
- **Comments:** approval maps to `approved` or `pending`. IPs are stored only as hashes, salted with the comment's own day.
- **Blog settings:**
  - Title, slogan, description, brand, comment policy and social links are copied. Twitter becomes X.
  - The GA4 ID becomes an integration.
  - The Google Ads script becomes an AW- integration, or a **disabled** custom script for review.
  - Optimize is dropped.
- **Not imported:** visits.
- **Publishing:** at the end, the imported state is published as events and a snapshot is queued.
