---
name: leadochat-mini-task-workflow
description: Run investigations, implementation, reviews, documentation, and handoffs safely in the LeadoChat Mini GitHub Laravel monolith. Use for every project task to enforce develop-based branches, dirty-worktree preservation, protected boundaries, focused verification, current documentation, and explicit deployment authority.
---

# LeadoChat Mini task workflow

Use this skill for every task in this repository. It is project guidance, not
authorization to publish or operate production.

## Establish context

1. Read `AGENTS.md`, `doc/INDEX.md`, and `.agents/qa-project-context.md`.
2. Read only the current documents relevant to the task. Treat phase and handoff
   files listed as historical in `doc/INDEX.md` as provenance, not instructions.
3. Inspect Git branch, status, upstream, and task-related existing changes. Preserve
   unrelated user work; never stash, reset, clean, rebase, merge, or delete it
   automatically.
4. Work from `develop` on a task-specific `feature/*` or `fix/*` branch. Never edit
   `main` directly. Current branch divergence is a risk to resolve explicitly, not a
   reason to force one branch over the other.
5. Confirm whether the request is investigation, diagnosis, implementation, review,
   release assessment, or deployment. Do not infer write/deploy permission from read
   access or saved credentials.

## Define the change

- State acceptance criteria, affected environment, protected boundaries, callers,
  consumers, data/migration impact, and rollback.
- Create a closed file allowlist and choose the smallest complete change. Do not add
  unrelated refactors, speculative guards, new services, or stack replacements.
- Preserve the Docker modular monolith and PostgreSQL compatibility.
- For a bug or behavior change, use `leadochat-mini-debugging`, establish a scenario
  matrix, and prove a focused failing regression before source implementation when
  feasible.
- For risk-register or security-sensitive work, also use
  `leadochat-mini-security-remediation`.

## Verify

Run the smallest focused check, then expand in proportion to the change:

```bash
docker compose exec app php artisan test --filter=RelevantTest
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app composer audit
docker compose exec app npm audit
docker compose exec app npm run build
```

Database-sensitive work requires PostgreSQL 16 evidence. Frontend behavior requires
a clean build and affected browser scenarios. A command that could not run is an
unverified gap, not a pass. Documentation-only work does not require a fabricated
red test.

Review the final diff for secrets, user changes, unintended files, migration risk,
deployment impact, and updated documentation. Use `doc/TESTING_AND_QUALITY.md` for
release gates.

## Handoff

Report outcome first, files changed, exact tests and results, open risks, migration
or rollback needs, and whether production remained untouched. Do not commit, push,
open/merge a pull request, or deploy unless the active user request authorizes that
action. For deployment readiness, use `leadochat-mini-release-readiness` and require
the exact approved revision.
