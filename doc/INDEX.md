# Documentation index

Last reviewed: 20 September 2026.

## Current sources of truth

| Document | Purpose | Update when |
| --- | --- | --- |
| [`README.md`](../README.md) | Project entry point and quick start | Product scope or first-run flow changes |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Runtime, modules, and protected boundaries | A module, integration, or runtime boundary changes |
| [`API_AND_ROUTES.md`](API_AND_ROUTES.md) | HTTP surfaces and authorization expectations | A route or external contract changes |
| [`DATABASE_AND_DATA.md`](DATABASE_AND_DATA.md) | Data domains, ownership, migrations, and backup rules | A table, relationship, retention, or migration changes |
| [`ENVIRONMENT_VARIABLES.md`](ENVIRONMENT_VARIABLES.md) | Environment-variable contract without values | Configuration keys or environment requirements change |
| [`LOCAL_DEVELOPMENT.md`](LOCAL_DEVELOPMENT.md) | Reproducible local setup | Docker, setup, or local ports change |
| [`TESTING_AND_QUALITY.md`](TESTING_AND_QUALITY.md) | Test commands and release gates | Test tooling or quality gates change |
| [`DEPLOYMENT_AND_OPERATIONS.md`](DEPLOYMENT_AND_OPERATIONS.md) | Current deployment and operational runbook | Deploy, rollback, backup, or monitoring changes |
| [`SECURITY_HEADERS_AND_CSP.md`](SECURITY_HEADERS_AND_CSP.md) | Browser-header policy, CSP telemetry, privacy, rollout, and rollback | Header directives, external origins, report handling, or CSP rollout changes |
| [`RISK_REGISTER.md`](RISK_REGISTER.md) | Evidence-backed open findings | A risk is discovered, accepted, mitigated, or closed |
| [`REMEDIATION_ROADMAP.md`](REMEDIATION_ROADMAP.md) | Ordered remediation phases and acceptance gates | Scope, order, ownership, or phase status changes |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Branching, review, tests, and documentation policy | Team workflow changes |
| [`CODEX_SKILLS.md`](CODEX_SKILLS.md) | Project-local agent skills and routing | A skill or agent workflow changes |
| [`SECURITY.md`](../SECURITY.md) | Private reporting and security handling policy | Security contact or release policy changes |
| [QA project context](../.agents/qa-project-context.md) | Stack, environment, risks, and measurable quality goals | Any audited baseline changes |

## Historical records

The following files record implementation history or earlier handoffs. They are
kept for provenance and are not current operational sources of truth:

- [`Leadochat_Mini_Phases_1_to_4_Documentation.md`](Leadochat_Mini_Phases_1_to_4_Documentation.md)
- [`Leadochat_Mini_Phases_5_and_6_Documentation.md`](Leadochat_Mini_Phases_5_and_6_Documentation.md)
- [`Leadochat_Mini_Phase_7_Documentation.md`](Leadochat_Mini_Phase_7_Documentation.md)
- [`cursor_handoff_leadochat_inbox_settings_tags.md`](cursor_handoff_leadochat_inbox_settings_tags.md)
- [`social-instagram-architecture-readme.md`](social-instagram-architecture-readme.md), a product design reference whose implementation status must be verified in current code.

Historical claims such as "local-ready", "staging-ready", or "currently broken"
must not be treated as current without re-verification.

## Documentation rules

- Never add credentials, tokens, secret values, private keys, customer data, or
  unredacted production logs.
- Prefer links to one authoritative section over duplicating facts.
- Date environment and audit snapshots because they become stale.
- Update documentation in the same pull request as behavior, schema, configuration,
  or operational changes.
- Record uncertain ownership as unknown; do not invent people, approvals, or SLAs.
