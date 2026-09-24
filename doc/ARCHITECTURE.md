# Architecture

## System shape

LeadoChat Mini is a Docker-based modular Laravel monolith. HTTP controllers,
domain models, provider services, queued jobs, Blade views, and realtime events are
deployed as one application codebase. The queue worker and Reverb server are
separate processes running the same code and configuration.

```text
Browser / Meta
      |
Cloudflare and TLS
      |
Nginx :80/:443 ---------> Reverb :8081 (/app websocket path)
      |
PHP-FPM application
      |          |
PostgreSQL    database queue worker
```

Microservices, an alternate framework, or machine-specific paths must not be
introduced without explicit architectural approval.

For the full request, data, realtime, provider, deployment, and performance
measurement map, see [`PERFORMANCE_ARCHITECTURE_MAP.md`](PERFORMANCE_ARCHITECTURE_MAP.md).

## Runtime components

| Component | Responsibility |
| --- | --- |
| `nginx` | TLS termination origin, static assets, PHP forwarding, Reverb proxy |
| `app` | Laravel web application and Artisan commands |
| `postgres` | Transactional data, sessions, cache, and queue tables |
| `queue` | Database-queue job processing with three attempts and backoff |
| `reverb` | WebSocket transport for realtime workspace updates |

Local topology is in `docker-compose.yml`; production topology is in
`docker-compose.prod.yml`.

## Application modules

### Public web and authentication

`PublicPageController` serves marketing, legal, contact, and landing pages.
Laravel Breeze controllers provide registration, login, password reset, email
verification routes, password confirmation, and logout. The `User` model implements
Laravel's email-verification contract; registrations, owner-created members, and
email changes require verification before workspace application access. A
delivery-capable production mail transport remains an operational prerequisite.

### Workspace and settings

`WorkspaceSettingsController` and the catalog/commerce controllers manage workspace
profile data, members, tags, departments, catalogs, products, offers, orders,
collections, product sets, and promotion campaigns. Workspace membership is a
tenant boundary. The Phase 1 candidate uses deny-by-default `workspace.access` and
`workspace.manage` gates plus controller-level object ownership checks. `AUTH-01`
remains in progress until that exact cumulative revision is deployed and smoke-
tested.

The Automation settings section stores per-Instagram-connection direct-message
rules under `ProviderConnection.meta.dm_automation`. Rules are disabled by
default, preserve unrelated provider metadata, and can be changed only through
the workspace-management capability. This feature adds no schema or environment
configuration.

### Inbox

`InboxController` owns conversation listing and state, messages, reactions, voice
and file attachments, internal notes, tags, department and agent assignment,
archive/trash/restore operations, catalog-product messages, and a realtime
snapshot. It is currently a large controller and must be decomposed only through
behavior-preserving, test-backed phases.

New locally uploaded message attachments are stored under the private `local`
disk. `MessageAttachmentController` serves them to authenticated members of the
owning workspace and supports HTTP Range responses for media. Meta receives a
short-lived signed provider URL with no session requirement. Remote inbound
provider URLs remain remote and are not rewritten as local files.

### Social and Instagram

`SocialController` owns Instagram posts, comments, stories, publishing, moderation,
and comment-to-DM bridging. Provider-specific API behavior is delegated to services
under `app/Services/Meta/Instagram`.
After persistence, `ProcessInstagramWebhookEvent` passes eligible
`WorkspaceRealtimeUpdated` message/comment updates directly to
`InstagramDmAutomationService` before broadcasting them through Reverb. The two
operations have independent failure boundaries, so realtime availability cannot
silently disable automation. The service limits execution to inbound story
replies and newly added active external comments, claims each source record
before the provider call, and delegates sends to `InstagramService`. Story replies use the
standard messaging endpoint; comment automation uses the native private-reply
contract and persists the outbound Inbox message. Claim/result state is stored in
the source record's existing JSON metadata so duplicate events cannot resend the
same automation. Failed sends remain failed rather than being retried
automatically, avoiding duplicate recipient messages after an ambiguous provider
response.
`InstagramInsightsController` and `InstagramInsightsService` add an owner-only,
read-only account Insights journey using the Instagram Login token and
`graph.instagram.com`. This is distinct from the Facebook Marketing API token
required for ad-account operations.

### Meta commerce

Services under `app/Services/Meta/Commerce` own discovery, diagnostics, catalog
synchronization, product-set and collection synchronization, order snapshots,
promotion previews, review packets, and app-review evidence.

The default Instagram-only evidence path is intentionally separated from live
commerce diagnostics. When `META_COMMERCE_REVIEW_SCOPES` is empty, it builds a
redacted aggregate packet without calling Facebook commerce endpoints. Commerce
diagnostics and packets remain deferred groundwork for a future, separately
authorized permission set.

Live diagnostics select the provider API family from the saved connection mode
and token scopes. Direct Instagram Login connections read account identity and
webhook subscriptions from `graph.instagram.com`; their locally recorded scopes
are audit context only and are not promoted to provider-reported grants. Catalog
checks that require a separate Facebook commerce authorization use an explicit
`not_applicable` state instead of presenting a false live failure. Legacy or
separately authorized commerce connections retain the Facebook Graph paths needed
for business, catalog, and commerce checks.

Provider diagnostics keep live and historical evidence separate. A successful
empty subscription response remains empty and cannot be replaced by saved callback
metadata. Locally recorded permission rows are returned for audit context, while
the live-grant set stays empty when the active token uses direct Instagram Login.

### OAuth and webhooks

The Instagram connection flow stores provider connection, requested-permission,
and token records. Configured scopes remain `requested` until independently
verified provider evidence exists; configuration is never treated as a grant.
Webhook verification and request-signature checking occur in
`InstagramWebhookController`; accepted events are stored as `WebhookEvent` records
and dispatched to `ProcessInstagramWebhookEvent`. Signature checking, event
idempotency, workspace resolution, and queue behavior are protected contracts.

The Phase 1 `DATA-02` candidate makes `OauthToken` the application encryption
boundary: access and refresh values are encrypted at rest, omitted from model
serialization, and read through hydrated models rather than raw query values.
Provider requests may receive decrypted credentials only in memory. Returned
diagnostics, exceptions, debug payloads, and persisted provider metadata must pass
through `ProviderSecretRedactor` before leaving the provider-service boundary.

### Realtime updates

`WorkspaceRealtimeUpdated` broadcasts workspace-scoped updates through Reverb.
Realtime transport is an enhancement, not a substitute for persisted source of
truth. Consumers must recover by refreshing a server snapshot after reconnect.

### Browser response security

`AddBrowserSecurityHeaders` is the application response boundary for MIME-sniffing,
framing, referrer, browser-permission, staged HSTS, and report-only CSP controls.
`CspReportController` accepts bounded public browser reports and emits only
normalized, query-free structured telemetry. This application layer does not cover
static or error responses served directly by Nginx; matching origin/edge controls
remain Phase 3 work. The policy and rollout contract are documented in
`SECURITY_HEADERS_AND_CSP.md`.

## Dependency direction

Controllers should validate and authorize the request, resolve workspace-owned
records, call a focused application/provider service, and return a response. Models
own relationships and casting. External Meta calls belong in provider services.
Long or retryable work belongs in jobs. Blade and JavaScript render server-provided
data but must not trust names, labels, provider content, or color values.

The current code does not consistently meet this target because several controllers,
the Instagram webhook job, and Blade scripts combine multiple responsibilities.
That debt is tracked as `ARC-01`; it does not justify a big-bang rewrite.

## Protected boundaries

Changes to these boundaries require explicit scope, consumer tracing, focused tests,
and a rollback plan:

- authentication, password reset, and email verification;
- workspace membership, roles, and record ownership;
- OAuth state, token storage, rotation, and Meta permissions;
- webhook signatures, idempotency, retries, and event lifecycle;
- queue payloads and failure behavior;
- public file storage and PHP execution;
- database migrations and cascade rules;
- Reverb channel authorization and event payloads;
- browser security headers, CSP report privacy, and policy enforcement;
- deployment branch, migrations, build artifacts, and rollback.

## Architecture decisions

- Keep a modular monolith.
- Keep PostgreSQL as the production database.
- Keep local and production Docker configuration separate.
- Prefer small domain services, policies, form requests, and focused jobs over
  expanding large controllers.
- Preserve backward compatibility unless a breaking change is explicitly approved.
- Treat code, migrations, tests, and current configuration as authoritative over
  historical phase documents.
