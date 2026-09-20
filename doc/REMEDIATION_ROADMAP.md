# Phased remediation roadmap

Status date: 20 September 2026. Phase 0 immediate containment and the cumulative
Phase 1 application release are deployed and verified at their documented scopes.
Future production releases remain **NO-GO** unless their applicable release gates
and residual-risk decisions are satisfied. Each phase should be handled as separate,
reviewable tasks rather than one large change.

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

**Current progress:** items 1 through 7 were implemented, locally verified, merged,
and cumulatively deployed as
`b7e948f4325c5fc318f4ab9f7274319d72b3d32a`. Item 1 provides private
message-attachment storage,
workspace-authorized browser delivery, short-lived signed Meta delivery, and an
idempotent legacy-file migration. Item 2 replaces the identified tag, department,
and agent `innerHTML` sinks with safe DOM construction and consistently allowlists
colors at both controller and model boundaries. Item 3 introduces a documented,
deny-by-default workspace role/capability matrix, route gates, owner-only privileged
operations, and cross-workspace negative tests. Item 4 makes email
verification effective, removes member pre-verification, adds dispatch/expiry/replay/
rate-limit coverage, and introduces a secret-safe mail configuration check. It
remains operationally blocked because production has no delivery-capable mail
credential. Item 5 blocks account deletion until every owned workspace is resolved,
adds password-protected ownership transfer and slug-confirmed workspace deletion,
preserves audit evidence, and proves the intentional cascade on PostgreSQL 16.
Item 6 adds encrypted OAuth-token casts, a transactional idempotent data migration,
count-only verification, and provider-boundary redaction; its PostgreSQL
forward/rollback/forward drill passed. No confirmed exposure justified provider
rotation, so rotation remains evidence-triggered rather than assumed.
The production legacy-file migration found zero eligible records and zero failures.
A fresh encrypted off-host backup passed checksum and isolated restore checks before
the data migrations. The workspace-audit and OAuth-token migrations applied, the
production `APP_KEY` was preserved, and count-only token verification found zero
unencrypted or unreadable fields. Item 7 adds application-wide
browser defense headers, HTTPS-only staged HSTS, report-only CSP, and a
privacy-bounded, rate-limited report receiver. Its current broad inline/media/
WebSocket allowances are explicitly temporary and enforcement is not enabled. The
headers and report receiver are live, and a synthetic report returned `204`.

Phase 1 application implementation and production rollout are complete. Operational
acceptance is not fully closed: `AUTH-02` still lacks a delivery-capable production
mail credential; authenticated role, XSS, inbox, attachment/provider, and deletion
smoke journeys need an approved synthetic production target; provider connectivity
after token encryption has not been exercised; and `OPS-05` still needs browser
telemetry observation before source narrowing or CSP enforcement. The owner approved
the exact release with the known temporary `MAIL_MAILER=log` risk, but that approval
does not close the finding.

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

**Progress on 21 September 2026:** pull request `#19` implements the CI foundation
and repository-side Dependabot configuration. Its exact head
`34e32795a884b39fb7435e7f4c60a6cb6c80c65d` passed all five jobs: full PHPUnit on
SQLite and PostgreSQL 16, PHP validation and changed-file Pint, clean frontend
install/build with a retained artifact, full-history Gitleaks, focused Semgrep, and
report-only dependency audits. The audit steps are not yet blocking because the
locked Composer and npm trees still contain known advisories. Dependency upgrades,
GitHub security-feature enablement, effective branch protection, and non-destructive
`develop` to `main` promotion remain required before this phase can close.

Pull request `#20` is the Composer-remediation candidate. Its lockfile has zero
known Composer advisories and its exact head passed both 114-test database jobs,
the clean frontend build, and the secret/SAST job. Merge and required-check
enforcement remain necessary before `DEP-01` can close.

Pull request `#21` is the npm-remediation candidate. Its exact lockfile installed
cleanly from the official npm registry, built with Vite 8.3.0, and reported zero npm
vulnerabilities. Both database jobs and the security job also passed. Merge and
required-check enforcement remain necessary before `DEP-02` can close.

Pull request `#22` is the enforcement candidate. Its exact head
`26e9763c4b51d081daa6c1f5c73a21643af75a73` passed the newly blocking Composer and
npm audit job, both database jobs, the frontend build, full-history Gitleaks, and
focused Semgrep. The Semgrep SARIF upload created a successful GitHub code-scanning
check with zero results across four rules. Weekly scheduling and Ubuntu 24.04 runner
pinning are included. Merge, repository security settings, protected-branch
requirements, and `develop` to `main` promotion remain.

**Result on 21 September 2026:** the scoped Phase 2 repository-governance work is
complete. Pull requests `#19` through `#22` and `#31` are merged; pull request `#29`
preserved both branch histories while promoting the complete `develop` tree to
`main`. Composer and npm audits report zero findings and block merges. The six CI
and code-scanning checks are required on protected `develop` and `main`; one approval,
last-pusher separation, stale-review dismissal, conversation resolution, and current
branch state are required; force-push and deletion are disabled. Dependabot alerts
and security updates, secret scanning and push protection, weekly full scans, and
Semgrep code scanning are enabled with zero open alerts at closure. The administrator
exemption is retained because this personal repository has only one collaborator.

Residual `FE-01`, `TEST-01`, and `REL-01` acceptance items are intentionally carried
forward: production does not deploy the retained frontend artifact, database tests
still lack deeper concurrency/JSONB breadth, and immutable production deployment
with runtime files outside the checkout remains Phase 4 work. Production was not
changed during Phase 2.

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
8. Move runtime secrets and TLS certificates outside the Git checkout into managed
   mounts. Until then, enforce the documented exact-path allowlist and prohibit
   broad `git clean` operations during deployment.

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

1. Configure and prove production transactional email to close `AUTH-02`.
2. Run approved authenticated synthetic browser/provider smoke and observe CSP
   telemetry for the deployed Phase 1 controls.
3. Harden the host, remove public Reverb exposure, and automate backup/monitoring.
4. Make deployments immutable, deploy the retained frontend artifact, and practice
   application rollback/data restore.
5. Expand PostgreSQL concurrency, JSONB, queue-claim, migration, and browser coverage.
6. Continue incremental architecture and test improvements.

This order may change only with new evidence, active exploitation, an outage, or an
explicit business priority decision. Record the reason rather than silently
reordering high-risk work.
