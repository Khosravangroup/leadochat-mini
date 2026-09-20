# Codex skills

Project-local skills live under `.agents/skills`. They route future Codex work to
the current documentation and project constraints. A skill narrows a workflow; it
does not grant permission to deploy, modify production, expose secrets, change a
protected contract, or publish code.

## Applied project-local skills

| Skill | Use when | Key behavior |
| --- | --- | --- |
| `leadochat-mini-task-workflow` | Any investigation, implementation, review, documentation, or handoff | Reads current sources, protects user work, scopes the diff, applies branch/test/documentation gates |
| `leadochat-mini-debugging` | A bug, failed test/build, integration error, or intermittent behavior is reported | Produces a falsifiable root cause before proposing a fix |
| `leadochat-mini-security-remediation` | Working on an item from the risk register or another security-sensitive change | Handles one finding at a time with containment, regression, secret-safe evidence, and rollback gates |
| `leadochat-mini-release-readiness` | Asked whether a revision can deploy, or to prepare/verify a release | Produces an evidence-based `GO`/`NO-GO`; never treats a green local suite as deploy authority |

## Codex skill review

| Source skill | Decision | Reason |
| --- | --- | --- |
| `skill-creator` | Used | Defined concise, triggerable, self-contained project skills and validation rules |
| `qa-project-context` | Used | Created `.agents/qa-project-context.md` as the single stack/environment/quality source |
| LeadoChat `systematic-debugging` | Adapted | Its evidence-first diagnosis fits; GitLab/Linear and multi-repository assumptions were removed |
| LeadoChat `test-driven-development` | Integrated into workflow/debugging | Red-green-regression discipline fits; project-specific publication dependencies were removed |
| generic `release-readiness` | Adapted | Go/no-go, smoke, backup, rollback, and exact-revision evidence are required, sized for the current single-host deployment |
| `leadochat-task-workflow` | Not copied verbatim | It is designed for GitLab, Linear, development-only multi-repository work, worktrees, and distinct backend/web/mobile publication rules that do not match this GitHub monolith |
| `leadochat-laravel-review` | Not copied | It is a report-only Linear/GitLab card reviewer with permissions and status transitions absent here |
| `security-testing` and Codex Security skills | Available by phase | Use for scoped security test design or a fresh repository scan; do not install a full DAST/SAST stack before Phase 2 scope and CI ownership are approved |

## Routing

1. Always start with `leadochat-mini-task-workflow`.
2. Add `leadochat-mini-debugging` for a defect or unexplained failure.
3. Add `leadochat-mini-security-remediation` for a registered security/operations
   risk or a change to authentication, OAuth, webhook, upload, authorization,
   secrets, deletion, or deployment safety.
4. Add `leadochat-mini-release-readiness` only for release planning or a go/no-go
   decision.
5. Documentation-only work uses the task workflow but does not manufacture a TDD
   failure.

## Maintenance

- Keep skill facts in linked source documents rather than duplicating volatile
  versions, counts, host state, or risk status.
- Validate every `SKILL.md` with the local `skill-creator` validator after editing.
- Update `AGENTS.md` routing when adding/removing a skill.
- Do not import another project's skill unchanged. Review its environment,
  integrations, permissions, branching, and publication assumptions first.
