# Environment variables

This document defines configuration names and intent, never secret values. The
committed template is `src/.env.example`; actual environment files remain ignored.

## Application

| Variable | Required | Secret | Purpose |
| --- | --- | --- | --- |
| `APP_NAME` | yes | no | Display/application name |
| `APP_ENV` | yes | no | Runtime environment such as local or production |
| `APP_KEY` | yes | yes | Laravel encryption and signing key |
| `APP_DEBUG` | yes | no | Must be `false` in production |
| `APP_URL` | yes | no | Canonical external URL |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | yes | no | Locale behavior |
| `APP_PREVIOUS_KEYS` | during rotation | yes | Safe Laravel key rotation support |

`APP_KEY` is also the encryption root for stored OAuth access and refresh tokens in
the Phase 1 `DATA-02` candidate. A deploy must never generate or replace it when
token rows already exist. Losing the active key and all applicable previous keys
makes those provider credentials unreadable. `APP_PREVIOUS_KEYS` permits a staged
read transition; it does not by itself prove every ciphertext has been re-encrypted
under the new primary key. Treat key rotation as a separate data migration with a
fresh backup, row-count verification, provider smoke test, and rollback window.

## Browser security headers

| Variable | Default | Purpose |
| --- | --- | --- |
| `SECURITY_HEADERS_ENABLED` | `true` | Emergency switch for the complete Laravel response-header layer |
| `CSP_REPORT_ONLY_ENABLED` | `true` | Emit report-only CSP plus reporting endpoint; never enables enforcement |
| `CSP_REPORT_MAX_BYTES` | `65536` | Maximum accepted CSP report request body |
| `CSP_REPORT_MAX_BATCH` | `20` | Maximum reports processed from one Reporting API batch |
| `SECURITY_HSTS_ENABLED` | `true` | Emit staged HSTS only when Laravel identifies HTTPS |
| `SECURITY_HSTS_MAX_AGE` | `86400` | Staged HSTS lifetime in seconds; no subdomain/preload scope |

Keep `APP_URL` on the canonical HTTPS origin in production because it is used to
construct `Reporting-Endpoints`. Disabling HSTS stops new headers but does not clear
a browser's cached policy before its prior `max-age` expires. CSP enforcement,
`includeSubDomains`, preload, and a longer HSTS lifetime require separate approval
after measured compatibility evidence. See `SECURITY_HEADERS_AND_CSP.md`.

## Database, cache, queue, and session

| Variable group | Production expectation |
| --- | --- |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | PostgreSQL connection; password is secret |
| `CACHE_STORE` and optional `DB_CACHE_*` | Current design supports database cache |
| `QUEUE_CONNECTION`, `DB_QUEUE_*`, `QUEUE_FAILED_DRIVER` | Current design uses the database queue and failed-job storage |
| `SESSION_DRIVER`, `SESSION_*` | Current design uses database sessions; secure-cookie settings must match TLS |

Production must not use the local Compose password. Do not put credentials in
Compose files, documentation, shell history, CI logs, or command arguments.

## Realtime and broadcasting

| Variable | Secret | Purpose |
| --- | --- | --- |
| `BROADCAST_CONNECTION` | no | Laravel broadcast driver |
| `REVERB_APP_ID` | no | Application identifier |
| `REVERB_APP_KEY` | treat as sensitive | Client/server application key |
| `REVERB_APP_SECRET` | yes | Server-side signing secret |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | no | Browser-facing connection |
| `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`, `REVERB_SERVER_PATH` | no | Reverb bind configuration |
| `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | public build values | Browser bundle configuration; never place the app secret here |

## Mail

`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME` configure password reset and email
verification delivery. The verified production snapshot used `MAIL_MAILER=log`,
which does not deliver messages to users and is an open release blocker.

`AUTH_EMAIL_VERIFICATION_EXPIRE` controls signed verification-link lifetime in
minutes and defaults to `60`. Password-reset expiry and token throttling remain in
Laravel's `auth.passwords.users` configuration. Before release, run:

```bash
php artisan app:mail-check
```

The command checks delivery capability and required SMTP fields without printing
credential values. A passing configuration check does not replace an inbox delivery
test through the actual provider.

## Meta and Instagram

| Variable | Secret | Purpose |
| --- | --- | --- |
| `INSTAGRAM_CLIENT_ID` | no | OAuth client ID |
| `INSTAGRAM_CLIENT_SECRET` | yes | OAuth client secret |
| `INSTAGRAM_APP_SECRET` | yes | Instagram request-signing secret where used |
| `META_APP_ID` | no | Meta application ID |
| `META_APP_SECRET` | yes | Meta application secret |
| `INSTAGRAM_WEBHOOK_APP_ID` | no | Webhook application ID |
| `INSTAGRAM_WEBHOOK_APP_SECRET` | yes | Webhook signature secret |
| `INSTAGRAM_WEBHOOK_VERIFY_TOKEN` | yes | Webhook verification token |
| `INSTAGRAM_REDIRECT_URI` | no | Exact OAuth callback URI |
| `INSTAGRAM_GRAPH_VERSION`, `META_GRAPH_VERSION` | no | Pinned Graph API versions |
| `INSTAGRAM_SCOPES` | no | Five requested Instagram Login permissions |
| `META_COMMERCE_REVIEW_SCOPES` | no | Optional Facebook Login/Marketing API permissions; empty for the current Instagram-only review |
| `INSTAGRAM_WEBHOOK_SUBSCRIBED_FIELDS` | no | Requested webhook fields |
| `INSTAGRAM_WEBHOOK_LOG_LEVEL` | no | Webhook-specific logging level |

Use the least permissions required. Redirect URIs and webhook URLs must exactly
match provider configuration. Never log authorization codes, access tokens,
refresh tokens, app secrets, verify tokens, or full signed payloads.

The current App Review submission is limited to the five permissions in
`INSTAGRAM_SCOPES`. `META_COMMERCE_REVIEW_SCOPES` defaults to an empty value and
must remain empty for this Instagram Login review. It is reserved for a future,
separately authenticated Facebook Login/Marketing API integration and is never
appended to the Instagram OAuth request. Do not infer that a commerce scope is
granted from its presence in configuration or in local connection metadata. See
`META_APP_REVIEW_PERMISSION_MATRIX.md` for the code-backed journey inventory.

## Files, logs, and optional providers

- `FILESYSTEM_DISK` chooses default storage; uploaded content must not become
  executable through a public disk.
- `ATTACHMENT_PROVIDER_URL_TTL_MINUTES` controls the lifetime of signed private
  attachment URLs supplied to external providers. It defaults to 60 minutes and is
  clamped to a range of 1 through 1440 minutes.
- `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, and provider-specific log variables
  control logging. Production logs must not contain secrets or customer payloads.
- AWS, Redis, SQS, Pusher, Ably, Postmark, Resend, Slack, and Papertrail variables
  are supported by framework configuration but are not proof that those services
  are active.

## Environment checklist

Before starting an environment:

1. Generate unique keys and passwords; never copy local defaults.
2. Set `APP_DEBUG=false` outside local development.
3. Configure PostgreSQL, mail delivery, queue, Reverb, and the exact public URL.
4. Configure only required Meta permissions and secrets.
5. Restrict the environment file to the service account; target mode is `600`.
6. Validate configuration without printing secret values.
7. Cache production configuration only after all values are correct.
8. Run `php artisan oauth-tokens:check-encryption` after the `DATA-02` migration;
   record counts and exit status only, never token values.
