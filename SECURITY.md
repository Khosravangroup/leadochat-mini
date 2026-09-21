# Security policy

## Supported code

Security work targets the active `develop` integration line, the `main` release
line, and the production revision explicitly verified at the start of an incident.
`main` must not be assumed to match production until an approved deployment proves
the running revision.

## Repository controls

Both long-lived branches require pull requests, current CI checks, dependency
audits, full-history secret scanning, and focused code scanning. GitHub secret
scanning and push protection plus Dependabot alerts/security updates are enabled.
The owner confirmed on 21 September 2026 that this single-collaborator repository
has no separate human reviewer and does not require one, so the required approval
count is zero. The sole administrator remains exempt from branch protection, but
must verify the same checks and disclose any actual bypass in the pull request
record. This code-review decision does not waive production-release approval.

## Reporting a vulnerability

Report suspected vulnerabilities privately to the repository owner or the
designated security contact. Do not open a public issue with exploit details,
credentials, customer data, access tokens, or production logs. The current
repository does not document a public security mailbox; the owner must designate
one before external disclosure is invited.

Include:

- affected revision and environment;
- the smallest safe reproduction;
- expected and actual behavior;
- impact and required privileges;
- redacted evidence; and
- a suggested containment action, if known.

## Handling rules

- Never test an exploit against production unless the owner explicitly authorizes
  that exact test and its safety controls.
- Never print, copy, or commit `.env` values, OAuth tokens, SSH keys, database
  contents, or customer payloads.
- Preserve webhook signature validation and tenant/workspace boundaries.
- Use a focused regression test before the fix and rerun the affected security
  scenarios after it.
- Rotate a secret only through an approved runbook; code changes alone do not
  invalidate an exposed credential.
- Back up and prove restore capability before a migration that transforms
  encrypted or destructive data.

## Release policy

A release is blocked while any confirmed critical or high vulnerability is open,
while dependency scans report unresolved critical/high advisories without an
accepted exception, or while rollback and backup evidence are missing for a data
change. See [Risk register](doc/RISK_REGISTER.md) and
[Testing and quality](doc/TESTING_AND_QUALITY.md).
