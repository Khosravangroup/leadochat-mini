---
name: leadochat-mini-debugging
description: Diagnose LeadoChat Mini bugs, failed PHPUnit or Vite builds, PostgreSQL issues, Meta integration failures, queue/webhook errors, and realtime problems with evidence before proposing or implementing a fix. Use when behavior is broken, intermittent, or unexplained.
---

# LeadoChat Mini debugging

First use `leadochat-mini-task-workflow`. Diagnosis alone does not authorize edits or
production changes.

## Prove the failure

1. Record expected and actual behavior, exact revision, environment, frequency,
   smallest safe reproduction, and redacted error evidence.
2. Separate source defects from environment failures such as registry/network errors,
   stale caches, missing native npm bindings, provider outages, or configuration.
3. Trace the failing value/action backward through the actual controller, policy,
   service, model, job/event, view, and external boundary that owns it.
4. Compare the nearest working path in current code. Do not use historical handoffs
   as proof of current behavior.
5. State one falsifiable root-cause hypothesis and run the smallest safe experiment
   that distinguishes it from an alternative. Change one variable at a time.
6. For production, prefer read-only, redacted evidence. Never execute an exploit,
   replay customer data, expose secrets, or mutate data without exact authorization.

## Fix only when requested

Build the applicable scenario matrix from `doc/TESTING_AND_QUALITY.md`. Add the
smallest regression test and prove it fails because of the defect, not setup. Change
only the owning boundary, rerun focused and affected checks, then run the required
full/PostgreSQL/build/security checks.

Authentication, workspace authorization, OAuth, webhooks, queues, attachments,
deletion, migrations, and deployment also require
`leadochat-mini-security-remediation`. If evidence requires a protected contract or
architecture change outside the task, stop and request direction.

Report the proven causal chain, fix or recommendation, verification, and residual
risk. A disappearing symptom or green unrelated test is not root-cause evidence.
