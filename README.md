# LeadoChat Mini

LeadoChat Mini is a Docker-based Laravel modular monolith for shared social inbox,
Instagram messaging and social management, workspace settings, and Meta commerce
workflows. The application is deployed at [mini.leadochat.com](https://mini.leadochat.com).

## Current stack

- PHP 8.4 and Laravel 13
- Blade, Alpine.js, Tailwind CSS, and Vite
- Filament 4
- PostgreSQL 16
- Laravel Reverb and a database-backed queue
- Nginx and Docker Compose

The authoritative stack, environment, test, and risk snapshot is maintained in
[the QA project context](.agents/qa-project-context.md).

## Repository layout

| Path | Purpose |
| --- | --- |
| `src/` | Laravel application, tests, frontend assets, and migrations |
| `docker/` | PHP-FPM and Nginx definitions |
| `docker-compose.yml` | Local development topology |
| `docker-compose.prod.yml` | Current production topology |
| `.github/workflows/` | GitHub Actions workflows |
| `.agents/skills/` | Project-local Codex workflows |
| `doc/` | Current and historical project documentation |

## Start locally

Create the application environment before starting Docker:

```bash
cp src/.env.example src/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm ci
docker compose exec app npm run build
```

The local application is served at `http://localhost:8080`. Reverb is exposed at
`http://localhost:8081`, and PostgreSQL is exposed to the host on port `5433`.
Do not reuse the local database password or local `.env` values in another
environment.

Run the application checks with:

```bash
docker compose exec app composer test
docker compose exec app php artisan route:list
docker compose exec app npm audit
docker compose exec app composer audit
```

See [Local development](doc/LOCAL_DEVELOPMENT.md) for the complete setup and
troubleshooting flow.

## Documentation

Start with the [documentation index](doc/INDEX.md). Important entry points are:

- [Architecture](doc/ARCHITECTURE.md)
- [API and routes](doc/API_AND_ROUTES.md)
- [Database and data](doc/DATABASE_AND_DATA.md)
- [Environment variables](doc/ENVIRONMENT_VARIABLES.md)
- [Testing and quality](doc/TESTING_AND_QUALITY.md)
- [Deployment and operations](doc/DEPLOYMENT_AND_OPERATIONS.md)
- [Risk register](doc/RISK_REGISTER.md)
- [Phased remediation roadmap](doc/REMEDIATION_ROADMAP.md)
- [Codex skills](doc/CODEX_SKILLS.md)

## Development policy

- Never work directly on `main`.
- Start feature and fix branches from `develop`.
- Preserve the modular-monolith architecture and PostgreSQL compatibility.
- Treat authentication, billing, webhooks, queues, and deployment as protected
  boundaries that require explicit task scope.
- Do not commit secrets, `.env` files, tokens, private keys, production data, or
  unredacted logs.
- Use focused tests and keep changes minimal and reversible.

Read [Contributing](doc/CONTRIBUTING.md), [Security](SECURITY.md), and
[`AGENTS.md`](AGENTS.md) before making changes.

## Known release status

The application is running, and the audited PHP test suite passed on 20 September
2026. It is **not currently release-ready**: critical and high security,
dependency, authorization, backup, CI, and infrastructure gaps are open. Do not
treat a green local test run as deployment approval. Use the
[remediation roadmap](doc/REMEDIATION_ROADMAP.md) and close the release gates in
[Testing and quality](doc/TESTING_AND_QUALITY.md) first.
