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
| Workspace | workspaces, workspace_members, workspace_departments, workspace_tags, workspace_audit_events | Workspace |
| Provider connection | provider_connections, oauth_tokens, provider_permissions | Workspace/provider connection |
| Webhooks | webhook_events | Provider event, optionally resolved to workspace/connection |
| Inbox | conversations, participants, messages, attachments, tag pivot | Workspace/conversation |
| Social | social_posts, social_post_media, social_comments, social_stories | Workspace/provider connection |
| Catalog | catalogs, products, market overrides, offers | Workspace/catalog |
| Commerce structure | product sets, set items, collections, collection pivot | Workspace/catalog |
| Commerce operations | orders, order items, order snapshots, promotion campaigns | Workspace |
| Infrastructure | cache, jobs, job batches, failed jobs | Application runtime |

There were 43 migrations at the audited revision. The Phase 1 `DATA-01` candidate
adds the `workspace_audit_events` migration. Use the repository migration files as
the field-level source of truth.

## Ownership and deletion rules

Every workspace-scoped lookup must prove that the authenticated user belongs to
the same workspace and has the required role. Route-model binding alone is not
authorization.

The Phase 1 `DATA-01` candidate blocks account deletion while the user owns any
workspace. An owner must resolve every owned workspace through one of two explicit
paths:

1. transfer ownership to an existing workspace member after re-entering the
   current password; or
2. permanently delete the workspace after re-entering the current password and
   typing the exact workspace slug.

`workspaces.owner_id` is the authoritative single-owner record. A successful
transfer updates it and both affected pivot roles in one locked transaction. A
profile deletion after transfer removes only the former owner's account and leaves
the workspace intact. Explicit workspace deletion intentionally uses the existing
foreign-key cascades and can remove conversations, messages, attachments, provider
connections, catalog/commerce records, and other scoped data.

Ownership transfer and workspace deletion write immutable evidence to
`workspace_audit_events`. The audit table deliberately has no cascading foreign
keys, so the recorded IDs and deletion metadata survive removal of a workspace or
actor. Restore remains a backup operation; there is no undelete UI or soft-delete
window. Until `DATA-01` is approved, deployed, and production-smoke-tested, any
owner-account or workspace deletion remains a release-blocking operation.

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

No automated application/PostgreSQL backup job or full-service restore drill was
verified on 20 September 2026. Phase 0 did produce one encrypted off-host snapshot
and successfully restore its database, uploaded files, and environment files in
isolated containers. The continuing target is:

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
