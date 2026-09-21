# Contributing

## Before work starts

1. Read `AGENTS.md`, `doc/INDEX.md`, and `.agents/qa-project-context.md`.
2. Select the applicable project-local skill from `.agents/skills`.
3. Confirm the requested outcome, affected environment, authority, and protected
   boundaries.
4. Inspect branch, status, upstream, and task-related existing work. Preserve every
   unrelated user change; never auto-stash, reset, clean, or rewrite it.
5. Reverify volatile facts before relying on an audit or production snapshot.

## Branches

- Never work directly on `main`.
- Use `develop` as the base branch.
- Use `feature/<short-purpose>` for features/docs and `fix/<short-purpose>` for
  defects/security fixes.
- Pull requests to `main` must come from `develop`.
- Do not merge, deploy, force-push, or delete a branch unless the active task
  explicitly authorizes it.
- Promote releases from `develop` to `main` by pull request. Never reconcile the
  branches with reset, rebase, squash, or force-push; merge promotion preserves the
  audited history.
- Both branches require pull requests, current CI/code-scanning checks, and
  resolved conversations. The owner confirmed there is no separate human GitHub
  reviewer and none is required, so the approval count is zero. The sole
  administrator exemption is not permission to bypass the evidence gates; disclose
  any actual bypass in the pull request record.

## Scope and design

- Preserve the Docker-based modular monolith and PostgreSQL compatibility.
- Change only files required by the accepted behavior and its focused tests/docs.
- Do not add a microservice, replace the stack, or create a generic abstraction for
  one current use case.
- Treat authentication, workspace roles, OAuth, webhooks, queues, attachments,
  migrations, realtime channels, and deployment as protected boundaries.
- List callers and consumers before changing a protected contract.

## Behavior changes

Use an evidence-first, test-first cycle:

1. State expected versus actual behavior and the affected revision/environment.
2. Reproduce the defect or define measurable acceptance criteria.
3. Build a scenario matrix including authorization, validation, tenant, dependency,
   idempotency, concurrency, compatibility, and failure cases that apply.
4. Add the smallest regression test and prove it fails for the right reason.
5. Implement the smallest complete change at the owning boundary.
6. Rerun focused, affected, full, PostgreSQL, build, and security checks in proportion
   to the risk.
7. Review the final diff and remove unrelated task-owned changes.

Documentation-only work does not require a fabricated failing test.

## Commit and pull request

- Use clear English Conventional Commit subjects such as `fix: prevent executable
  message attachments`.
- Do not combine dependency upgrades, schema changes, authorization changes, and
  refactors in one commit unless inseparable and explicitly reviewed as such.
- Describe behavior, risk, test evidence, migration/rollback, and documentation in
  the pull request.
- Link the relevant risk ID when remediation closes a registered finding.
- Never include secrets, `.env` values, customer data, tokens, database dumps,
  private keys, or unredacted logs.

### Pull-request verification

The Phase 2 validation workflow reports these checks for pull requests targeting
`develop` or `main`:

- `PHP / SQLite`;
- `PHP / PostgreSQL 16`;
- `Frontend build`;
- `Secret and focused SAST scan`;
- `Dependency audit`.

PHP syntax and the full PHPUnit suite cover the current tree. Pint checks changed
PHP files until the separately tracked legacy formatting baseline is removed.
Composer and npm audits are blocking and the focused Semgrep result is uploaded to
GitHub code scanning. Never bypass a required check, expose deployment secrets to
pull-request code, or treat an uploaded artifact as production-approved by itself.

## Documentation changes

Update the matching source of truth in the same change:

- routes/contracts: `API_AND_ROUTES.md`;
- tables/relationships/retention: `DATABASE_AND_DATA.md`;
- environment keys: `ENVIRONMENT_VARIABLES.md` and `src/.env.example`;
- modules/runtime: `ARCHITECTURE.md`;
- tests/gates: `TESTING_AND_QUALITY.md`;
- deploy/rollback/monitoring: `DEPLOYMENT_AND_OPERATIONS.md`;
- findings/status: `RISK_REGISTER.md` and `REMEDIATION_ROADMAP.md`;
- agent workflow: `CODEX_SKILLS.md`, `.agents/skills`, and `AGENTS.md`.

Do not rewrite historical phase records as if they were current runbooks; keep a
clear historical banner and link to current documentation.

## Definition of done

- acceptance criteria and applicable scenario matrix are satisfied;
- focused and required full checks pass on the final revision;
- PostgreSQL-specific behavior is verified where affected;
- no secret or unrelated user change appears in the diff;
- migration, rollout, monitoring, and rollback are documented where applicable;
- current documentation and risk status are updated;
- production remains unchanged unless a separately authorized deployment completes
  with exact-revision and post-deploy evidence.
