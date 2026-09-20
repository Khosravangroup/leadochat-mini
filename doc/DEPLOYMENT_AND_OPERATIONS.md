# Deployment and operations

## Verified production snapshot

Snapshot date: 20 September 2026.

- Public URL: `https://mini.leadochat.com`
- Application directory: `/opt/leadochat`
- Runtime: Docker Compose with `app`, `nginx`, `postgres`, `queue`, and `reverb`
- Verified branch: `develop`
- Verified revision: `b7e948f4325c5fc318f4ab9f7274319d72b3d32a`
- Container state: all five running; PostgreSQL healthy at the snapshot time
- Laravel state: production, debug off, configuration/routes/views cached
- Database: PostgreSQL 16
- Queue and sessions: database-backed
- Mail: log driver, which is not production-delivery capable
- Edge: Cloudflare in front of the origin; observed public certificate validity is
  operationally volatile and must be checked live.

Access credentials and SSH keys are intentionally not documented in the repository.
They remain in protected local credential storage on the Mac mini. Reverify host,
user, repository, branch, and revision before every operation.

## Phase 1 production release

The repository owner approved the exact cumulative Phase 1 revision
`b7e948f4325c5fc318f4ab9f7274319d72b3d32a` on 20 September 2026. It was deployed
from `develop` over the previous known-good revision
`e8878e667e37115b07f86b9ddc634b5fabd72a24`. The release preserved the active
`APP_KEY`, `.env`, and TLS files, and left the production checkout with only the two
documented runtime-managed paths untracked.

Pre-deploy recovery evidence used the encrypted off-host snapshot
`20260920T152300Z`. Every encrypted artifact passed SHA-256 verification. PostgreSQL
16, uploaded files, and runtime configuration were restored in isolated temporary
containers with no published ports, and no plaintext backup artifact remained.

Release execution and verification evidence:

- a clean production `npm ci --no-audit --no-fund` installed 174 packages and the
  Vite 8.0.8 production build passed;
- application, queue, and Reverb images built successfully, and Composer install
  completed;
- `workspace_audit_events` creation and OAuth-token encryption migrations applied;
- token verification reported one row, one checked field, and zero unencrypted or
  unreadable values without printing credentials;
- the private-attachment migration found zero eligible records and reported zero
  failures;
- all five containers ran after release, PostgreSQL was healthy, Reverb accepted a
  local connection, queue depth remained zero, and the two historical failed jobs
  did not increase;
- repeated `/up` probes, the home page, and `/login` returned `200`; anonymous
  `/dashboard` redirected; an invalid webhook verification request returned `403`;
- a synthetic safe CSP report returned `204`; all Phase 1 application security
  headers were present, while enforced CSP remained absent as designed;
- no new `ERROR`, `CRITICAL`, `ALERT`, or `EMERGENCY` application entry appeared in
  the release observation window.

Rollback was not triggered because the defined health gates passed. This evidence
does not close the production mail or authenticated browser/provider smoke gaps.
The owner explicitly accepted the known one-time release risk that production still
uses `MAIL_MAILER=log`; `AUTH-02` remains open until real delivery is configured and
proven.

## Current deployment flow

The manual GitHub Actions workflow connects to the server over SSH and runs
`/opt/leadochat/deploy.sh`. The script currently:

1. fetches Git and force-resets the selected branch;
2. installs npm dependencies and builds Vite assets on the host;
3. builds application, queue, and Reverb images;
4. starts PostgreSQL, then application services;
5. installs Composer dependencies inside the application container;
6. fixes Laravel writable-directory permissions;
7. generates an app key if absent;
8. runs forced migrations;
9. rebuilds Laravel caches and creates the storage symlink.

This flow is not yet a safe release system. It has no automated pre-deploy tests,
database backup gate, immutable artifact, maintenance/zero-downtime strategy,
post-deploy smoke gate, or automated rollback. It also installs development
Composer dependencies because `--no-dev` is absent.

## Branch promotion and remaining deployment risk

Feature/fix branches start from `develop`; reviewed release promotion goes from
`develop` to `main`. Pull request `#29` reconciled the earlier histories with a merge
commit, without reset, rebase, squash, or force-push. At that promotion point the
branch trees were identical and every `develop` commit was contained by `main`.
Both branches are protected by review and required checks.

Production still runs the older verified Phase 1 revision from `develop`, while
`deploy.sh` defaults to `main`. Phase 2 did not deploy. Until Phase 4 makes deployment
immutable and approved by exact revision, every deploy remains `NO-GO` unless a
human approver names the exact `main` commit, proves it is the intended release, and
satisfies the backup, runtime-file, migration, smoke, and rollback gates below.

## Runtime-managed files in the production checkout

The read-only Phase 1 inventory found exactly two untracked runtime paths in
`/opt/leadochat`: `.env` and `docker/nginx/certs/`. These paths contain sensitive
configuration and TLS material and are intentionally outside version control. They
are production state, not disposable build output.

Until runtime configuration and certificates are mounted from managed storage
outside the checkout, every deployment must preserve and verify these exact paths.
Never run `git clean`, add or commit either path, print their contents, include them
in a plaintext artifact, or overwrite them from the repository. A pre-deploy
`git status --short` result containing any other changed or untracked path is a
`NO-GO` until the operator identifies and resolves it safely. The allowed paths must
still pass secret-safe backup, ownership, permission, and certificate-validity
checks; their presence on the allowlist is not proof that their contents are valid.

## Pre-deploy checklist

- Confirm approved change set, exact commit, branch, operator, and release approver.
- Confirm required CI checks pass on that exact commit.
- Confirm no critical/high security or dependency blockers remain.
- Capture `git status --short` without file contents. Require the production
  checkout to be clean except for the runtime-managed `.env` and
  `docker/nginx/certs/` paths described above; never use `git clean` to satisfy this
  gate.
- Back up PostgreSQL and verify the artifact before migrations.
- Review migration forward/rollback behavior on PostgreSQL 16.
- For any future `DATA-01`-related change, create and restore-verify a fresh encrypted
  database
  and upload backup before migrating; do not exercise workspace deletion until the
  exact release revision and destructive test target are explicitly approved.
- For any future `DATA-02`-related change, confirm the current `APP_KEY` is configured
  and will
  be preserved, inventory only token row/null counts, and create a fresh
  restore-verified encrypted database backup. Never print token columns.
- Confirm `.env` permissions and required configuration without printing values.
- Confirm runtime-managed TLS files remain present, protected, and valid without
  printing private-key material.
- Run `php artisan app:mail-check`; require a real verification and password-reset
  message to arrive through the configured production provider.
- Record current containers, image IDs, database migration status, queue depth,
  failed jobs, disk space, and recent error fingerprints.
- Identify the previous known-good revision and rehearse the appropriate rollback.
- Avoid an unattended or low-support deployment window.

Immediately after the `DATA-02` migration, run:

```bash
php artisan oauth-tokens:check-encryption
```

Require a zero `unencrypted_or_unreadable` count, then perform an approved provider
connection smoke test. If the application revision must be rolled back past the
encrypted-cast change, roll back the token migration before starting the old code.
That compatibility action restores plaintext and must be treated as temporary
containment; prefer a forward fix and re-encrypt as soon as possible.

## Post-deploy verification

Within five minutes:

```bash
curl --fail --silent --show-error https://mini.leadochat.com/up
curl --fail --silent --show-error --output /dev/null https://mini.leadochat.com/
curl --fail --silent --show-error --dump-header - --output /dev/null https://mini.leadochat.com/
```

Also verify:

- deployed branch and SHA match the approved release;
- all five containers are healthy/running without unexpected restarts;
- PostgreSQL accepts connections and migration status is expected;
- queue depth is stable and no new failed jobs appear;
- Reverb accepts expected connections;
- no new production error fingerprint appears;
- report-only CSP, reporting endpoint, HSTS, frame, MIME, referrer, and permissions
  headers match `SECURITY_HEADERS_AND_CSP.md`, while enforced CSP remains absent;
- CSP telemetry is normalized and bounded, and key browser journeys have no
  unexplained violation or compatibility regression;
- the safe smoke journeys in `TESTING_AND_QUALITY.md` pass.

## Rollback policy

Rollback criteria must be selected before deployment. Immediate rollback evaluation
is required for three consecutive health failures, evidence of data corruption or
cross-workspace exposure, a critical security regression, widespread authentication
failure, a stopped queue, or sustained new server errors.

The current deployment builds mutable artifacts in place and runs migrations before
a proven rollback mechanism, so rollback is not yet trustworthy. Phase 4 of the
remediation roadmap must introduce immutable/reproducible release artifacts or an
equivalent versioned deployment, a known-good application rollback, and a separate
data recovery path. Never blindly roll back an irreversible migration.

## Backup and recovery

The Phase 0 point-in-time snapshot `20260920T125105Z` and the Phase 1 pre-deploy
snapshot `20260920T152300Z` are stored encrypted off-host on the protected Mac mini.
They contain PostgreSQL, `storage/app`, and protected runtime configuration. Their
encryption passphrase remains in macOS Keychain and was never printed, passed as a
command-line argument, or stored beside the artifacts.

Verified evidence:

- SHA-256 checks passed for every encrypted artifact;
- PostgreSQL restored into a temporary isolated PostgreSQL 16 container with no
  published port: 39 tables, 4 users, 3 workspaces, 31 messages, 9 attachments;
- `storage/app` restored into a temporary tmpfs container: 14 files, 7144 KiB;
- environment files restored into a temporary tmpfs container: 5 files;
- temporary restore containers were removed and no plaintext backup was retained;
- isolated data restore completed in under five seconds, but full-service RTO has
  not been tested.

The Phase 1 snapshot independently restored 39 tables, 4 users, 3 workspaces, 31
messages, 9 attachments, and 1 OAuth-token row into PostgreSQL 16. It also restored
14 uploaded files totaling 7144 KiB and the protected runtime configuration/TLS
set. All temporary restore containers were removed, artifact permissions were
`600`, and zero plaintext backup files remained. This second point-in-time proof is
still not an automated backup schedule or a full-service RTO test.

This snapshot is not a backup schedule. Before any production data migration:

- create an encrypted PostgreSQL backup outside the application host;
- capture required uploaded files and configuration through a secret-safe process;
- verify checksums and readability;
- test restoration into an isolated environment;
- record RPO, RTO, operator, and evidence.

Workspace deletion has no application-level undo. The supported recovery path is
an isolated restore from a verified backup, followed by an explicitly reviewed
selective/full recovery plan. Never test the deletion workflow against a real
customer workspace; use an approved synthetic workspace and record its exact ID and
slug before the operation.

Target RPO is 24 hours and target RTO is 4 hours until the owner defines stricter
requirements. Phase 3 must automate encrypted capture, retention, freshness alerts,
and quarterly isolated restores.

## Host hardening backlog

The initial audit found inactive UFW and fail2ban services, SSH password
authentication enabled, root key login permitted, and Reverb port `8081` bound
publicly. It also found no Nginx upload-execution block, missing HSTS/CSP/frame
controls, and environment copies readable as mode `644`.

Phase 0 added and loaded the Nginx PHP-family deny beneath `/storage/`, changed the
active environment file to mode `600`, and removed four plaintext environment
backups after encrypted restore-verified capture. Phase 1 deployed
application-generated browser headers and privacy-bounded report-only CSP
telemetry. The public application response and synthetic CSP receiver smoke passed;
authenticated browser telemetry and Nginx static/error responses remain open. The
remaining SSH, firewall, public Reverb, origin-level header, automated
permission-check, and backup-scheduling items stay open.

The Phase 1 release also exposed an operational weakness in the current script:
image builds install and compile a large dependency set, and recursive permission
changes can alter tracked `.gitignore` modes. The observed mode-only drift was
corrected and content was unchanged. Phase 4 must replace broad recursive changes
with targeted runtime-directory permissions and a reproducible artifact flow.

Remediation must preserve verified access and Cloudflare/origin traffic. Apply and
test controls incrementally with a second session available; never lock out the only
administrative path. Close public `8081` after proving Nginx/WebSocket proxy behavior.

## Routine operations

At least daily or through monitoring:

- health and TLS checks;
- container restart/OOM state, CPU, memory, and disk;
- queue depth, failed jobs, and oldest pending job;
- application errors grouped by fingerprint;
- PostgreSQL availability, storage growth, and backup freshness;
- webhook received/processed/failed rates and age;
- Reverb connection and error rates.

Document alert destinations and on-call ownership before enabling unattended alerts.
