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
