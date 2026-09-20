# AGENTS.md

## Project architecture
- This project is Docker-based.
- Keep the architecture as a modular monolith unless explicitly requested otherwise.
- Do not introduce microservices.
- Do not replace the current stack without explicit approval.
- Keep backward compatibility where possible.

## Branch policy
- Never work directly on "main".
- Default working branch is "develop".
- New work should go into "feature/*" or "fix/*" branches created from "develop".
- Pull requests to "main" must come from "develop".

## Safety rules
- Do not change authentication, billing, webhook logic, queue logic, or deployment flow unless explicitly requested.
- Do not remove existing environment variables unless explicitly requested.
- Do not perform destructive database changes without clearly marking them.
- Prefer minimal, reversible changes.

## Docker rules
- Respect the existing Docker setup.
- Do not hardcode machine-specific paths.
- Keep production and development configs separate.

## Code change rules
- Change only the files necessary for the requested task.
- Avoid unrelated refactors.
- Keep naming consistent with the existing codebase.
- Preserve PostgreSQL compatibility.

## Review rules
- Flag risky changes.
- Flag destructive migrations.
- Flag changes that can break deployment.
- Flag secrets accidentally added to the repository.

## Current documentation
- Read "doc/INDEX.md" and ".agents/qa-project-context.md" before relying on project state.
- Treat the phase documents and Cursor handoff listed as historical in "doc/INDEX.md" as provenance, not current operating instructions.
- Update the applicable source-of-truth document with route, schema, configuration, architecture, test, deployment, or risk changes.
- Never add credentials, tokens, private keys, customer data, database dumps, or unredacted logs to documentation.

## Project-local skills
- For every project task, use ".agents/skills/leadochat-mini-task-workflow/SKILL.md" first.
- For bugs, failed tests/builds, and unexplained integration behavior, also use ".agents/skills/leadochat-mini-debugging/SKILL.md".
- For risk-register work or changes to uploads, XSS defenses, roles, authentication, OAuth, webhooks, queues, deletion, dependencies, secrets, infrastructure exposure, backups, or deployment safety, also use ".agents/skills/leadochat-mini-security-remediation/SKILL.md".
- For release planning, smoke verification, rollback planning, or a GO/NO-GO decision, also use ".agents/skills/leadochat-mini-release-readiness/SKILL.md".
- A skill does not grant permission to commit, push, open or merge a pull request, deploy, mutate production, rotate secrets, or change server/network configuration.
- Documentation-only work does not require a fabricated failing test.
