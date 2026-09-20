# Leadochat Mini — Documentation for Phases 5 and 6

> **Historical record — not an operational source of truth.** This file describes
> an earlier implementation snapshot. Statements about readiness, provider behavior,
> and current schema may be obsolete. Start with [the documentation index](INDEX.md)
> and verify current code and environment before acting.

## Project Context
This document records the implementation progress for:

- Phase 5 — Connection Center
- Phase 6 — Instagram Login local OAuth flow

The project continues to use the same architecture established in earlier phases:

- Laravel
- Blade
- Breeze
- Filament
- Reverb
- PostgreSQL
- Docker Compose
- Nginx

These phases moved the project from basic workspace management into the first real provider integration flow.

---

# Phase 5 — Connection Center

## Goal
Build the shared provider connection foundation for the Meta integrations layer.

This phase was designed to support future connection flows for:

- Instagram
- Facebook Page + Messenger
- WhatsApp Business

The purpose was to create both:
- the database structure
- the first user-facing management screen

---

## Phase 5 — Completed Work

### 1) Connection data model
Three core models and tables were added:

- `provider_connections`
- `oauth_tokens`
- `provider_permissions`

These form the shared connection layer for all supported providers.

### 2) Database schema created

#### `provider_connections`
This table stores the main linked account for a provider.

Key fields:
- `workspace_id`
- `provider`
- `provider_account_type`
- `provider_account_id`
- `provider_account_name`
- `status`
- `connected_at`
- `last_synced_at`
- `meta`

Purpose:
- represent one provider account connection for one workspace
- allow provider-specific metadata
- support both pending and connected states

#### `oauth_tokens`
This table stores tokens associated with a provider connection.

Key fields:
- `provider_connection_id`
- `token_type`
- `access_token`
- `refresh_token`
- `expires_at`
- `scopes`
- `is_primary`

Purpose:
- store OAuth token information separate from the main connection
- support future token refresh logic
- keep the connection model cleaner

#### `provider_permissions`
This table stores granted permissions/scopes per connection.

Key fields:
- `provider_connection_id`
- `permission`
- `status`
- `granted_at`
- `expires_at`

Purpose:
- track granted scopes individually
- support future review diagnostics
- make permission state visible per provider account

### 3) Workspace relation added
`Workspace` was updated to include:
- `providerConnections()`

This made the connection system properly workspace-based.

### 4) Connection Center screen created
A real page was built at:
- `/connections`

This screen shows:
- current workspace information
- available providers
- saved provider connections

It currently includes 3 provider cards:
- Instagram
- Facebook Page + Messenger
- WhatsApp Business

This page became the shared entry point for all provider integrations.

### 5) Provider definitions finalized
The provider layer was clarified as follows:

#### Instagram
- uses Instagram Login
- will support messaging, comments, publishing, stories, and later ads-related flows

#### Facebook
- means **Facebook Page + Messenger**
- does **not** mean personal Facebook Messenger account access
- the scope is Page messaging, Page content, and Page comments

#### WhatsApp
- means WhatsApp Business / Embedded Signup flow
- prepared for future onboarding, messaging, templates, and webhooks

This clarification was important to keep the architecture aligned with official Meta APIs.

### 6) Seed data used for UI testing
Temporary test connections were inserted for:
- Instagram
- Facebook
- WhatsApp

Related test tokens and permissions were also inserted.

Purpose:
- validate the connection tables
- validate the saved connections table in the UI
- confirm the model relations work correctly

Later, those seed rows were removed to keep only the more realistic Instagram OAuth flow data.

## Phase 5 — Result
At the end of Phase 5, the project had:
- a working connection schema
- a working `/connections` page
- a shared provider model ready for real OAuth integration
- a clean provider structure tied to workspaces

This phase successfully prepared the project for the first real provider flow in Phase 6.

---

# Phase 6 — Instagram Login (Local Flow)

## Goal
Implement the first real provider integration flow using Instagram Login.

Because the project was still running locally, the goal was not to complete real production OAuth against Meta, but to build the full application-side flow so that the system would be ready for staging later.

## Phase 6 — Completed Work

### 1) Instagram connect routes added
Two routes were introduced:
- `/connect/instagram`
- `/connect/instagram/callback`

Purpose:
- start the OAuth flow
- receive the callback result

### 2) Instagram connection controller added
A dedicated controller was created:
- `InstagramConnectController`

Responsibilities:
- redirect the user to the Instagram authorization URL
- receive the callback
- pass control to the Instagram login service
- render a debug callback result page

### 3) Instagram login service created
A dedicated service was created:
- `InstagramLoginService`

This service now handles:
- authorization URL creation
- state generation
- state persistence in session
- callback state validation
- callback result normalization
- temporary pending connection creation
- token exchange handoff

### 4) Instagram config and environment settings added
The project was updated with Instagram-specific config inside:
- `config/services.php`

And corresponding `.env` values were added:
- `INSTAGRAM_CLIENT_ID`
- `INSTAGRAM_CLIENT_SECRET`
- `INSTAGRAM_REDIRECT_URI`
- `INSTAGRAM_SCOPES`

During local development, demo placeholder values were used.

The real Instagram App ID / Secret are intended to be used later on staging with a public redirect URI.

### 5) Instagram connect button activated
The Instagram card on `/connections` was upgraded from a disabled placeholder to a working connect button.

After CSS/debug troubleshooting, the final button worked and successfully redirected to the Instagram authorization page.

This confirmed:
- the route worked
- the controller worked
- the service generated a valid URL
- the UI was correctly connected to the backend flow

### 6) Local callback debug screen built
A dedicated callback debug page was created:
- `connections/instagram-callback.blade.php`

This page displays:
- callback status
- success or failure state
- callback payload
- exchange result details

This made debugging much easier during local development.

### 7) State validation implemented
A secure OAuth state flow was implemented:
- state generated before redirect
- state stored in session
- state returned in callback
- state compared using `hash_equals`

Possible callback states:
- `callback_received`
- `invalid_state`
- `callback_missing_code`

This was a critical part of making the flow production-ready later.

### 8) Pending Instagram connection logic added
When a valid callback was received, the project created or updated a `provider_connections` record with status:
- `pending_token_exchange`

The temporary record stored callback details inside `meta`, including:
- authorization code
- callback state
- raw incoming state
- authorization URL
- mode

This moved the flow beyond a simple demo and into a real application-side process.

### 9) Pending flow made visible in the UI
The `/connections` page was improved to distinguish between:
- pending OAuth callback rows
- demo seed rows
- connected rows

A dedicated yellow status badge was added for:
- `pending_token_exchange`

And the connection kind column clarified whether a row was:
- `Pending OAuth Callback`
- `Demo Seed Data`
- `Real Connection`

This made the integration progress understandable directly from the UI.

### 10) Demo connection cleanup completed
Older seed-based test rows were deleted so that only the more realistic Instagram flow remained.

Removed:
- demo provider connections
- demo tokens
- demo permissions

This left the environment clean for final OAuth flow testing.

### 11) Token exchange service created
A dedicated service was added:
- `InstagramTokenExchangeService`

Responsibilities:
- in local: simulate token exchange and store a debug result
- in staging/production: prepare for real POST request to Instagram token endpoint
- store final connection state
- create OAuth token row
- create permission rows

This service is the bridge between callback handling and final connected provider storage.

### 12) Schema improved for external OAuth identity
A new field was added to `provider_connections`:
- `external_oauth_user_id`

This allows the project to distinguish:
- internal workspace connection identity
- external provider-side user/account identity

This is important for future staging and production consistency.

### 13) Exchange flow connected to callback
After callback validation, the login service now calls the token exchange service.

In local mode:
- it generates a debug access token
- sets the connection status to `connected`
- assigns a debug external user id
- stores permissions
- stores token data

This means the callback now completes a full local mock integration cycle.

### 14) Final local retest succeeded
After cleanup and retesting with a fresh valid OAuth state:

The final local Instagram connection succeeded with:

#### provider_connections
- `provider = instagram`
- `provider_account_id = local-debug-instagram-user`
- `external_oauth_user_id = local-debug-instagram-user`
- `provider_account_name = Instagram OAuth User`
- `status = connected`

#### oauth_tokens
- one access token row created
- correct scopes stored
- `is_primary = true`

#### provider_permissions
Four permissions created:
- `instagram_business_basic`
- `instagram_business_content_publish`
- `instagram_business_manage_messages`
- `instagram_business_manage_comments`

This confirmed that the full local flow works end to end.

## Important Problems Solved During Phase 6

### 1) Missing connect button
The Instagram connect button did not appear at first.

Cause:
- view content and CSS/button rendering issues

Fix:
- view was debugged aggressively
- visible debug markers were added
- button rendering was simplified
- final visible button was restored

### 2) Callback invalid state
Some callback tests failed because:
- the incoming state was old
- the session state had already changed

Fix:
- always test callback using the latest state generated by the newest connect attempt

### 3) Duplicate local connected records
A previous local connected row caused a unique constraint conflict when a pending row tried to become connected with the same provider account id.

Fix:
- older connected test row was deleted
- retest was done with a clean table state

### 4) Missing `external_oauth_user_id`
The connection updated successfully but `external_oauth_user_id` stayed empty.

Cause:
- field existed in database but was missing from model `fillable`

Fix:
- `ProviderConnection` model was corrected
- flow was reset and retested successfully

### 5) Migration file naming mistake
A migration was accidentally written into a file literally named:
- `PASTE_FILENAME_HERE`

This caused the intended schema change not to apply.

Fix:
- a new migration with a real filename was created
- schema was corrected
- column and index were added properly

## Phase 6 — Result
At the end of Phase 6, the project has a complete local Instagram OAuth simulation flow:

1. user clicks connect
2. app redirects to Instagram authorization page
3. callback is received
4. state is validated
5. pending connection is created
6. token exchange service runs
7. connection becomes connected
8. token row is created
9. permission rows are created

This is now ready to be adapted for real staging usage by replacing the demo app credentials and using a public redirect URI.

---

# Current Project Status After Phases 5 and 6

## Completed so far
- Phase 1 — Infrastructure
- Phase 2 — Core technical foundation
- Phase 3 — Public site
- Phase 4 — Workspace and team foundation
- Phase 5 — Connection Center
- Phase 6 — Instagram Login local OAuth flow

## Current capabilities
The project can now:
- manage workspaces
- manage provider connection records
- show a connection center
- initiate Instagram Login
- validate Instagram callback state
- create pending connections
- simulate token exchange locally
- mark a provider connection as connected
- store tokens and permissions

## Key Files Added or Updated in Phases 5 and 6

### Models
- `src/app/Models/ProviderConnection.php`
- `src/app/Models/OauthToken.php`
- `src/app/Models/ProviderPermission.php`
- `src/app/Models/Workspace.php`

### Migrations
- `create_provider_connections_table`
- `create_oauth_tokens_table`
- `create_provider_permissions_table`
- `add_external_oauth_user_id_v2_to_provider_connections_table`

### Controllers
- `src/app/Http/Controllers/ConnectionController.php`
- `src/app/Http/Controllers/InstagramConnectController.php`

### Services
- `src/app/Services/Meta/Instagram/InstagramLoginService.php`
- `src/app/Services/Meta/Instagram/InstagramTokenExchangeService.php`

### Views
- `src/resources/views/connections/index.blade.php`
- `src/resources/views/connections/instagram-callback.blade.php`

### Config
- `src/config/services.php`
- `src/.env`

### Routes
- `src/routes/web.php`

## Recommended Next Step

## Phase 7 — Instagram Webhook and Basic Sync
The next phase should implement:
- webhook verification route
- webhook receive route
- raw webhook event storage
- first Instagram event processing job
- sync/logging foundation for future inbox and comments

This is the correct next step because the project now has:
- workspaces
- provider connections
- callback flow
- tokens
- permissions

and is ready to receive real provider events.

## Final Note
Phases 5 and 6 successfully transformed the project from a static MVP shell into a real provider-aware integration system with a working local OAuth simulation for Instagram.
