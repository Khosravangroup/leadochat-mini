# Deployment and operations

## Verified production snapshot

Snapshot date: 20 September 2026.

- Public URL: `https://mini.leadochat.com`
- Application directory: `/opt/leadochat`
- Runtime: Docker Compose with `app`, `nginx`, `postgres`, `queue`, and `reverb`
- Verified branch: `develop`
- Verified revision: `e8878e667e37115b07f86b9ddc634b5fabd72a24`
- Container state: all five running with zero restarts at the snapshot time
- Laravel state: production, debug off, configuration/routes/views cached
- Database: PostgreSQL 16
- Queue and sessions: database-backed
- Mail: log driver, which is not production-delivery capable
- Edge: Cloudflare in front of the origin; observed public certificate validity is
  operationally volatile and must be checked live.

Access credentials and SSH keys are intentionally not documented in the repository.
They remain in protected local credential storage on the Mac mini. Reverify host,
user, repository, branch, and revision before every operation.

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

## Branch conflict

Repository policy says feature/fix branches start from `develop`, and production was
running `develop` at the snapshot time. `deploy.sh` defaults to `main`, while
`main` and `develop` are diverged. Until `REL-01` is resolved, every deploy must be
treated as `NO-GO` unless a human approver names the exact branch and commit and
proves it is the intended release.

Do not resolve the divergence with an automatic reset, rebase, or force-push.
Inventory the unique commits, choose the canonical promotion flow, preserve history,
and protect both branches first.

## Pre-deploy checklist

- Confirm approved change set, exact commit, branch, operator, and release approver.
- Confirm required CI checks pass on that exact commit.
- Confirm no critical/high security or dependency blockers remain.
- Back up PostgreSQL and verify the artifact before migrations.
- Review migration forward/rollback behavior on PostgreSQL 16.
- For the `DATA-01` candidate, create and restore-verify a fresh encrypted database
  and upload backup before migrating; do not exercise workspace deletion until the
  exact release revision and destructive test target are explicitly approved.
- Confirm `.env` permissions and required configuration without printing values.
- Run `php artisan app:mail-check`; require a real verification and password-reset
  message to arrive through the configured production provider.
- Record current containers, image IDs, database migration status, queue depth,
  failed jobs, disk space, and recent error fingerprints.
- Identify the previous known-good revision and rehearse the appropriate rollback.
- Avoid an unattended or low-support deployment window.

## Post-deploy verification

Within five minutes:

```bash
curl --fail --silent --show-error https://mini.leadochat.com/up
curl --fail --silent --show-error --output /dev/null https://mini.leadochat.com/
```

Also verify:

- deployed branch and SHA match the approved release;
- all five containers are healthy/running without unexpected restarts;
- PostgreSQL accepts connections and migration status is expected;
- queue depth is stable and no new failed jobs appear;
- Reverb accepts expected connections;
- no new production error fingerprint appears;
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

The Phase 0 point-in-time snapshot `20260920T125105Z` is stored encrypted off-host
on the protected Mac mini. It contains PostgreSQL, `storage/app`, and environment
files. Its encryption passphrase remains in macOS Keychain and was never printed,
passed as a command-line argument, or stored beside the artifacts.

Verified evidence:

- SHA-256 checks passed for every encrypted artifact;
- PostgreSQL restored into a temporary isolated PostgreSQL 16 container with no
  published port: 39 tables, 4 users, 3 workspaces, 31 messages, 9 attachments;
- `storage/app` restored into a temporary tmpfs container: 14 files, 7144 KiB;
- environment files restored into a temporary tmpfs container: 5 files;
- temporary restore containers were removed and no plaintext backup was retained;
- isolated data restore completed in under five seconds, but full-service RTO has
  not been tested.

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
backups after encrypted restore-verified capture. The remaining SSH, firewall,
public Reverb, security-header, automated permission-check, and backup-scheduling
items stay open.

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
