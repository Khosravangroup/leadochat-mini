---
name: leadochat-mini-security-remediation
description: Plan, implement, or verify one LeadoChat Mini risk-register finding involving uploads, XSS, roles, authentication, OAuth tokens, webhooks, deletion, dependencies, secrets, host exposure, backups, or deployment safety. Use for security-sensitive remediation and keep production testing non-exploitative unless exactly authorized.
---

# LeadoChat Mini security remediation

Use after `leadochat-mini-task-workflow`. Read `SECURITY.md`,
`doc/RISK_REGISTER.md`, and the applicable phase in `doc/REMEDIATION_ROADMAP.md`.
Handle one finding or one inseparable control family per task.

## Revalidate and contain

1. Reverify the finding on the exact branch/revision and identify prerequisites,
   privileges, affected data, blast radius, and existing compensating controls.
2. Do not demonstrate impact on production. Use static evidence and isolated tests;
   any live security test needs explicit scope and safety controls.
3. If exposure is active, propose the smallest reversible containment first. Saved
   server/GitHub access is not permission to change firewall, secrets, data, or
   deployment.
4. For data or secret transformations, require a verified backup, rollback/forward-
   fix plan, key owner, rotation sequence, and log-redaction check before mutation.

## Implement safely

- Define abuse, authorization, tenant, boundary, malformed-input, retry/idempotency,
  compatibility, and rollback scenarios that apply.
- Add a focused test that fails before the fix and cannot cause harm.
- Fix the owning boundary and use defense in depth only where each layer has a proven
  purpose. Do not weaken webhook signatures, CSRF, authentication, or validation to
  obtain green tests.
- Never log or commit `.env` values, tokens, private keys, request signatures,
  customer payloads, database dumps, or unredacted failure jobs.
- Dependency remediation must use reviewable update groups; never accept a blind
  forced major upgrade.

## Close a finding

A finding remains open until its exact acceptance criteria in
`doc/RISK_REGISTER.md` are verified. Record commands/results, PostgreSQL and browser
evidence where applicable, deployment/rotation/restore work still required, and
residual risk. Update the register status only for the verified revision and
environment; a local code fix does not prove production remediation.
