# Phased remediation roadmap

Status date: 20 September 2026. Phase 0 immediate containment is deployed and
verified. Overall decision remains **NO-GO for a new production release** until the
applicable Phase 1 release blockers are verified. Each phase should be handled as
separate, reviewable tasks rather than one large change.

## Operating rules for every phase

1. Reverify the finding and production revision before planning the change.
2. Create a task-specific `fix/*` or `feature/*` branch from current `develop`.
3. Define a closed file allowlist, affected consumers, rollback, and scenario matrix.
4. Add focused regression evidence before changing behavior when feasible.
5. Keep authentication, webhook, queue, data, and deployment changes in separately
   reviewable tasks.
6. Run local, PostgreSQL, security, and environment checks proportional to risk.
7. Do not deploy until a human approver accepts the exact revision and residual risk.
8. Update the risk register and relevant source-of-truth document when a task closes.

## Phase 0 — Containment and recoverability

**Goal:** reduce immediate compromise/data-loss exposure before deeper changes.

**Findings:** `SEC-01`, `OPS-01`, `OPS-02`.

**Result on 20 September 2026:** completed for the immediate containment and
recoverability baseline. Pull request
`https://github.com/Khosravangroup/leadochat-mini/pull/8` was merged into `develop`
as `e8878e667e37115b07f86b9ddc634b5fabd72a24` and deployed to production.

**Work packages:**

1. **Completed baseline:** created encrypted off-host PostgreSQL, uploaded-file, and
   environment backups; verified checksums; restored all three in isolation. The
   database restore contained 39 tables, 4 users, 3 workspaces, 31 messages, and 9
   attachments. The upload restore contained 14 files. Snapshot identifier:
   `20260920T125105Z`. Full-service RTO and automated freshness remain Phase 3 work.
2. **Completed containment:** added paired MIME/original-extension allowlists for
   message attachments, aligned the browser picker, and added an Nginx storage
   script deny. Real-content regressions cover PHP, double-extension, renamed PHP,
   JFIF, Ogg Opus, and AAC/ADTS files. Permanent private attachment storage remains
   `SEC-01` Phase 1 work.
3. **Completed:** changed the live environment file to mode `600`, encrypted and
   restore-verified the active file and four historical copies, then securely
   removed the four plaintext backups. No plaintext backup artifact was retained.
4. **Assessed:** no PHP-like legacy upload, repository secret, untrusted interactive
   host user, or other confirmed credential exposure was found. Rotation was not
   performed without evidence; reassess immediately if new access evidence appears.

**Acceptance evidence:** isolated restores and checksums passed; the complete local
suite passed with 60 tests and 421 assertions; both Nginx configurations passed
`nginx -t`; production loaded one storage-deny block; `/up` returned HTTP 200;
all five containers remained running; configuration values remained loadable; no
credential was printed or committed.

**Rollback:** retain the last known-good Nginx configuration and application revision;
test configuration before reload; do not restore insecure file permissions as a
shortcut.

## Phase 1 — Application security and identity boundaries

**Goal:** close high-impact application vulnerabilities with focused tests.

**Findings:** `SEC-01`, `SEC-02`, `AUTH-01`, `AUTH-02`, `DATA-01`, `DATA-02`,
and the application portion of `OPS-05`.

**Order:**

1. Complete permanent private attachment storage and controlled download/preview.
2. Remove DOM XSS sinks and validate tag/department/agent names and colors.
3. Define the workspace role/capability matrix; implement policies/gates and
   cross-workspace negative tests route group by route group.
4. Implement effective email verification and configure deliverable transactional
   email; repair invitation/pre-verification behavior.
5. Replace direct owner deletion with transfer-or-confirmed-workspace-deletion and
   prove cascades on PostgreSQL.
6. Encrypt OAuth tokens, migrate existing records after backup, prevent log leakage,
   and decide provider token rotation.
7. Introduce CSP in report-only mode after removing unsafe inline DOM construction.

**Current progress:** item 1 has an implemented and locally verified candidate:
private message-attachment storage, workspace-authorized browser delivery,
short-lived signed Meta delivery, and idempotent legacy-file migration. It remains
open until the exact revision is deployed and the production migration/inventory is
verified.

**Acceptance gate:** all relevant security regression tests pass; no ordinary member
can perform owner/admin actions; no cross-workspace object is accessible; password
reset and verification emails arrive through the production provider; owner deletion
cannot silently cascade; migrated tokens remain usable and unreadable as plaintext.

**Deployment strategy:** one vulnerability family per change. Token encryption and
owner deletion must not share a migration/deploy task.

## Phase 2 — Dependencies, reproducible builds, CI, and repository governance

**Goal:** make every proposed revision reproducibly testable before deployment.

**Findings:** `DEP-01`, `DEP-02`, `CI-01`, `CI-02`, `FE-01`, the fast-feedback part
of `TEST-01`, and governance part of `REL-01`.

**Work packages:**

1. Add least-privilege pull-request CI for Composer validation/audit, PHP syntax,
   Pint, PHPUnit SQLite, PostgreSQL integration, clean npm install/audit/build,
   secret scanning, and focused SAST.
2. Update Composer dependencies in compatible groups, assessing each advisory's
   reachability and running the full regression suite.
3. Update npm direct and transitive dependencies from a clean lockfile install;
   preserve Vite/Tailwind behavior and browser smoke coverage.
4. Enable Dependabot/dependency alerts, GitHub secret scanning, and code scanning.
5. Protect `develop` and `main`; require review and checks, restrict force-push and
   deletion, and apply least-privilege workflow permissions.
6. Inventory the unique commits on `main` and `develop` and approve a non-destructive
   reconciliation plan.

**Acceptance gate:** required checks are green on the exact head; a clean frontend
artifact is reproducible; no unaccepted critical/high dependency advisory remains;
branch protection is effective; secret/code scanning reports are triaged.

## Phase 3 — Host hardening, monitoring, and operational resilience

**Goal:** minimize infrastructure attack surface and detect failures early.

**Findings:** `OPS-01` through `OPS-06`, `OBS-01`.

**Work packages:**

1. Establish a proven non-root administrative/deploy account before tightening SSH;
   disable password authentication if no approved dependency exists.
2. Enable a host firewall allowing only required management and web traffic; apply
   brute-force protection and verify unattended security updates.
3. Remove the public Reverb port mapping after Nginx/Cloudflare WebSocket validation.
4. Add tested Nginx upload-execution denial and security headers; promote CSP from
   report-only only after violations are resolved.
5. Automate encrypted backups, retention, freshness alerts, and quarterly restores.
6. Add dashboards/alerts for health, 5xx/error fingerprints, latency, resources,
   disk, PostgreSQL, queue depth/age/failures, webhook lifecycle, Reverb, TLS, and
   backup freshness.
7. Classify and dispose of historical failed jobs without exposing payloads.

**Acceptance gate:** only approved ports listen; a second session confirms SSH
access; WebSockets work through the supported path; headers pass compatibility tests;
alert delivery is tested; restore meets RPO/RTO.

## Phase 4 — Safe deployment and PostgreSQL release proof

**Goal:** make releases deterministic, observable, and recoverable.

**Findings:** `REL-01`, the deployment portion of `CI-01`, and `TEST-01`.

**Work packages:**

1. Select and enforce the canonical production promotion branch after reconciling
   current divergence.
2. Deploy an immutable commit/artifact rather than rebuilding an unpinned moving
   branch on the server.
3. Install production PHP dependencies without development packages and retain exact
   frontend build provenance.
4. Gate migrations on backup, PostgreSQL tests, and rollback/forward-fix review.
5. Add pre-deploy checks, a sub-five-minute smoke suite, running-SHA verification,
   and post-deploy health/error/queue gates.
6. Practice application rollback and data restore separately; record durations.
7. Create a distinct staging environment or document an approved low-risk substitute
   before risky integration changes.

**Acceptance gate:** a release candidate moves through CI, staging/equivalent,
approval, exact-revision deployment, smoke verification, and a practiced rollback
without manual source mutation on the server.

## Phase 5 — Architecture and testability

**Goal:** reduce change risk after security and release controls exist.

**Findings:** `ARC-01` and the remaining breadth of `TEST-01`.

**Work packages:**

1. Add characterization tests around the highest-risk inbox, social, and webhook
   behaviors.
2. Move authorization into policies and complex validation into Form Requests where
   Phase 1 has not already done so.
3. Extract one cohesive application/provider responsibility at a time from large
   controllers and the webhook job; preserve public contracts.
4. Move inline page behavior into focused JavaScript modules with safe DOM APIs and
   browser tests.
5. Add concurrency/idempotency, provider-failure, realtime reconnect, accessibility,
   and critical E2E coverage.

**Acceptance gate:** each slice reduces complexity metrics and line ownership while
all characterization and scenario tests stay green. No big-bang rewrite or
microservice split is allowed.

## Phase 6 — Documentation and continuous governance

**Goal:** keep controls from regressing after the initial program.

**Findings:** `DOC-01` plus recurrence controls for every closed item.

**Work packages:**

1. Review and merge the documentation index, context, runbooks, risk register, and
   project-local skills.
2. Assign release approver, operations/on-call, security contact, data owner, QA
   owner, and documentation owners.
3. Require documentation updates with routes, schema, configuration, architecture,
   and operations changes.
4. Review risks monthly, dependencies continuously, access quarterly, restore
   quarterly, and incident/release learnings after each event.
5. Archive obsolete handoffs while preserving history and clear provenance.

**Acceptance gate:** every current document has an owner/review cadence; closed risks
retain automated regression or monitoring; stale historical guidance is never routed
as current operating policy.

## Suggested execution order for the next tasks

1. `SEC-01` containment and permanent upload fix.
2. `OPS-01` backup plus successful isolated restore.
3. `SEC-02` stored XSS removal.
4. `AUTH-01` role matrix and authorization policies.
5. `DATA-01` safe owner deletion/transfer.
6. `DATA-02` encrypted OAuth-token migration.
7. `AUTH-02` effective verification and mail delivery.
8. CI plus reproducible frontend build, followed by dependency upgrades.
9. Branch/deployment reconciliation and host hardening.
10. Incremental architecture/test improvements.

This order may change only with new evidence, active exploitation, an outage, or an
explicit business priority decision. Record the reason rather than silently
reordering high-risk work.
