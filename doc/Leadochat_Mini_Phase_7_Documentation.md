# Leadochat Mini — Documentation for Phase 7

## Project Context
This document records the implementation progress for:

- Phase 7 — Instagram Webhook and Basic Sync Foundation

This phase was implemented after the project had already completed:
- infrastructure
- core technical setup
- public pages
- workspace system
- connection center
- local Instagram OAuth flow

The goal of this phase was not full production webhook integration yet, because real webhook testing requires a public server and domain.  
Instead, the goal was to make the project:

- local-ready
- staging-ready
- structurally correct for real Meta webhook testing later

---

# Phase 7 — Instagram Webhook and Basic Sync Foundation

## Goal
Build the first webhook foundation for Instagram so that the project can:

- verify webhook setup requests
- receive webhook payloads
- store raw event data
- dispatch processing jobs
- mark events as processed or failed

This phase creates the internal architecture needed before building:
- inbox sync
- message sync
- comments sync
- live event processing

---

## What Was Implemented

### 1) Webhook event data model
A dedicated model and table were created:

- `WebhookEvent`
- `webhook_events`

This table is the base event log for provider webhooks.

It stores:
- provider
- event type
- object
- raw headers
- raw payload
- status
- processing timestamps
- last error
- optional workspace relation
- optional provider connection relation

This makes the webhook pipeline inspectable and debuggable without putting logs inside the public panel UI.

---

### 2) Webhook database schema
The `webhook_events` table was designed with these key fields:

- `workspace_id`
- `provider_connection_id`
- `provider`
- `event_type`
- `object`
- `provider_event_id`
- `status`
- `source`
- `headers`
- `payload`
- `last_error`
- `processed_at`

Important decisions:
- `payload` is stored as `jsonb`
- `headers` are stored as `jsonb`
- `status` is explicit so each event can move through a lifecycle:
  - `received`
  - `processed`
  - `failed`

Indexes were also added for:
- provider + status
- workspace + provider

This supports future filtering and debugging at scale.

---

### 3) Webhook verification endpoint
A real verification route was created for Instagram:

- `GET /webhooks/meta/instagram`

This route handles the standard Meta webhook verification query parameters:
- `hub_mode`
- `hub_verify_token`
- `hub_challenge`

If the token matches, it returns the challenge as plain text.  
If not, it returns `403`.

This is exactly the type of endpoint that Meta will later call when the server is deployed publicly.

---

### 4) Webhook receive endpoint
A real receive route was created:

- `POST /webhooks/meta/instagram`

This route:
- receives the raw JSON payload
- extracts a first-level event type
- stores the event in `webhook_events`
- stores headers and payload
- marks the event initially as `received`
- dispatches a processing job

This means the webhook endpoint is now more than a simple logger; it is the first entry point of the sync pipeline.

---

### 5) Instagram webhook controller
A dedicated controller was created:

- `InstagramWebhookController`

It contains two methods:

#### `verify()`
Handles webhook verification requests.

#### `receive()`
Handles webhook payload delivery, stores the event, and dispatches a job for processing.

This separation keeps the webhook flow clean and easy to extend later.

---

### 6) Webhook verify token config
Instagram webhook verification configuration was added to:

- `config/services.php`
- `.env`

A dedicated setting now exists:

- `INSTAGRAM_WEBHOOK_VERIFY_TOKEN`

During local development a local debug token was used:
- `leadochat-mini-instagram-verify-token`

This allows the verification logic to work locally and later be replaced with a real staging value.

---

### 7) Separate route file for webhook endpoints
Webhook routes were moved into their own file:

- `routes/webhooks.php`

This keeps the project cleaner and prevents public routes, app routes, and webhook routes from becoming mixed together.

The new webhook route file is loaded through:
- `bootstrap/app.php`

This is important because webhook endpoints are integration infrastructure, not user-facing pages.

---

### 8) Local verify test succeeded
The verification endpoint was tested locally using `curl`.

Test used:
- `hub_mode=subscribe`
- correct verify token
- sample challenge value

Expected output:
- the same challenge value

Actual result:
- successful

This confirmed that the local verification logic is correct and staging-ready.

---

### 9) Local POST webhook test succeeded
A fake Instagram webhook payload was posted locally using `curl`.

The sample payload simulated:
- `object = instagram`
- entry changes
- a field such as `messages` or `comments`

The controller successfully:
- accepted the payload
- created a `webhook_events` row
- returned success JSON

This confirmed the receive pipeline works.

---

### 10) Processing job created
A queue job was added:

- `ProcessInstagramWebhookEvent`

This job is the first processing layer after webhook receipt.

Responsibilities:
- load a webhook event from the database
- extract basic data from payload
- update `event_type`
- update `object`
- set status to `processed`
- fill `processed_at`
- if an exception occurs:
  - set status to `failed`
  - save `last_error`

This is a foundation job, not the final message/comment sync job.  
Its purpose is to establish the event-processing architecture correctly before advanced sync logic is added.

---

### 11) Webhook receive now dispatches processing job
After saving the event, the controller now dispatches:

- `ProcessInstagramWebhookEvent`

This turns the webhook system into a proper async flow:
1. receive webhook
2. store raw event
3. dispatch job
4. process event later

That is the correct architecture for future production scaling.

---

### 12) Queue worker successfully processed webhook events
A Laravel queue worker was run manually:

- `php artisan queue:work --tries=1`

The worker successfully processed the queued webhook job and showed:
- `RUNNING`
- `DONE`

The corresponding event row then changed from:
- `received`

to:
- `processed`

This confirmed:
- queue dispatch works
- queue worker works
- webhook processing job works
- status transition works

---

### 13) Old unprocessed event was manually re-dispatched
An earlier event had been stored before the worker was running, so it remained in `received` state.

That event was manually dispatched using:
- `php artisan tinker --execute="App\Jobs\ProcessInstagramWebhookEvent::dispatch(1);"`

After that:
- it was processed successfully
- no failed jobs existed
- both stored webhook events reached `processed`

This helped confirm the system works consistently even if events were received before a worker started.

---

## Important Architectural Decisions

### 1) No webhook logs UI in the panel
A deliberate decision was made **not** to create a webhook logs page inside the user-facing panel.

Reason:
- the panel should stay clean
- the MVP should focus on visible product results
- raw logs are not necessary for the reviewer or end-user UI
- debugging can be done directly in the database

This was considered the more professional choice for the current stage of the project.

### 2) Logs remain in the database
Even though logs are not shown in the panel, all raw webhook events are still persisted in:
- `webhook_events`

This gives the project:
- traceability
- debugging ability
- future extensibility

without cluttering the visible UI.

### 3) Real public testing postponed to staging
This phase was intentionally implemented in a local-safe way.

Real Meta webhook delivery requires:
- a public server
- a public domain
- a reachable HTTPS URL

So Phase 7 was completed as:
- local-ready
- staging-ready

while acknowledging that the final real webhook test must happen later on the server.

---

## Problems Solved During Phase 7

### 1) Worker was not running when the first event arrived
The first event remained in `received` status because the queue worker was not active yet.

Fix:
- start the queue worker
- manually redispatch the old event

### 2) Need for async processing confirmed
The local tests showed that storing the event alone is not enough; a worker is required to move events through the processing lifecycle.

This validated the async design decision.

### 3) UI clutter was intentionally avoided
There was a consideration to expose webhook logs inside the panel.

Decision:
- do not expose logs in UI for now
- keep the UI focused on product flows
- use the database directly for inspection

This kept the product cleaner and more presentation-friendly.

---

## Final Result of Phase 7
At the end of Phase 7, the project can now:

1. verify Instagram webhook setup requests
2. receive Instagram webhook payloads
3. store raw payloads in the database
4. dispatch a queue job for each webhook event
5. process events asynchronously
6. mark webhook events as processed or failed
7. keep the user-facing panel clean while preserving debug data in the database

This means the webhook infrastructure is now in place and ready to support the next layers of the application.

---

## Key Files Added or Updated in Phase 7

### Models
- `src/app/Models/WebhookEvent.php`

### Migrations
- `create_webhook_events_table`

### Controllers
- `src/app/Http/Controllers/InstagramWebhookController.php`

### Jobs
- `src/app/Jobs/ProcessInstagramWebhookEvent.php`

### Routes
- `src/routes/webhooks.php`
- `src/bootstrap/app.php`

### Config
- `src/config/services.php`
- `src/.env`

---

## Current State After Phase 7
The project now has:
- workspace system
- connection center
- local Instagram OAuth flow
- webhook verification
- webhook ingestion
- queue-based webhook processing

This is a strong foundation for building the next product layer.

---

## Recommended Next Step

## Phase 8 — Unified Inbox Foundation
The next phase should start building:
- conversations schema
- messages schema
- inbox route and controller
- basic inbox UI
- first provider-aware message data model

This is the correct next step because the project now has:
- provider connections
- OAuth flow foundation
- webhook event ingestion
- processing foundation

and is ready to move toward visible message management.

---

## Final Note
Phase 7 completed the webhook side of the Instagram integration foundation and made the project structurally ready for real server-side testing later, while keeping the local development flow clean and practical.
