# Risk register

Assessment date: 20 September 2026. Revision assessed:
`f65945d30c039532cd3109d3a78dfb33eacfa88d`.
Phase 0 containment revision:
`e8878e667e37115b07f86b9ddc634b5fabd72a24`.
Phase 1 production revision:
`b7e948f4325c5fc318f4ab9f7274319d72b3d32a`.

This is an evidence-backed engineering risk register, not proof that every
potential vulnerability has been exploited. No exploit payload was executed
against production. Revalidate a finding immediately before fixing it because code
and infrastructure may change.

## Severity and status

- **Critical:** plausible compromise or unrecoverable loss; contain immediately.
- **High:** major confidentiality, integrity, availability, or release-control gap.
- **Medium:** meaningful defense, reliability, or maintainability gap.
- **Low:** limited direct impact but worth tracking.
- **Open:** no verified remediation.
- **In progress:** work exists but acceptance evidence is incomplete.
- **Closed:** the acceptance criteria have been verified on the intended revision
  and environment.

## Summary

| ID | Severity | Status | Area | Phase |
| --- | --- | --- | --- | --- |
| `SEC-01` | Critical | In progress | Executable public attachment path | 0-1 |
| `OPS-01` | Critical | In progress | No verified backup/restore | 0, 3 |
| `SEC-02` | High | In progress | Stored DOM XSS sinks | 1 |
| `AUTH-01` | High | In progress | Missing workspace role authorization | 1 |
| `AUTH-02` | High | In progress | Ineffective email verification and mail delivery | 1 |
| `DATA-01` | High | In progress | Owner deletion can cascade workspace data | 1 |
| `DATA-02` | High | In progress | OAuth tokens stored plaintext | 1 |
| `DEP-01` | High | In progress | Composer security advisories | 2 |
| `DEP-02` | High | In progress | npm security advisories | 2 |
| `CI-01` | High | In progress | No CI gate or branch protection | 2 |
| `REL-01` | High | Open | Branch divergence and deploy-target mismatch | 2-4 |
| `OPS-02` | High | In progress | Production environment files readable as `644` | 0, 3 |
| `OPS-03` | High | Open | SSH and host firewall hardening gaps | 3 |
| `FE-01` | Medium | In progress | Clean frontend build not proven | 2 |
| `CI-02` | Medium | In progress | Security scanning disabled/missing | 2 |
| `OPS-04` | Medium | Open | Reverb port publicly bound | 3 |
| `OPS-05` | Medium | In progress | Missing browser security headers | 1, 3 |
| `TEST-01` | Medium | In progress | SQLite-only automated database coverage | 2, 4 |
| `ARC-01` | Medium | Open | Oversized controllers/job and inline scripts | 5 |
| `OBS-01` | Medium | Open | Limited verified monitoring and operational SLOs | 3 |
| `OPS-06` | Low | Open | Historical failed jobs not dispositioned | 3 |
| `DOC-01` | Medium | In progress | Documentation was stale and fragmented | 6 |

## Detailed findings

### `SEC-01` — Executable public attachment path

**Evidence:** `InboxController` validates attachments with only `file` and size,
stores them on the public disk under `message-attachments`, and the public storage
symlink exposes that directory. Production Nginx forwards every matching `.php`
path under the public document root to PHP-FPM. Symfony can derive a `.php`
extension for PHP MIME content.

**Impact:** an authenticated user may be able to upload server-executable PHP and
request it through the public storage path, resulting in remote code execution.

**Immediate containment:** disable general attachments or enforce a narrow server-
side allowlist; add an Nginx deny rule for script extensions beneath storage; do not
rely on client MIME or filename. Verify containment in a non-production environment
before reload.

**Phase 0 update:** containment was merged through pull request `#8` and deployed as
`e8878e667e37115b07f86b9ddc634b5fabd72a24`. Message uploads now require both an
approved detected MIME and approved original extension. The picker advertises the
same finite set. Both Nginx configurations deny PHP-family extensions under
`/storage/`, and the production container loaded that rule after `nginx -t`.
Production inventory found zero legacy PHP-like public uploads. The full local suite
passed with 60 tests and 421 assertions.

**Phase 1 implementation:** new local message attachments use private storage; browser
access requires authentication and owning-workspace membership; Meta receives a
temporary signed URL; signatures are removed from persisted send metadata; and an
idempotent command migrates legacy public message attachments after backup. Local
SQLite coverage passed with 71 tests and 491 assertions, while the attachment
security subset passed on PostgreSQL 16 with 15 tests and 93 assertions. The exact
cumulative revision was deployed after a fresh restore-verified backup. The
production legacy-file migration found zero eligible records and zero failures.

**Residual risk:** an approved authenticated production attachment send/download
and provider-delivery smoke was not available. Keep the finding in progress until
that intended-environment evidence is recorded.

**Close when:** uploads are stored outside executable public paths or served through
a controlled download response; content/extension/MIME allowlists are enforced;
Nginx cannot execute scripts in upload paths; regression tests prove allowed files
work and PHP/polyglot/double-extension cases cannot execute.

### `OPS-01` — No verified backup/restore

**Evidence:** no application/PostgreSQL backup schedule, matching backup artifact,
or restore drill was verified. The only observed system timer was unrelated package
database backup.

**Phase 0 update:** encrypted off-host artifacts were created for PostgreSQL,
uploaded files, and environment files under snapshot `20260920T125105Z`. SHA-256
verification passed. Isolated PostgreSQL 16 restore recovered 39 tables and the
expected record counts; isolated tmpfs restores recovered 14 uploaded files and 5
environment files. No plaintext artifact was retained.

**Phase 1 release update:** a second encrypted off-host snapshot
`20260920T152300Z` passed SHA-256 verification and isolated restores before the data
migrations. It recovered 39 tables, 14 uploaded files, protected runtime
configuration/TLS files, and the expected count-only database inventory. No
plaintext artifact remained.

**Residual risk:** this is a verified point-in-time baseline, not an automated
schedule. Retention, freshness alerting, periodic restore drills, and full-service
RTO evidence remain open in Phase 3.

**Impact:** security/data migrations, operator error, disk loss, or compromise can
cause unrecoverable application data loss.

**Close when:** encrypted off-host PostgreSQL and upload backups meet the documented
RPO; monitoring proves freshness; a clean isolated restore meets the RTO and records
integrity checks and operator evidence.

### `SEC-02` — Stored DOM XSS sinks

**Evidence:** workspace tag and department names are interpolated into `innerHTML`
in `resources/views/settings/index.blade.php`; inbox tag, department, and agent
markup similarly reuses untrusted strings in `resources/views/inbox/index.blade.php`
and `resources/js/pages/inbox-tags.js`.

**Impact:** a stored workspace-controlled value may execute script in another
authenticated user's browser and act with that user's session.

**Phase 1 implementation:** tag, department, agent, and local attachment-preview markup
is now constructed with DOM element creation plus `textContent`, `dataset`, and
direct property/style assignment.
Tag and department colors require an exact six-digit hexadecimal value at every
write endpoint; model accessors normalize valid colors and replace unsafe legacy
values with `#6366f1`. Regression coverage exercises malicious names in settings
and inbox rendering, unsafe/blank input, legacy database values, and the targeted
DOM sinks. The full SQLite suite passed with 76 tests and 545 assertions; the five
focused tests passed on PostgreSQL 16 with 54 assertions; and a fresh Vite 8.0.8
build completed. The exact revision is deployed. Authenticated production browser
smoke remains required before closing this finding.

**Close when:** untrusted values use safe DOM text/attribute assignment, color/style
values are allowlisted, server validation is consistent, and regression tests cover
malicious names across settings and inbox rendering.

### `AUTH-01` — Missing workspace role authorization

**Evidence:** `WorkspaceSettingsController` considers any workspace member able to
manage the team and allows member creation after membership checks. No durable
project policy/gate layer was found for the audited routes.

**Impact:** ordinary members can perform owner/admin actions, create pre-verified
users, or mutate workspace data beyond their role. Other route-bound records may be
susceptible to cross-workspace access if a controller misses a manual check.

**Phase 1 implementation:** a deny-by-default role/capability matrix now backs Laravel
gates for workspace access and owner-only management. All authenticated workspace
routes require membership with a recognized role. Settings, team, labels, catalog,
commerce, connection/OAuth, social publish/moderation, and workspace-tag creation
also require owner management capability. Cross-workspace negative tests cover
inbox, settings, catalog, commerce, and social resource groups; a structural test
asserts middleware coverage for every protected named route. The full SQLite suite
passed with 82 tests and 737 assertions, while the six focused tests passed on
PostgreSQL 16 with 192 assertions. Invitation identity behavior remains part of
`AUTH-02`. The exact revision is deployed; approved production role smoke remains
required before this finding closes.

**Close when:** a documented role/capability matrix exists; Laravel policies/gates
protect every privileged workspace resource; invitation/member creation does not
bypass identity verification; negative tests cover ordinary roles and cross-
workspace IDs for every protected route group.

### `AUTH-02` — Ineffective verification and non-delivering production mail

**Evidence:** routes use `verified`, but `User` does not implement Laravel's
verification contract. Production used the `log` mail driver. Registration creates
an already verified timestamp in the current settings flow.

**Impact:** email ownership is not proven, and password-reset/verification messages
are not delivered to users. Account recovery and identity trust are unreliable.

**Phase 1 implementation:** `User` now implements Laravel's verification contract. New
registrations redirect to verification, owner-created members are no longer marked
verified, registration/member/email-change flows send verification notifications,
and unverified users cannot enter workspace routes. Verification and password-reset
tests cover delivery dispatch, expiry, replay, and rate limits. A secret-safe
`app:mail-check` command rejects non-delivering or incomplete SMTP configuration.
The full SQLite suite passed with 93 tests and 781 assertions; the 25 focused tests
passed on PostgreSQL 16 with 83 assertions.

**Production residual verified 20 September 2026:** the exact Phase 1 implementation
is deployed, but the server still uses `MAIL_MAILER=log` and has no SMTP
username/password. No matching SMTP credential was
found in the Mac mini Keychain. Live DNS has MX, SPF, and DKIM (`x` selector), while
DMARC is monitoring-only (`p=none`). A real provider credential, secret-safe server
configuration, end-to-end delivery checks, and an owner-approved DMARC enforcement
plan are required before closing this finding.

The owner explicitly approved this release with the known temporary non-delivery
risk. That one-time release decision does not satisfy the close criteria.

**Close when:** product requirements define which identities need verification;
`User` and registration/invitation flows enforce it; a real production mail provider
is configured with SPF/DKIM/DMARC as applicable; delivery, expiry, replay, rate-limit,
and reset tests pass without exposing tokens.

### `DATA-01` — Owner deletion cascades workspace data

**Evidence:** profile deletion directly deletes the user, while workspace ownership
uses a cascading foreign key.

**Impact:** an owner can unintentionally delete a workspace and related operational
and customer data through ordinary profile deletion.

**Phase 1 implementation:** profile deletion is blocked while any owned workspace
remains. The profile UI exposes separate transfer and permanent-delete workflows.
Both require the current password; transfer accepts only an existing non-self
member, while deletion additionally requires the exact workspace slug and displays
the cascade/backup impact. Both operations recheck authoritative ownership inside
a database transaction with a row lock, and foreign workspace IDs return `404`.
Transfer preserves the workspace and demotes the former owner to member; explicit
deletion intentionally uses the existing foreign-key cascades. Audit rows survive
workspace or actor deletion so operator evidence remains available.

The cumulative SQLite suite passed with 99 tests and 822 assertions. Six focused
ownership, transfer, authorization, multi-workspace, audit, and cascade tests passed
on PostgreSQL 16 with 41 assertions. A fresh PostgreSQL migration, rollback of the
new audit table, and forward migration all passed. The exact cumulative revision was
deployed after a fresh verified backup, and the audit-table migration applied.
Destructive smoke was intentionally not run against customer data; the finding
remains in progress pending an approved synthetic transfer/deletion journey.

**Close when:** an explicit transfer-or-delete workflow exists; destructive impact
is shown and reconfirmed; sole-owner and multiple-owned-workspace cases are
protected; audit and restore paths are defined; PostgreSQL tests prove cascade
behavior is intentional; and the exact candidate is deployed and smoke-tested after
a verified backup.

### `DATA-02` — OAuth tokens stored plaintext

**Evidence:** `OauthToken` casts dates and booleans but not access/refresh tokens to
encrypted values.

**Impact:** database read access or an exposed backup yields provider credentials.

**Phase 1 implementation:** `OauthToken` now encrypts access and refresh fields at the
model boundary and hides them from serialization. A transactional, idempotent data
migration encrypts existing plaintext rows and supports an explicit compatibility
rollback. The secret-safe `oauth-tokens:check-encryption` command reports only row
and field counts and fails closed on plaintext or unreadable ciphertext. Raw token
queries in runtime consumers were replaced with hydrated model reads. Provider
errors, diagnostics, local-debug responses, and persisted Meta sync metadata now
redact secret-bearing keys and known values while tests prove real outbound requests
still receive the credential.

The cumulative SQLite suite passed with 108 tests and 868 assertions. Eleven focused
encryption, redaction, Instagram, and commerce tests passed on PostgreSQL 16 with
58 assertions. A PostgreSQL forward/rollback/forward drill proved plaintext input
becomes unreadable in the raw column, remains usable through the model, returns to
plaintext on explicit rollback, and re-encrypts successfully. Production inventory
found one token row, no refresh token, a configured `APP_KEY`, and no previous keys;
no token value was printed. No confirmed credential exposure was found, so provider
rotation was not performed. Reassess and rotate immediately if exposure evidence
appears.

**Production verification:** after a fresh restore-verified encrypted backup, the
exact cumulative revision and token migration were deployed with the existing
`APP_KEY` preserved. Count-only verification reported one row, one checked field,
and zero unencrypted or unreadable values. No token value was printed.

**Residual risk:** approved provider connectivity smoke was not performed. Closure
still requires that intended-environment evidence and any rotation required by a
future exposure assessment.

**Close when:** tokens are encrypted at rest with managed keys; existing rows are
migrated safely; reads/writes/refresh continue to work; logs and errors cannot leak
tokens; backup/rollback is proven; provider token rotation is completed when the
incident assessment requires it.

### `DEP-01` — Composer advisories

**Evidence:** `composer audit` on the locked production dependencies reported 50
advisories across 17 packages: 14 high, 31 medium, 3 low, and 2 unspecified. Affected
packages included Laravel, Filament, Livewire, Guzzle, and Symfony components.

**Impact:** known framework or library vulnerabilities may be reachable through
application paths. Severity counts alone do not prove exploitability.

**Phase 2 candidate update:** pull request `#20` refreshes the compatible runtime
dependency graph. It moves Filament from 4.10.0 to 4.13.4, Laravel from 13.4.0 to
13.32.0, and updates the affected Livewire, Guzzle, CommonMark, Symfony, and related
transitive packages. `composer audit --locked` reports zero advisories on exact head
`f2617c1c43b12521c15a97729bb9fe9201f57fe2`. The full SQLite and PostgreSQL 16
suites each passed 114 tests with 911 assertions; frontend build and secret/SAST
jobs also passed. The finding remains in progress until this candidate is merged
and the audit is enforced as a required check.

**Close when:** dependencies are updated in reviewable groups; advisory reachability
is assessed; application/security regression and PostgreSQL tests pass; the final
audit has no unaccepted critical/high advisory; any exception has owner and expiry.

### `DEP-02` — npm advisories

**Evidence:** `npm audit` reported 14 advisories: 2 critical, 8 high, 3 moderate,
and 1 low. Direct ranges included vulnerable Axios, Concurrently, PostCSS, and Vite
versions.

**Impact:** build-chain or browser-side vulnerabilities can affect delivered assets
or developer/CI environments.

**Phase 2 candidate update:** pull request `#21` updates Axios to 1.20.0 and
refreshes the existing-major lock graph, including Concurrently 9.2.4, PostCSS
8.5.28, Vite 8.3.0, Shell Quote 1.9.0, Browserslist 4.29.0, and Nano ID 3.3.19.
On exact head `d184cedf3c3d173bcce913d9ae268fa16c8001d6`, GitHub CI installed 162 packages
from the official registry, reported zero npm vulnerabilities, and completed the
Vite build. Both database jobs passed 114 tests with 911 assertions, and the
secret/SAST job passed. The finding remains in progress until the candidate is
merged and the audit becomes a required blocking check.

**Close when:** direct and transitive packages are updated without unsafe forced
major upgrades; clean lockfile install and Vite build pass; affected UI behavior and
browser smoke tests pass; no unaccepted critical/high advisory remains.

### `CI-01` — No CI gate or branch protection

**Evidence:** the only active GitHub Actions workflow was manual production deploy;
the audited revision had no checks/statuses. `main` and `develop` were unprotected.

**Impact:** untested, unreviewed, or vulnerable code can reach a release branch or
production; history can be overwritten without a repository policy gate.

**Phase 2 candidate update:** pull request `#19` adds a least-privilege workflow.
At exact head `34e32795a884b39fb7435e7f4c60a6cb6c80c65d`, all five jobs passed, including
full SQLite and PostgreSQL 16 suites, frontend build, secret/SAST scanning, and
dependency reporting. Dependency audits remain report-only, the workflow is not yet
merged, and neither protected branch requires it, so the finding remains in
progress.

**Phase 2 enforcement update:** pull request `#22` makes both dependency audits
blocking, schedules weekly validation, pins Ubuntu 24.04, and publishes Semgrep
SARIF. All workflow jobs passed on exact head
`26e9763c4b51d081daa6c1f5c73a21643af75a73`. Branch protection and required-review
policy remain necessary before this finding can close.

**Close when:** protected branches require reviewed pull requests and current CI
checks; force-push/deletion are restricted; least-privilege CI runs PHP, PostgreSQL,
frontend build, lint, dependency, secret, and security checks.

### `REL-01` — Branch divergence and deploy-target mismatch

**Evidence:** production was on `develop`, `deploy.sh` defaults to `main`, and the
branches had three versus four unique commits in the audit comparison. The Phase 1
read-only inventory also found runtime-managed `.env` and
`docker/nginx/certs/` paths untracked inside the production checkout. The current
reset flow preserves untracked files, but a broad clean or checkout replacement can
silently destroy the active configuration or TLS material.

**Impact:** a manual deploy can silently select different code, omit fixes, or
reintroduce behavior. The release source is not reproducible.

**Close when:** unique commits are reconciled without history loss; the canonical
promotion path is documented and enforced; deployment requires an immutable,
approved revision; the running SHA is verified post-deploy; rollback targets the
previous known-good revision; runtime secrets and certificates are mounted from
managed storage outside the source checkout, or the deployment pipeline enforces
and verifies an exact, secret-safe runtime-path allowlist without cleaning it.

### `OPS-02` — Environment file permissions

**Evidence:** production `src/.env`, its root symlink target, and four environment
backup files were mode `644` and owned by root.

**Phase 0 update:** the active environment file is now mode `600` and owned by
`root:root`. The four plaintext `.env.bak-*` files were encrypted off-host,
restore-verified, and securely removed. Follow-up inventory found zero plaintext
copies and zero non-system interactive users. No repository-secret evidence or
confirmed credential access was found, so rotation was not performed.

**Residual risk:** add an automated permission/copy check and reassess rotation if
new access evidence appears. This finding remains in progress until recurrence
controls exist.

**Impact:** any local user/process with host read access can obtain application,
database, provider, and Reverb secrets; extra backups multiply exposure.

**Close when:** only the required service/operator accounts can read secrets,
redundant backups are securely removed after validated recovery capture, permissions
are checked automatically, and exposed credentials are rotated when warranted.

### `OPS-03` — SSH and host firewall hardening gaps

**Evidence:** UFW and fail2ban were inactive; SSH allowed password authentication,
root login by key, and six authentication attempts.

**Impact:** internet-facing management has a broader attack surface and limited
brute-force controls. Saved access is not authorization to change these settings.

**Close when:** an approved non-root administrative path is proven; password login
is disabled if no exception is needed; firewall rules permit only required traffic;
rate-limiting/banning and unattended security updates are monitored; a second
session proves changes do not lock out operators.

### `FE-01` — Clean frontend build not proven

**Evidence:** the existing local dependency tree lacked a native Rolldown binding;
two isolated `npm ci` attempts ended with registry `ECONNRESET`, and offline cache
was incomplete.

**Impact:** the committed lockfile and current source have no reproducible clean-build
evidence. Production assets may drift from source.

**Phase 1 release update:** clean installs and Vite 8.0.8 builds passed for the
candidate and on the exact production revision. The finding remains in progress
because no CI gate retains and deploys an immutable frontend artifact.

**Phase 2 candidate update:** pull request `#19` completed a clean Node 24 install
and Vite build on its exact head and retained `src/public/build` for seven days.
The artifact is not yet a required protected-branch output or the source of a
production deployment, so the close condition is not yet met.

**Close when:** a clean, isolated install from `package-lock.json` and `npm run build`
pass in CI on a supported Node version, with the artifact retained and deployed from
that exact revision.

### `CI-02` — Security scanning disabled or absent

**Evidence:** GitHub secret scanning and Dependabot were disabled, and no code-
scanning analysis existed for the repository.

**Impact:** credential exposure and new vulnerable dependencies can remain unnoticed.

**Phase 2 candidate update:** pull request `#19` passed full-history Gitleaks and a
focused first-party Semgrep policy on exact head
`34e32795a884b39fb7435e7f4c60a6cb6c80c65d`, and adds weekly grouped Dependabot
configuration for Composer, npm, and GitHub Actions. GitHub secret-scanning,
push-protection, dependency-alert, and code-scanning features are not yet enabled,
and scheduled/required enforcement remains incomplete.

**Phase 2 enforcement update:** pull request `#22` passed full-history Gitleaks and
focused Semgrep, then uploaded a zero-result SARIF analysis covering four rules to
GitHub code scanning. The candidate also schedules weekly scans. Repository secret
scanning, push protection, dependency alerts, and protected-branch enforcement
remain to be enabled after merge.

**Close when:** secret scanning, dependency updates/alerts, SAST, and scheduled/full
dependency scans are enabled; findings route to an owner; false-positive exceptions
are reviewed and time-bounded.

### `OPS-04` — Reverb port publicly bound

**Evidence:** production Compose publishes `8081:8081`, while Nginx already proxies
the `/app` WebSocket path. The host was listening publicly on port `8081`.

**Impact:** clients can bypass intended edge/origin controls and reach Reverb
directly.

**Close when:** Reverb is reachable only on the internal Docker network or an
explicitly protected interface; Cloudflare/Nginx WebSocket operation and channel
authorization remain verified.

### `OPS-05` — Missing browser security headers

**Evidence:** production Nginx did not define HSTS, Content Security Policy,
frame-ancestor/frame options, content-type sniff protection, or a referrer policy.

**Impact:** browser defense-in-depth against downgrade, framing, MIME confusion,
and XSS is weaker. A strict CSP can break inline scripts and must be introduced from
measured report-only data.

**Phase 1 implementation:** global Laravel middleware adds `nosniff`, same-origin frame
control, strict-origin referrer handling, a restrictive browser permissions policy,
one-day HSTS only on recognized HTTPS requests, and CSP strictly through
`Content-Security-Policy-Report-Only`. No enforced CSP header is emitted. The
initial policy inventories the current inline-script/style and dynamic media/Reverb
constraints. Both legacy and Reporting API reports route to a public receiver with
rate, body, batch, field, and URL-sanitization controls; query strings, fragments,
user information, script samples, arbitrary keys, cookies, and raw bodies are not
logged.

Six focused tests passed with 43 assertions, covering headers, HTTPS-only HSTS,
absence of enforced CSP, both report formats, CSRF independence, sanitization,
rate-limit declaration, rollback switches, invalid JSON, and oversized bodies. The
cumulative SQLite suite passed with 114 tests and 911 assertions, and a clean Vite
8.0.8 build passed.
The exact Phase 1 revision is now deployed. Public normal and cache-busted responses
returned all expected application headers, enforced CSP remained absent, and a
synthetic safe CSP report returned `204`.

**Residual risk:** the report-only policy retains `unsafe-inline` for scripts and
styles and broad HTTPS media/image plus WebSocket schemes while the existing UI is
measured. Application middleware cannot protect Nginx-served static/error responses.
Authenticated browser journey evidence, telemetry observation, inline-code
reduction, source narrowing, Nginx static/error coverage, and separately approved
enforcement remain open.

**Close when:** headers are deployed with compatibility tests; CSP starts in report-
only mode, observed violations are resolved without unsafe broad allowances, and
the enforced policy covers scripts, frames, connections, and mixed content.

### `TEST-01` — SQLite-only database coverage

**Evidence:** `phpunit.xml` forces SQLite `:memory:` while production uses
PostgreSQL and JSONB.

**Impact:** migrations, constraints, JSON queries, locking, ordering, and
transaction behavior may pass tests but fail in production.

**Phase 2 candidate update:** the complete current suite passed on PostgreSQL 16 and
SQLite in pull request `#19`, with 114 tests and 911 assertions in each job. This
establishes repeatable database-engine coverage for the existing suite, but does
not by itself add the missing JSONB, locking, queue-claim, concurrency, or
idempotency scenarios. The checks are not yet required by branch protection.

**Close when:** CI runs database-sensitive feature/migration tests on PostgreSQL 16,
including ownership cascades, JSONB, queue claims, and concurrent/idempotent paths.

### `ARC-01` — Oversized implementation units

**Evidence:** `SocialController` was about 2,041 lines, `InboxController` about
1,713 lines, and `ProcessInstagramWebhookEvent` about 1,655 lines. Large inline
Blade/JavaScript blocks own multiple UI behaviors.

**Impact:** changes have high regression and review cost; security and tenant checks
are easy to apply inconsistently.

**Close when:** prioritized behavior is covered first, then responsibilities move in
small compatible slices to policies, Form Requests, services/jobs, and focused JS
modules; every slice reduces complexity without unrelated rewrites.

### `OBS-01` — Monitoring and SLO gaps

**Evidence:** container/log health could be inspected manually, but no verified
application error tracker, queue/webhook alerting, SLO dashboard, or named on-call
process was documented.

**Impact:** failures, growing queue lag, provider rejection, disk pressure, or
degraded delivery may remain undetected until user reports.

**Close when:** health, error fingerprints, latency, resource use, database, queue,
webhook lifecycle, Reverb, TLS, backup freshness, and disk alerts have defined
thresholds, owners, test notifications, and an incident runbook.

### `OPS-06` — Historical failed jobs

**Evidence:** two failed jobs dated 20 April 2026 remained in production while no
pending jobs existed.

**Impact:** low current availability risk, but unresolved failure payloads can hide a
product defect or retain sensitive data.

**Close when:** failures are classified, sensitive payload retention is reviewed,
safe retries or dispositions are recorded, and failed-job age/count monitoring is
enabled.

### `DOC-01` — Stale and fragmented documentation

**Evidence:** the application README was the Laravel skeleton; phase documents still
described local/staging readiness after production deployment; an old Cursor handoff
described potentially obsolete broken state; no current runbook or risk register
existed.

**Impact:** maintainers and agents can apply obsolete instructions, select the wrong
environment, or miss known release blockers.

**Close when:** the documentation suite in `doc/INDEX.md` is reviewed and merged,
historical files are clearly labeled, project-local skills route work to current
sources, and documentation updates are required with behavior/configuration changes.

## Verified controls and positive evidence

- Instagram webhook verification and request-signature validation exist; required
  production variables were set at the audit time.
- Invalid webhook verification returned `403`.
- Production debug was off and Laravel configuration/routes/views were cached.
- All five expected containers were running with zero restarts at the snapshot.
- No production `ERROR` log lines were observed for the audit day.
- Public pages, authentication entry points, health endpoint, and anonymous dashboard
  redirect returned expected status classes.
- No common secret pattern was found in the limited current-tree/history scan. This
  is not a substitute for GitHub secret scanning.
- Public TLS was active through Cloudflare.
