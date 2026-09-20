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

There were 43 migrations at the audited revision. The cumulative Phase 1 release
added the `workspace_audit_events` migration and the transactional `DATA-02` token
encryption data migration; both were applied to production on 20 September 2026.
The post-deploy production schema contained 40 tables. Use the repository migration
files as the field-level source of truth.

## Ownership and deletion rules

Every workspace-scoped lookup must prove that the authenticated user belongs to
the same workspace and has the required role. Route-model binding alone is not
authorization.

The deployed Phase 1 `DATA-01` change blocks account deletion while the user owns
any workspace. An owner must resolve every owned workspace through one of two
explicit paths:

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
window. The schema change is deployed, but destructive production smoke was
intentionally not run against customer data. Any production owner-account or
workspace deletion still requires an approved synthetic target or an explicitly
reviewed real operation with a current verified backup.

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

The deployed Phase 1 `DATA-02` change encrypts `oauth_tokens.access_token` and
nullable `oauth_tokens.refresh_token` through Laravel encrypted model casts. The data
migration transforms existing plaintext values in a transaction, is idempotent for
already encrypted values, and its rollback decrypts values only for application
rollback compatibility. Token fields are hidden from model serialization.

The production migration preserved the existing `APP_KEY`. Count-only verification
after deployment reported one token row, one checked field, and zero unencrypted or
unreadable values. No credential was printed. Provider connectivity was not
exercised because no approved provider smoke target was available, so operational
closure remains pending that check.

Provider error text, diagnostic output, local-debug responses, and persisted Meta
sync metadata pass through `ProviderSecretRedactor`; live HTTP requests still
receive the real credential. The safe verification command prints counts only:

```bash
php artisan oauth-tokens:check-encryption
```

It fails when any non-null token cannot be decrypted with the configured key set.
Never use a database query that prints token columns as an operational check.

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

For `DATA-02`, preserve the existing production `APP_KEY`, create and restore-verify
a fresh encrypted database backup, run the migration, then run
`oauth-tokens:check-encryption`. A code rollback to a revision without encrypted
casts requires rolling this migration back first; that deliberately restores
plaintext and therefore reopens the original database-exposure risk. Prefer a
forward fix. Key rotation is a separate reviewed operation: retain the old key in
`APP_PREVIOUS_KEYS`, prove reads, re-encrypt every row under the new primary key,
verify counts without printing values, and remove the old key only after rollback
and retention windows are approved.

## Backup and restore

No automated application/PostgreSQL backup job or full-service restore drill was
verified on 20 September 2026. Phase 0 produced snapshot `20260920T125105Z`, and the
Phase 1 release produced a second fresh snapshot `20260920T152300Z`. Both were
encrypted off-host and restored in isolation; the Phase 1 restore recovered 39
tables, 4 users, 3 workspaces, 31 messages, 9 attachments, 1 OAuth-token row, 14
uploaded files, and protected runtime configuration/TLS files. The continuing
target is:

- RPO: no more than 24 hours of data loss;
- RTO: service restored within 4 hours;
- encrypted backup storage separate from the application host;
- documented retention and access control;
- a quarterly restore drill with recorded duration and integrity checks.

Every future data-affecting release must repeat the fresh backup and isolated restore
gate; these point-in-time snapshots do not provide scheduled protection.

## Audit snapshot

The production database was reachable and consistent enough for application
queries during the audit. Snapshot counts included four users, three workspaces,
no pending jobs, and two historical failed jobs dated 20 April 2026. These values
are operational observations, not invariants; re-query them before incident or
capacity decisions.
