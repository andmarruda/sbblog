# SBBlog v2: security review (Z-04)

What the console does to protect accounts and content, and what is still left to do. Last
reviewed on 2026-11-03, at the end of phase 10.

## Sign-in

| Area | What is in place |
|---|---|
| Passwords | Hashed with bcrypt and rehashed when the cost changes. Login is limited to 5 failures per email and IP. Failures are audited with a hash of the email, never the address itself. |
| Two-factor (TOTP) | The secret and recovery codes are stored with Laravel encryption (`APP_KEY`). A code is accepted once: the last used time step is cached, so replays and older codes fail. There are 8 single-use recovery codes, shown only once. Setup, removal and regeneration need a recent password confirmation. |
| Login challenge | After the password step, the user is kept in the session as a pending login for 10 minutes, and nobody is authenticated yet. The challenge allows 5 attempts per user per minute, on top of a route limit of 10 per minute. Passing it regenerates the session. |
| Passkeys (WebAuthn) | Uses `lbuchs/webauthn` with `none` attestation. Each challenge lives in the session and is used once. The client data origin must equal `APP_URL` exactly, which is stricter than the library's suffix match. The sign counter is checked. The user handle must match the credential's owner. Passwordless login requires user verification (PIN or biometrics). As a second factor, only the pending user's passkeys are accepted. |
| Trusted devices | The cookie holds a 64-character random token and the database stores only its SHA-256 hash. It lasts 30 days, after which `model:prune` deletes it daily. Users can revoke one device or all of them. Removing the last second factor revokes them all. |
| Enforcement | Workspace policies (`required_all`, `required_by_role`) and 2FA-flagged permissions now send users without 2FA to **Account → Security** instead of returning a 403. The `Gate` still refuses a flagged permission, such as raw scripts or members, until 2FA is on. Set `SBBLOG_ENFORCE_TWO_FACTOR=false` only for local debugging. |

## Browser

- **Content-Security-Policy**, sent with every response:
  `default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'`.
  - Scripts need the per-request nonce. Vite tags and the inline theme script carry it.
  - `style-src 'unsafe-inline'` remains because Radix and Tiptap set `style` attributes. Style injection cannot run code.
  - `img-src https:` allows media served from R2 or S3 public URLs.
  - In local development, the Vite dev server origin and its WebSocket are added automatically.
- **Other headers:**
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Cross-Origin-Opener-Policy: same-origin`
  - `Permissions-Policy` turns off camera, microphone, geolocation, payment and USB.
  - HSTS is sent on HTTPS.
- **Session cookie:**
  - `HttpOnly` and `SameSite=Lax` are always set.
  - `Secure` defaults to on when `APP_ENV=production`; `SESSION_SECURE_COOKIE` overrides it.
  - Sessions are invalidated on logout, and other sessions can be signed out from **Account → Sessions**.
- **CSRF:** Laravel's token protects every console form and JSON call (`X-XSRF-TOKEN`). The API under `/api` uses bearer tokens instead of cookies.

## Rate limits

| Endpoint | Limit |
|---|---|
| Login | 5 failures per email and IP (`LoginRequest`) |
| 2FA challenge | 10/min per route, plus 5 failures per user |
| Passkey sign-in, options and verify | 20/min and 10/min |
| Register, password reset, email verification, password confirmation | 6/min |
| OAuth token exchange | 30/min |
| OAuth approve | 20/min |
| Content API v1 | 120/min per token owner (user or app installation) |
| Frontend sync and comments | 120/min per frontend; comments 5/min per reader IP hash |
| Token, secret and webhook-test actions | 6 to 10/min |

## Data handling

- **Tokens and secrets:** sync tokens, OAuth client secrets, personal access tokens and authorization codes are stored only as hashes and shown once.
- **Reader IPs:** stored as daily-salted HMACs. Emails in the audit log are SHA-256 hashes.
- **Webhooks:** private and reserved addresses are refused (SSRF guard) unless `sbblog.publishing.allow_private_webhooks` is on.
- **Raw scripts:** need a 2FA-flagged permission and the password, and are limited to the blog scope.
- **Article bodies:** validated against `body.schema.json`. Links allow `http(s)`, `mailto`, relative or `#` only. HTML is rendered and escaped by the console.

## Dependency audit

- `composer audit`: no advisories.
- `pnpm audit --prod`: two findings, both in the `shadcn` CLI's own dependencies. These are `minimatch` (moderate, ReDoS) and `deepmerge` (low). That CLI runs only when adding components and never ships to the browser. Revisit when `shadcn` updates them.

## Left for later

- Kafka, NATS and Redis transports are stubbed. Review their credentials handling when they are built.
- Passkey attestation is `none`, so authenticator models are not verified. That is enough for a CMS console. Revisit only if a customer requires certified authenticators.
- Run an external penetration test before the public launch.
