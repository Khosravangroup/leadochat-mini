# API and routes

LeadoChat Mini currently exposes browser-oriented Laravel routes rather than a
versioned public JSON API. Treat named routes and response shapes as internal web
contracts unless a provider contract requires otherwise.

Generate the exact current inventory with:

```bash
docker compose exec app php artisan route:list
```

The audited revision registered 127 routes. The cumulative Phase 1 candidate,
including the two `DATA-01` ownership routes and CSP report receiver below,
registers 132 routes.

## Public pages

Public `GET` routes include:

- `/`, `/features`, `/about`, `/pricing`, and `/contact`;
- `/privacy-policy`, `/policies-and-procedures`, and `/data-deletion`;
- product landing pages for omnichannel inbox, Instagram DM automation, WhatsApp,
  Messenger, customer analytics, sales funnel, and shared inbox;
- authentication entry points such as `/login`, `/register`, and password reset;
- Laravel's health endpoint `/up`.

Public pages must not expose authenticated workspace or provider data.

## Authenticated application

Routes under the `auth` and effective `verified` middleware cover:

- `/dashboard`;
- `/connections` and Instagram OAuth connect/callback;
- `/inbox` and conversation messages, attachments, voice, notes, assignment,
  tags, archive, trash, restore, catalog shares, and realtime snapshot;
- `/social/instagram` posts, comments, stories, publishing, moderation, and DM
  reply;
- `/settings` for workspace, team, tags, departments, catalogs, products, offers,
  Meta commerce sync/diagnostics/evidence, orders, product sets, collections, and
  promotions.

Profile edit, password, account deletion, and workspace ownership-resolution routes
require `auth` but are outside the `verified` group. Keeping ownership resolution
available to an unverified authenticated owner prevents verification state from
making a workspace impossible to transfer or explicitly delete.

### Profile ownership routes

| Method | Path | Named route | Contract |
| --- | --- | --- | --- |
| `POST` | `/profile/workspaces/{workspace}/transfer-ownership` | `profile.workspaces.transfer` | Current owner only; requires current password and an existing non-self member; transfers authoritative ownership atomically |
| `DELETE` | `/profile/workspaces/{workspace}` | `profile.workspaces.destroy` | Current owner only; requires current password and the exact workspace slug; intentionally cascades scoped data |
| `DELETE` | `/profile` | `profile.destroy` | Requires current password and is rejected while any owned workspace remains |

Foreign workspace identifiers return `404`. The transfer and delete operations
write audit evidence. They are browser form endpoints with CSRF protection, not a
public JSON API.

`User` implements Laravel's email-verification contract. New registrations and
owner-created members remain unverified and receive a verification notification;
email changes clear verification and send a fresh link. Production access still
depends on configuring a delivery-capable mail transport. Workspace membership is
not sufficient for privileged settings operations; the role gates below apply.

### Instagram OAuth callback

The `GET /connect/instagram` and `GET /connect/instagram/callback` routes require
an authenticated, email-verified workspace manager. The callback accepts only the
authorization state saved in that browser session for the manager's current
workspace, consumes it once, and does not persist the state, authorization URL, or
authorization code in connection metadata. A repeated or mismatched callback
fails without another token exchange. A success redirect requires a completed
token exchange; a received callback alone is not proof of connection. This is the
`fix/meta-review-software-readiness` candidate contract, not yet a verified
production behavior. No live Instagram channel was exercised for this candidate.

### Meta App Review diagnostics

`GET /social/instagram/insights` is restricted to a verified workspace manager.
It selects only a connected Instagram account in the current workspace and
reads seven-day account reach, views, and total interactions from
`graph.instagram.com` with its Instagram token. Provider errors are not reflected
into the HTML response. Missing metric data is shown as unavailable, not zero.
The response is private and not cached. No provider call has been made against a
live channel for this candidate.

The current OAuth request is limited to `instagram_business_basic`,
`instagram_business_manage_messages`, `instagram_business_manage_comments`,
`instagram_business_content_publish`, and
`instagram_business_manage_insights`. Configured scopes are persisted locally as
`requested`; local configuration alone never produces a `granted` status.

`POST /settings/commerce/connections/{connection}/app-review-evidence` builds the
current Instagram-only packet from workspace-scoped aggregate counts and the
configured five-scope journey matrix. With the default empty
`META_COMMERCE_REVIEW_SCOPES`, it makes no commerce-provider request and does not
include commerce, order, campaign, message, comment, customer, or webhook payload
samples in the exported JSON. Live Meta grants and behavior remain a separate
verification gate.

`POST /settings/commerce/connections/{connection}/diagnostics` and the review
packet route remain owner-only and workspace-scoped. The diagnostic now separates
provider-reported grants from local code evidence. A configured commerce scope
without a demonstrated API journey appears in
`permissions.without_demonstrated_api_journey`; an unsupported Instagram OAuth
scope appears separately in
`permissions.instagram_without_demonstrated_api_journey`. Either adds a failing
readiness item and blocks the commerce review summary. The exported packet
carries both gap lists and contains aggregate counts rather than recent message,
comment, customer, post, webhook-error, order, or campaign samples. A grant alone
never proves the feature works. See
`META_APP_REVIEW_PERMISSION_MATRIX.md`.

For a direct Instagram Login connection, diagnostics read identity and subscribed
webhook fields from `graph.instagram.com` with the token in the authorization
header. The unsupported Facebook `/me/permissions` probe is not attempted; saved
permission rows are reported with their recorded status for audit context but are
never treated as live provider grants. Catalog probes are marked
`not_applicable` instead of failed when a separate Facebook commerce authorization
is required. Facebook Graph diagnostics remain available only for the legacy or
separately authorized commerce path.

When the direct identity response contains both `id` and `user_id`, diagnostics
use `user_id` as the canonical Instagram account identifier because it is the
value stored on the provider connection and used by account-scoped Instagram
endpoints.

The diagnostics response exposes `graph_api_family`, the effective
`graph_version`, both configured values under `graph_versions`, and a three-state
`outcome` on provider checks. Live webhook fields are derived only from a
successful provider response; saved callback fields remain separately available
as `webhook.saved_verified_fields`. Review packets that preserve the three-state
catalog contract use schema version `2`.

## Meta webhook

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/webhooks/meta/instagram` | Provider subscription verification |
| `POST` | `/webhooks/meta/instagram` | Signed Instagram event receipt |

The verification token and request signature are distinct controls. The `POST`
endpoint must validate the raw request body before parsing or queuing. Accepted
events are stored and dispatched to the database queue. Preserve provider event
identity, idempotency, event state, retry behavior, and workspace resolution.

The audited invalid verification request returned `403`; this negative check is
safe for smoke testing. Never place a real token in a URL, command history, test
fixture, or log.

## CSP report receiver

| Method | Path | Named route | Purpose |
| --- | --- | --- | --- |
| `POST` | `/csp-reports` | `csp.report` | Receive normalized report-only CSP telemetry |

The endpoint is intentionally public and does not use browser CSRF state. It is
rate-limited, body- and batch-bounded, and logs only an allowlist of normalized
fields after removing URL query strings and fragments. It must never log raw
reports, `script-sample`, cookies, authorization data, or arbitrary payload keys.
See `SECURITY_HEADERS_AND_CSP.md` for the rollout and privacy contract.

## Authorization contract

Every authenticated handler must enforce all applicable layers:

1. authenticated identity;
2. verified email when the product requires it;
3. workspace membership;
4. required workspace role/capability;
5. ownership of each route-bound resource by that workspace;
6. provider connection ownership and status for external actions.

Use Laravel policies or gates for durable domain authorization. Do not rely on UI
visibility, request IDs, or route-model binding as authorization. Negative tests
must cover cross-workspace IDs, ordinary-member attempts, deleted/inactive records,
and unauthenticated requests.

### Workspace role/capability matrix

The current application uses two explicit gates. Unknown or missing roles are
denied by default.

| Role | `workspace.access` | `workspace.manage` | Intended scope |
| --- | --- | --- | --- |
| `owner` | Allow | Allow | Inbox operations plus workspace configuration, team, channels, catalogs, commerce, OAuth, publishing, and moderation |
| `agent` | Allow | Deny | Dashboard, inbox operations, attachments, and read-only social views |
| `member` | Allow | Deny | Same current operational surface as `agent`; keep separate for future least-privilege refinement |
| Unknown/missing | Deny | Deny | No workspace application access |

The application treats `workspaces.owner_id` as authoritative owner evidence even
if a legacy pivot role is inconsistent. Every route in the authenticated workspace
group requires `workspace.access`. All `settings.*` routes, connection/OAuth routes,
social publish/moderation routes, and workspace-tag creation additionally require
`workspace.manage`. Route-bound controllers must still return `404` for a resource
owned by another workspace; a role gate does not replace object ownership checks.

## Validation and response rules

- Use focused Form Requests where request validation or authorization is complex.
- Allowlist attachment extensions, MIME types, and content expectations; store
  untrusted uploads outside any executable public path.
- Serve locally stored message attachments through the authenticated
  `attachments.show` route after workspace ownership checks. Provider delivery uses
  the separate signed, expiring `attachments.provider` route; never persist or log
  its temporary signature.
- Escape untrusted names and labels. Build DOM nodes with safe text properties,
  not interpolated `innerHTML`.
- Do not return tokens, secrets, raw exception messages, or provider payloads.
- Map provider failures into stable application errors while retaining redacted
  diagnostic context server-side.
- Rate-limit abuse-sensitive authentication, OAuth, webhook, publish, and message
  operations according to measured needs.

## Realtime contract

Reverb is proxied under `/app`. Broadcast events must be workspace-scoped and
authorized. Clients must handle disconnect/reconnect and use the inbox realtime
snapshot to recover persisted state. A WebSocket event must not become the only
record of a message or state transition.

## Change checklist

When changing a route or provider contract:

- list every caller and consumer;
- define success, validation, authorization, cross-workspace, provider-failure,
  retry/idempotency, and compatibility scenarios;
- add focused tests before implementation where behavior changes;
- update this document and environment requirements;
- document any breaking change and rollback procedure.
