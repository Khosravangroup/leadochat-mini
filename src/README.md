# LeadoChat Mini application

This directory contains the Laravel application for LeadoChat Mini. The project
overview, Docker setup, policies, risks, and operational documentation live at the
repository root.

- [Project README](../README.md)
- [Documentation index](../doc/INDEX.md)
- [Architecture](../doc/ARCHITECTURE.md)
- [Local development](../doc/LOCAL_DEVELOPMENT.md)
- [Testing and quality](../doc/TESTING_AND_QUALITY.md)
- [Browser security headers and CSP rollout](../doc/SECURITY_HEADERS_AND_CSP.md)
- [Security policy](../SECURITY.md)

Run framework commands from this directory only when working outside Docker. The
supported local workflow runs them through the root Compose project:

```bash
docker compose exec app php artisan test
docker compose exec app php artisan route:list
docker compose exec app npm run build
```

Do not commit `src/.env`, generated dependencies, build output, runtime storage,
tokens, customer payloads, or database exports.
