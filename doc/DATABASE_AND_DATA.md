# Database and data

## Database contract

Production uses PostgreSQL 16. Schema changes must preserve PostgreSQL compatibility
and be tested against PostgreSQL before deployment. The default PHPUnit suite uses
SQLite in memory and cannot prove JSONB behavior, PostgreSQL constraints, locking,
or migration compatibility.

## Data domains

| Domain | Primary tables/models | Ownership |
| --- | --- | --- |
| Identity | users, password reset tokens, sessions | User |
| Workspace | workspaces, workspace_members, workspace_departments, workspace_tags | Workspace |
| Provider connection | provider_connections, oauth_tokens, provider_permissions | Workspace/provider connection |
| Webhooks | webhook_events | Provider event, optionally resolved to workspace/connection |
| Inbox | conversations, participants, messages, attachments, tag pivot | Workspace/conversation |
| Social | social_posts, social_post_media, social_comments, social_stories | Workspace/provider connection |
| Catalog | catalogs, products, market overrides, offers | Workspace/catalog |
| Commerce structure | product sets, set items, collections, collection pivot | Workspace/catalog |
| Commerce operations | orders, order items, order snapshots, promotion campaigns | Workspace |
| Infrastructure | cache, jobs, job batches, failed jobs | Application runtime |

There were 43 migrations at the audited revision. Use the repository migration
files as the field-level source of truth.

## Ownership and deletion rules

Every workspace-scoped lookup must prove that the authenticated user belongs to
the same workspace and has the required role. Route-model binding alone is not
authorization.

Current owner deletion can cascade through `workspaces.owner_id` and destroy
workspace data. Until `DATA-01` is closed, deleting an owner account is a
release-blocking operation that requires manual review and a verified backup.

Soft deletion, retention, privacy erasure, and provider-data retention are not
consistently documented or implemented across all domains. Do not promise a
retention period until product/legal owners approve it and enforcement is tested.

## Sensitive data

Treat these as secrets or regulated customer content:

- OAuth access and refresh tokens;
- webhook headers and payloads;
- conversation messages, attachments, notes, and participant identifiers;
- provider account metadata;
- email addresses and authentication/session data;
- database backups, exports, failure payloads, and logs.

OAuth tokens are currently stored without encrypted casts and are tracked as
`DATA-02`. Encryption remediation must include a data migration, backup, rollback,
key management, and token rotation decision.

## Migration rules

Before a migration:

1. Define forward behavior, rollback behavior, lock/runtime expectations, and data
   transformation impact.
2. Back up the affected data and verify that the backup is readable.
3. Test forward and, where safe, backward migration against PostgreSQL 16.
4. Test application compatibility during any mixed-version window.
5. Use additive changes before destructive cleanup when possible.
6. Never run a destructive migration without explicit approval and a restore plan.

After a migration, verify schema state, row counts, constraints, queue health, and
the affected journeys. A successful Artisan exit code alone is insufficient.

Legacy locally stored message attachments can be inventoried and moved from the
public disk with:

```bash
php artisan attachments:migrate-private --dry-run
php artisan attachments:migrate-private
```

The command is idempotent, verifies matching source/target checksums before deleting
the public copy, preserves remote provider attachments, and updates `meta.disk` to
`local`. Run it only after a fresh verified backup and verify both the row count
and public/private file inventories afterward.

## Backup and restore

No application/PostgreSQL backup job or successful restore drill was verified on
20 September 2026. The target is:

- RPO: no more than 24 hours of data loss;
- RTO: service restored within 4 hours;
- encrypted backup storage separate from the application host;
- documented retention and access control;
- a quarterly restore drill with recorded duration and integrity checks.

The first remediation phase must establish a backup before any token-encryption,
authorization, or deletion change reaches production.

## Audit snapshot

The production database was reachable and consistent enough for application
queries during the audit. Snapshot counts included four users, three workspaces,
no pending jobs, and two historical failed jobs dated 20 April 2026. These values
are operational observations, not invariants; re-query them before incident or
capacity decisions.
