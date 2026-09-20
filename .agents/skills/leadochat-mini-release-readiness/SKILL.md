---
name: leadochat-mini-release-readiness
description: Produce an evidence-based GO or NO-GO decision for a LeadoChat Mini revision and prepare or verify its release. Use for release readiness, smoke tests, rollback planning, deployment verification, or questions about whether the current GitHub revision can safely reach production.
---

# LeadoChat Mini release readiness

First use `leadochat-mini-task-workflow`. Read `.agents/qa-project-context.md`,
`doc/TESTING_AND_QUALITY.md`, `doc/DEPLOYMENT_AND_OPERATIONS.md`, and open items in
`doc/RISK_REGISTER.md`. Assessment does not authorize deployment.

## Identify the candidate

Require the exact commit SHA, source branch, intended environment, approved change
set, migration/data impact, release approver, operator, and previous known-good
revision. A moving branch name alone is not a release candidate. Treat the current
`main`/`develop` mismatch as a blocker until explicitly reconciled.

## Evidence gates

Return `NO-GO` when an applicable required item lacks evidence:

- required GitHub checks pass on the exact commit;
- clean Composer/npm installs, PHP tests/lint/Pint, PostgreSQL tests, asset build,
  audits, secret scan, and security regression are green;
- no open critical/high risk or dependency advisory lacks an approved, expiring
  exception;
- backup is fresh and restore/rollback is proven for the change class;
- migrations are reviewed and tested on PostgreSQL 16;
- configuration and secret permissions are validated without printing values;
- monitoring, operator, approver, deploy window, and communication are named;
- a sub-five-minute smoke suite and post-deploy observation plan are ready.

Do not invent missing evidence or call a skipped check passed.

## Rollback and verification

Define rollback triggers before deployment: three consecutive health failures, data
integrity or cross-workspace exposure, critical security regression, widespread auth
failure, stopped queue, or sustained new server errors require immediate evaluation.
Separate application rollback from database recovery; never blindly reverse an
irreversible migration.

If deployment is explicitly authorized, verify the running SHA, containers,
PostgreSQL/migrations, queue, Reverb, health/public pages, error fingerprints, and
safe smoke journeys within five minutes. Monitor for at least the agreed observation
window. Report final `GO`, `NO-GO`, or `ROLLED BACK` with evidence and unresolved
risk.
