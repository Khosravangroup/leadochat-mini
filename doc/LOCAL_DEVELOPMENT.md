# Local development

## Prerequisites

- Docker Desktop or a compatible Docker Engine with Compose v2
- Git
- Network access to Composer and npm registries for the first dependency install

Host-installed PHP, Composer, Node.js, PostgreSQL, and Nginx are optional because
the supported path uses Docker.

## First setup

From the repository root:

```bash
cp src/.env.example src/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm ci
docker compose exec app npm run build
```

The committed example already targets the local Compose service names and ports.
Update only local values in `src/.env`. Configure a Meta development application
only when the task requires OAuth or webhook work. Never copy production values
into the local file.

Open:

- Application: `http://localhost:8080`
- Reverb transport: `http://localhost:8081`
- PostgreSQL from the host: `localhost:5433`

## Daily commands

```bash
docker compose up -d
docker compose ps
docker compose logs --tail=100 app queue reverb nginx postgres
```

Stop without deleting the database volume:

```bash
docker compose stop
```

Deleting the Compose volume destroys the local database. Do not run a volume-removal
command unless loss of local data is explicitly intended.

## Database and application maintenance

```bash
docker compose exec app php artisan migrate:status
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan route:list
```

Seeders are not documented as a required production-like fixture source. Inspect a
seeder before running it, and never run local seed commands against production.

## Tests and static checks

```bash
docker compose exec app composer test
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app composer audit
docker compose exec app npm audit
docker compose exec app npm run build
```

Run the smallest focused test first during development, then the affected suite,
then the full suite before handoff. See `TESTING_AND_QUALITY.md`.

## Common problems

### Application cannot connect to the database

Inside Compose, use service name `postgres` and port `5432`; host port `5433` is
only for tools running on the Mac. Verify `DB_CONNECTION=pgsql`, then inspect
`docker compose ps` and PostgreSQL logs.

### Reverb is unavailable

Verify that the `reverb` container is running, its application credentials match
the Vite-facing keys, and port `8081` is not already in use. Browser clients should
use the host/scheme configured for their environment.

### Vite build reports a missing native binding

Do not reuse `node_modules` copied from another OS or CPU architecture. Remove only
the project-local dependency directory after confirming it contains no user work,
then run a clean install from the lockfile. Registry network errors are environment
failures, not proof that source code is broken.

### Permission errors under `storage`

Check ownership inside the container and ensure `storage` and `bootstrap/cache` are
writable by the PHP-FPM user. Do not solve this with global `777` permissions.

### Configuration changes have no effect

Clear Laravel caches locally:

```bash
docker compose exec app php artisan optimize:clear
```

## Local safety

- `src/.env`, database exports, logs, uploads, and OAuth responses are sensitive.
- Use only dedicated Meta test identities and test workspaces.
- Do not replay real customer webhook payloads without redaction and authorization.
- Never interpret local SQLite tests as PostgreSQL migration proof.
