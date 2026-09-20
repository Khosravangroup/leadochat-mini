# QA Project Context

Last verified: 21 September 2026. This file is the source of truth for project
stack, environments, quality goals, and test policy. Reverify volatile production
facts before relying on them.

## 1. Product Overview

LeadoChat Mini is a workspace-based social customer-engagement application. Its
current product surface includes public marketing and policy pages, account
authentication, workspace settings, team membership, a shared Instagram inbox,
Instagram posts/comments/stories, Meta OAuth and webhooks, realtime updates, and
catalog/commerce administration.

Critical user journeys are:

1. A visitor registers, verifies the account, signs in, and reaches the dashboard.
2. A workspace owner configures the workspace, team, departments, and tags.
3. An authorized member connects an Instagram business account through OAuth.
4. Meta sends a signed webhook which is stored, queued, processed idempotently,
   and reflected in the relevant workspace.
5. An agent opens a conversation, sends or receives a message, handles an
   attachment, assigns an agent/department/tag, and sees a realtime update.
6. A social manager reviews posts, comments, and stories and performs an allowed
   publish, reply, moderation, or delete action.
7. A commerce manager configures a catalog, products, product sets, collections,
   orders, promotions, and Meta synchronization.
8. A user resets a forgotten password and an owner safely deletes or transfers an
   account without unintended workspace data loss.

## 2. Tech Stack

- Backend: PHP 8.4, Laravel 13, Eloquent, Laravel Breeze, Filament 4.
- Frontend: Blade, Alpine.js, Tailwind CSS 3/4 tooling, Vite 8, Axios, Laravel Echo.
- Data: PostgreSQL 16 in production; JSONB is used for provider/webhook metadata.
- Async and realtime: database queue worker and Laravel Reverb.
- Runtime: Docker Compose with `app`, `nginx`, `postgres`, `queue`, and `reverb`.
- Web server: Nginx with PHP-FPM.
- External integration: Meta/Instagram Graph API, OAuth, webhooks, and commerce APIs.
- Architecture: modular monolith. Microservices are out of scope unless explicitly
  approved.

## 3. Test Stack

- PHPUnit 12 through Laravel's `php artisan test` runner.
- Feature and unit suites under `src/tests`.
- Current automated tests use in-memory SQLite, synchronous queues, array sessions,
  and array mail.
- Latest cumulative Phase 1 baseline: 114 tests and 911 assertions, all passing.
- PHP syntax validation passed for all PHP files in the audited revision.
- No committed browser E2E, accessibility, visual-regression, or load suite is
  present. The Phase 2 CI workflow runs the complete existing suite against both
  SQLite and PostgreSQL 16, but still lacks focused concurrency and JSONB breadth.
- Clean Vite 8.0.8 builds passed locally for Phase 1 and again during the exact
  production release. The Phase 2 CI workflow also passes a clean Node 24 build
  and retained its artifact for seven days; it is not yet a protected-branch gate
  or deployment input.

## 4. CI/CD

- GitHub Actions contains the manual `Deploy Production` workflow. Phase 2 pull
  request `#19` added validation for PHP syntax, changed-file Pint, full SQLite and
  PostgreSQL 16 tests, a clean frontend build, dependency audits, full-history
  secret scanning, and focused SAST.
- Composer and npm remediation are merged with zero locked audit findings. Pull
  request `#22` made the audits blocking, schedules weekly runs, pins Ubuntu 24.04,
  and uploads Semgrep SARIF to GitHub code scanning.
- `develop` and `main` are protected. They require one approval, last-pusher
  separation, stale-review dismissal, resolved conversations, current branch state,
  and the six CI/code-scanning checks. Force-push and deletion are disabled. The
  sole administrator is exempt to prevent a single-collaborator deadlock.
- Dependabot alerts/security updates, secret scanning/push protection, weekly
  Gitleaks/dependency scans, and Semgrep code scanning are enabled. Each GitHub
  security alert surface reported zero open alerts at Phase 2 closure.
- Production was verified on `develop` at revision
  `b7e948f4325c5fc318f4ab9f7274319d72b3d32a`, while `deploy.sh` defaults to
  `main`. Branch and deployment policy therefore conflict.
- Pull request `#29` reconciled the audited branch histories without rewriting them.
  At promotion, the trees were identical and `develop` had no commit absent from
  `main`; the four commits unique to `main` were historical/promotion merge commits.
  `develop` is the integration branch and `main` is the release branch.

## 5. Environments

### Local

Docker Compose serves the app on port `8080`, Reverb on `8081`, and PostgreSQL on
host port `5433`. Local values come from `src/.env`; that file is ignored and must
never be committed.

### Test

PHPUnit defaults to in-memory SQLite for fast local feedback. The Phase 2 CI
candidate additionally runs the same complete suite against an isolated PostgreSQL
16 service. Focused JSONB, locking, concurrent/idempotent path, and migration matrix
coverage remains incomplete.

### Staging

No independently verified staging environment or staged-rollout mechanism is
documented.

### Production

The public service is `https://mini.leadochat.com`. The application is deployed as
five Docker containers under `/opt/leadochat` behind Cloudflare. On the audit date,
all containers were running with zero restarts, application debug was off, and
configuration/routes/views were cached. Access credentials remain outside the
repository in the Mac mini's protected local credential stores.

The cumulative Phase 1 release was deployed on 20 September 2026 after encrypted
off-host backup and isolated restore verification. Both data migrations applied,
token encryption verification passed, the attachment migration had no eligible
records, public smoke checks and the CSP receiver passed, and the expected
application security headers are live. Production still uses `MAIL_MAILER=log`, and
authenticated browser/provider smoke remains pending an approved synthetic target.

## 6. Quality Goals

- 100% of committed PHP tests must pass before review and before deployment.
- 100% of changed PHP files must pass syntax validation and Laravel Pint checks.
- 100% of changed critical journeys must have an automated success test and every
  applicable authorization, validation, and failure scenario.
- 100% of schema changes must be tested against PostgreSQL 16 before deployment.
- Zero open critical or high security findings at release time unless the owner
  records a time-bounded exception with containment and an owner.
- Zero unresolved critical/high dependency advisories at release time unless a
  documented exception proves non-reachability and defines an expiry date.
- The Phase 2 Composer remediation from pull request `#20` is merged, reports zero
  locked Composer advisories, and passed both complete database suites.
- The Phase 2 npm remediation from pull request `#21` is merged, reports zero npm
  vulnerabilities, passed a clean Node 24 install and Vite 8.3.0 build, and passed
  both complete database suites.
- The post-deploy smoke suite must complete in under 5 minutes and cover health,
  authentication, dashboard/inbox read, a safe webhook negative check, and queue
  health.
- The backup target is an RPO of at most 24 hours and an RTO of at most 4 hours;
  these targets remain unproven until a restore drill succeeds.
- Three consecutive failed health checks, any evidence of data corruption, or a
  newly exploitable critical vulnerability blocks promotion and triggers rollback
  evaluation.

## 7. Risk Areas

- Residual authenticated/private attachment delivery smoke and Nginx static/error
  response boundaries; the private-storage implementation and storage execution
  deny are deployed.
- Residual authenticated browser proof for the deployed stored-DOM-XSS remediation.
- Provider connectivity proof and key-rotation planning for deployed encrypted
  OAuth tokens.
- Production role-matrix smoke for the deployed workspace authorization gates.
- Ineffective email verification and a production mail driver that cannot deliver
  password-reset email.
- Cascading owner deletion and workspace data loss.
- Meta webhook idempotency, signature validation, retries, and tenant resolution.
- Dependency advisories across Composer and npm packages.
- PostgreSQL 16 now runs the complete existing suite in the Phase 2 CI workflow,
  but focused JSONB, locking, queue-claim, concurrency, and migration breadth remains
  incomplete.
- Missing automated backup/restore operations, host hardening, Nginx static/error
  security headers, authenticated CSP telemetry evidence, and monitoring. The
  Phase 1 application-header/report-only controls are deployed.
- Branch drift, unprotected branches, and production/deploy branch mismatch.
- Large controllers, jobs, and Blade/JavaScript files with limited focused tests.

The current evidence and remediation mapping are in `doc/RISK_REGISTER.md`.

## 8. Team

The repository does not document team size, release approver, on-call owner,
security contact, data owner, or QA owner. These roles must be assigned before the
first production remediation release. Do not infer a reviewer from the Git author,
issue assignee, or repository owner.

## 9. Conventions

- Branch from `develop` into `feature/*` or `fix/*`; never work directly on `main`.
- Pull requests to `main` must come from `develop`.
- Use focused, reversible changes and preserve the modular monolith.
- Do not change authentication, billing, webhook, queue, or deployment behavior
  unless the active task explicitly authorizes that boundary.
- Preserve PostgreSQL compatibility and existing Docker topology.
- Never commit secrets, runtime `.env` files, private keys, tokens, customer data,
  or unredacted logs.
- For bugs and behavior changes, prove the failure, add a focused regression test,
  implement the smallest fix, and verify affected scenarios.
- Documentation-only changes do not require a fabricated failing test.
- A passing local suite is evidence, not permission to deploy.
