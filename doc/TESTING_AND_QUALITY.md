# Testing and quality

## Current baseline

At revision `f65945d30c039532cd3109d3a78dfb33eacfa88d` on 20 September
2026:

- 54 PHPUnit tests passed with 390 assertions in 2.94 seconds;
- all audited PHP files passed syntax validation;
- the suite used in-memory SQLite, synchronous queues, array sessions, and array
  mail;
- public page and health probes passed, `/dashboard` redirected when anonymous,
  and an invalid webhook verification request returned `403`;
- the clean frontend build could not be proven because dependency installation was
  interrupted by external registry/network failures;
- no GitHub Actions test/check run gated the revision.

This baseline is evidence for one revision, not a permanent status.

For the Phase 1 private-attachment candidate on 20 September 2026, a fresh
`npm ci --no-audit --no-fund` installed 172 packages and `npm run build`
completed successfully with Vite 8.0.8. This is local evidence, not yet a
reproducible CI gate.

For the Phase 1 stored-XSS candidate on 20 September 2026, the full SQLite suite
passed with 76 tests and 545 assertions. Five focused validation, legacy-data,
view-escaping, and DOM-sink tests passed on PostgreSQL 16 with 54 assertions. A
fresh `npm ci --no-audit --no-fund` installed 172 packages and the Vite 8.0.8
production build passed. Browser smoke on the exact deployed revision remains
required.

For the Phase 1 workspace-authorization candidate on 20 September 2026, the full
SQLite suite passed with 82 tests and 737 assertions. Six focused role-matrix,
route-coverage, ordinary-role, unknown-role, owner, and cross-workspace tests passed
on PostgreSQL 16 with 192 assertions. Production role smoke tests remain required.

For the Phase 1 email-verification candidate on 20 September 2026, the full SQLite
suite passed with 93 tests and 781 assertions. Twenty-five focused registration,
verification, password-reset, profile-email, member-creation, and mail-configuration
tests passed on PostgreSQL 16 with 83 assertions. Production verification/reset
delivery remains blocked until a real mail transport is configured and inbox
delivery is proven.

For the Phase 1 safe owner-deletion candidate on 20 September 2026, the cumulative
SQLite suite passed with 99 tests and 822 assertions. Six focused account-block,
transfer-validation, successful-transfer, explicit-cascade, foreign-workspace, and
multiple-owned-workspace tests passed on PostgreSQL 16 with 41 assertions. A fresh
PostgreSQL migration, one-step rollback of `workspace_audit_events`, and forward
migration all passed. Production deletion and migration smoke tests remain gated on
a fresh verified backup and explicit exact-revision approval.

For the Phase 1 OAuth-token encryption candidate on 20 September 2026, the
cumulative SQLite suite passed with 108 tests and 868 assertions. Eleven focused
encryption, rollback, redaction, Instagram request, and Meta metadata tests passed
on PostgreSQL 16 with 58 assertions. A separate PostgreSQL
forward/rollback/forward drill used synthetic values and proved raw plaintext
matches changed from one to zero after migration, model reads still matched, the
explicit rollback restored one plaintext match, and the second forward migration
passed `oauth-tokens:check-encryption`. No token value appeared in verification
output. Missing-key and unreadable-foreign-ciphertext cases fail closed instead of
double-encrypting data; the former failed transactionally as expected before the
successful run with an explicit non-production test key.

For the Phase 1 browser-header and report-only CSP candidate on 20 September 2026,
six focused tests passed with 43 assertions. They cover the exact headers, absence
of enforced CSP, HTTPS-only staged HSTS, legacy and Reporting API payloads, report
sanitization, route rate limiting, controlled rollback switches, malformed JSON,
and oversized input. The cumulative SQLite suite passed with 114 tests and 911
assertions. A clean
`npm ci --no-audit --no-fund` installed 172 packages and the Vite 8.0.8 production
build passed. Before deployment, a live production recheck showed none of the
tracked headers; deployment and browser/telemetry smoke remained required at that
point.

The exact cumulative Phase 1 revision
`b7e948f4325c5fc318f4ab9f7274319d72b3d32a` was subsequently deployed to
production. A fresh encrypted backup passed checksum and isolated restore checks
before deployment. The production Vite 8.0.8 build passed after a clean install;
both database migrations applied; token verification reported zero unencrypted or
unreadable fields; and the attachment migration had zero eligible records and zero
failures. Repeated `/up`, home, and login probes passed; anonymous dashboard access
redirected; the invalid webhook check returned `403`; a synthetic CSP report
returned `204`; queue depth and historical failed-job count remained stable; Reverb
accepted a local connection; and no new high-severity application log entry appeared
in the observation window. The expected browser headers are now visible on public
application responses and enforced CSP remains absent. Authenticated browser role,
XSS, inbox, attachment/provider, deletion, and real-email delivery journeys were not
run because no approved production synthetic account/provider target was available.

For the Phase 2 CI-foundation candidate
`34e32795a884b39fb7435e7f4c60a6cb6c80c65d` in pull request `#19` on
21 September 2026, all five workflow jobs passed on the exact head. Both the
SQLite and PostgreSQL 16 jobs passed 114 tests with 911 assertions. The workflow
also validated Composer configuration, checked PHP syntax, ran Pint against changed
PHP files, completed a clean Node 24 install and Vite build, retained the frontend
artifact for seven days, scanned full Git history with Gitleaks, and ran the focused
first-party Semgrep policy. Composer and npm audits are deliberately report-only in
this foundation candidate because they still report 50 and 14 advisories
respectively; they are not yet release gates. The candidate does not become a
repository control until it is merged and required by branch protection.

For the Phase 2 Composer-remediation candidate
`f2617c1c43b12521c15a97729bb9fe9201f57fe2` in pull request `#20`, the locked
dependency audit reported zero advisories. The exact head passed the complete suite
on SQLite and PostgreSQL 16, with 114 tests and 911 assertions in each job, plus the
clean frontend build and secret/SAST jobs. This is merge-candidate evidence; the
dependency audit remains non-blocking until the enforcement change lands.

For the Phase 2 npm-remediation candidate
`d184cedf3c3d173bcce913d9ae268fa16c8001d6` in pull request `#21`, the exact
lockfile installed 162 packages under Node 24 from the official npm registry,
reported zero vulnerabilities, and completed the Vite 8.3.0 production build. The
same head passed 114 tests with 911 assertions on both SQLite and PostgreSQL 16 and
passed the secret/SAST job. This is merge-candidate evidence; required enforcement
still depends on the final governance change.

For the Phase 3 off-host backup workflow on 21 September 2026, Bash syntax and
the invalid-destination rejection passed. A live preflight verified the pinned
production host and source availability. Snapshot `20260921T080639Z` passed all
four SHA-256 checks and the script's encrypted-stream readability checks. A
separate, network-isolated PostgreSQL 16 restore passed with 40 public tables,
4 users, 3 workspaces, and 31 messages; tmpfs archive restores passed with
14 storage files, 2 certificate files, and a mode-`600` environment file. The
temporary restore container was removed. This is not a full-service RTO test or
proof of a recurring 24-hour RPO; scheduling, alert delivery, retention, and
periodic restore checks remain unverified.

The Phase 3 read-only freshness checker passed Bash syntax validation and seven
synthetic scenarios on macOS: missing snapshot, healthy snapshot, corrupted
checksum, unexpected checksum inventory, unsafe file mode, missing completion
marker, and stale snapshot. A
separate check against the real encrypted snapshot `20260921T080639Z` passed
without reading plaintext. These checks do not validate any notification
transport or background scheduler, which remain disabled.

## Local verification

Start with the smallest affected check:

```bash
docker compose exec app php artisan test --filter=RelevantTest
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app composer audit
docker compose exec app npm audit
docker compose exec app npm run build
```

For database-sensitive work, run the affected suite against PostgreSQL 16 in an
isolated test database. Never point automated tests at production.

## Required test layers

### Unit

Provider payload mapping, validation helpers, state transitions, formatting, and
other deterministic logic. A unit test does not replace boundary authorization or
database behavior.

### Feature/integration

HTTP routes, policies, validation, database relationships, queue dispatch,
webhook signatures/idempotency, and provider service boundaries. Add PostgreSQL
coverage for JSONB, constraints, migrations, cascade behavior, and concurrency.

### Browser smoke and E2E

Add a small suite for registration/login, workspace settings, inbox read/send,
attachment rejection, social views, and password reset. Use dedicated test accounts
and provider sandboxes. Do not send messages to real customer identities.

### Security regression

Required for upload execution, stored XSS, cross-workspace access, role escalation,
OAuth state/token handling, webhook signature bypass, account deletion, CSRF, and
secret exposure. A scanner result does not replace an application-specific
regression test.

## Scenario gate

Before a behavior change, write a task-scoped scenario matrix. Include applicable:

- success path and reported failure;
- validation and boundary values;
- unauthenticated, unverified, wrong-role, and cross-workspace cases;
- state transitions and deletion/cascade effects;
- dependency/provider failures, timeouts, and malformed responses;
- queue retry, idempotency, duplicate, concurrency, and ordering behavior;
- realtime disconnect/reconnect and persisted-state recovery;
- backward compatibility and deployment mixed-version behavior.

Every applicable scenario must be tested or explicitly recorded as residual risk.

## CI target

Add a pull-request workflow that, at minimum:

1. installs PHP dependencies from `composer.lock`;
2. validates Composer configuration and audits dependencies;
3. runs PHP syntax checks and Pint in check mode;
4. runs PHPUnit on SQLite for fast feedback;
5. runs database-sensitive tests on PostgreSQL 16;
6. installs npm dependencies from `package-lock.json` and builds assets;
7. audits npm dependencies;
8. scans for committed secrets and performs a focused SAST pass;
9. reports every required check to protected `develop` and `main` branches.

Pin action versions and least-privilege workflow permissions. Do not expose deploy
secrets to pull-request code.

The Phase 2 foundation implements this shape in `.github/workflows/ci.yml`. Pull
requests `#20` and `#21` reduced both locked dependency audits to zero findings, and
the enforcement change in pull request `#22` makes both audits blocking, uploads
Semgrep SARIF to GitHub code scanning, schedules a weekly full run, and pins Ubuntu
24.04. Pint is intentionally scoped to PHP files changed against the pull-request
base while the 26-file legacy formatting baseline is handled separately; syntax and
both PHPUnit database jobs still cover the complete current tree. Every named job
and the Semgrep code-scanning result are required on protected `develop` and `main`.

## Release gate

A production release is `NO-GO` unless all applicable evidence is present:

- required CI checks pass on the exact release revision;
- no open critical/high security or dependency finding lacks a documented,
  time-bounded exception;
- migrations pass on PostgreSQL and destructive changes have explicit approval;
- backup and restore evidence exists for data-affecting work;
- a named release approver and operator are available;
- the deploy branch and exact revision are unambiguous;
- a rollback procedure has been rehearsed for the release class;
- the under-five-minute smoke suite passes before and immediately after deploy;
- queue, Reverb, database, error logs, and container health remain normal.

The owner's experimental-project alert opt-out does not itself waive these
release gates for the publicly deployed instance. Do not report unattended
monitoring or a guaranteed backup RPO when neither is operating.

## Production smoke suite

Keep production checks read-only or use explicitly designated synthetic data:

1. `/up` returns healthy and the home page returns `200`.
2. Login page loads; a dedicated synthetic user can authenticate if approved.
3. Dashboard and inbox read operations load for the synthetic workspace.
4. Invalid webhook verification returns `403`; do not submit real provider data.
5. Containers are healthy, queue depth is stable, and no new error fingerprint
   appears after deployment.

Target duration is under five minutes. Any health-check failure, data-integrity
concern, critical security regression, or repeated new error requires rollback
evaluation.

## Coverage gaps to close

- attachment type/execution and public-storage behavior;
- private attachment authorization, signed-provider URL expiry, HTTP Range support,
  and idempotent legacy-file migration;
- authenticated production browser smoke for the tag/department/agent stored-XSS
  remediation;
- workspace roles and cross-tenant object access;
- effective email verification and real mail delivery;
- approved synthetic owner transfer/deletion smoke; backup-linked restore evidence
  exists for the deployed migration;
- provider smoke for deployed OAuth-token encryption and key rotation if exposure
  evidence or an approved rotation plan requires it;
- authenticated CSP report-only telemetry, inline-code reduction, narrowed sources,
  and separately approved enforcement;
- webhook duplicates, retries, ordering, and malformed provider payloads;
- PostgreSQL migration/constraint behavior;
- browser E2E, accessibility, and reliable frontend build;
- backup restore and deployment rollback drills.
