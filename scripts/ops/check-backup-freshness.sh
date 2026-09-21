#!/usr/bin/env bash

# Read-only macOS check of the newest complete off-host snapshot. Intended as a
# monitoring primitive; it neither schedules capture nor sends notifications.
set -euo pipefail
umask 077
export LC_ALL=C

die() {
    printf 'backup-freshness: %s\n' "$*" >&2
    exit 1
}

[[ $# -eq 0 ]] || die 'no arguments expected'
[[ $(uname -s) == Darwin ]] || die 'macOS is required'
for tool in stat date shasum awk grep id; do
    command -v "$tool" >/dev/null || die "missing required tool: $tool"
done

: "${LEADOCHAT_BACKUP_ROOT:?set LEADOCHAT_BACKUP_ROOT}"
backup_root=${LEADOCHAT_BACKUP_ROOT%/}
max_age_seconds=${LEADOCHAT_BACKUP_MAX_AGE_SECONDS:-86400}

[[ $backup_root == /* && $backup_root != / && -d $backup_root && ! -L $backup_root ]] ||
    die 'backup root must be an existing absolute, non-symlink directory'
[[ $(stat -f '%Lp' "$backup_root") == 700 ]] || die 'backup root must have mode 700'
[[ $(stat -f '%Su' "$backup_root") == "$(id -un)" ]] || die 'backup root must belong to the current user'
[[ $max_age_seconds =~ ^[1-9][0-9]{0,8}$ ]] || die 'invalid maximum age'

shopt -s nullglob
snapshots=("$backup_root"/[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]T[0-9][0-9][0-9][0-9][0-9][0-9]Z)
((${#snapshots[@]} > 0)) || die 'no snapshot directories found'
latest=${snapshots[${#snapshots[@]}-1]}
snapshot_id=${latest##*/}
[[ -d $latest && ! -L $latest ]] || die "latest snapshot is not a directory: $snapshot_id"
[[ $(stat -f '%Lp' "$latest") == 700 ]] || die "snapshot directory permissions are unsafe: $snapshot_id"
[[ $(stat -f '%Su' "$latest") == "$(id -un)" ]] || die "snapshot directory owner is unexpected: $snapshot_id"

snapshot_epoch=$(date -j -u -f '%Y%m%dT%H%M%SZ' "$snapshot_id" '+%s' 2>/dev/null) ||
    die "invalid snapshot timestamp: $snapshot_id"
now_epoch=$(date -u '+%s')
age_seconds=$((now_epoch - snapshot_epoch))
((age_seconds >= 0)) || die "snapshot timestamp is in the future: $snapshot_id"
((age_seconds <= max_age_seconds)) || die "snapshot is stale: $snapshot_id age_seconds=$age_seconds"

for artifact in postgres.dump.enc storage-app.tar.gz.enc runtime-config.tar.gz.enc MANIFEST.txt SHA256SUMS COMPLETE; do
    [[ -f $latest/$artifact && ! -L $latest/$artifact && -s $latest/$artifact ]] ||
        die "snapshot is incomplete: $snapshot_id"
    [[ $(stat -f '%Lp' "$latest/$artifact") == 600 ]] ||
        die "snapshot artifact permissions are unsafe: $snapshot_id"
done

expected_names=$'postgres.dump.enc\nstorage-app.tar.gz.enc\nruntime-config.tar.gz.enc\nMANIFEST.txt'
actual_names=$(awk '{ print $2 }' "$latest/SHA256SUMS")
[[ $actual_names == "$expected_names" ]] || die "snapshot checksum inventory is unexpected: $snapshot_id"
grep -Fx "created_utc=$snapshot_id" "$latest/MANIFEST.txt" >/dev/null ||
    die "snapshot manifest does not match its directory: $snapshot_id"

(cd "$latest" && shasum -a 256 -c SHA256SUMS >/dev/null) ||
    die "snapshot checksum verification failed: $snapshot_id"

printf 'backup-freshness: healthy snapshot=%s age_seconds=%s\n' "$snapshot_id" "$age_seconds"
