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
build passed. A live production recheck still showed none of the tracked headers;
deployment and browser/telemetry smoke remain required.

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
- deployed browser smoke for the tag/department/agent stored-XSS remediation;
- workspace roles and cross-tenant object access;
- effective email verification and real mail delivery;
- deployed owner transfer/deletion smoke and backup-linked restore evidence;
- OAuth token encryption and key rotation;
- deployed CSP report-only telemetry, inline-code reduction, narrowed sources, and
  separately approved enforcement;
- webhook duplicates, retries, ordering, and malformed provider payloads;
- PostgreSQL migration/constraint behavior;
- browser E2E, accessibility, and reliable frontend build;
- backup restore and deployment rollback drills.
