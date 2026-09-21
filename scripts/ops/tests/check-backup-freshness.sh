#!/usr/bin/env bash

# macOS-only synthetic fixtures; no production data or credentials are used.
set -euo pipefail
umask 077
export LC_ALL=C

script_dir=$(cd "$(dirname "$0")/.." && pwd)
fixture_root=$(mktemp -d /tmp/leadochat-mini-freshness-test.XXXXXXXX)
cleanup() {
    [[ $fixture_root == /tmp/leadochat-mini-freshness-test.[A-Za-z0-9]* ]] || return 1
    rm -r "$fixture_root"
}
trap cleanup EXIT

expect_status() {
    local expected=$1 label=$2 actual
    shift 2
    if "$@" >"$fixture_root/check-output" 2>&1; then
        actual=0
    else
        actual=$?
    fi
    [[ $actual == "$expected" ]] || {
        printf '%s: expected exit %s, got %s\n' "$label" "$expected" "$actual" >&2
        return 1
    }
    printf 'PASS %s\n' "$label"
}

check() {
    LEADOCHAT_BACKUP_ROOT=$fixture_root bash "$script_dir/check-backup-freshness.sh"
}

expect_status 1 missing_snapshot check

snapshot_id=$(date -u +%Y%m%dT%H%M%SZ)
snapshot_dir="$fixture_root/$snapshot_id"
mkdir "$snapshot_dir"
for artifact in postgres.dump.enc storage-app.tar.gz.enc runtime-config.tar.gz.enc; do
    printf 'synthetic test artifact\n' >"$snapshot_dir/$artifact"
done
printf 'created_utc=%s\n' "$snapshot_id" >"$snapshot_dir/MANIFEST.txt"
printf 'completed_utc=%s\n' "$snapshot_id" >"$snapshot_dir/COMPLETE"
(
    cd "$snapshot_dir"
    shasum -a 256 postgres.dump.enc storage-app.tar.gz.enc runtime-config.tar.gz.enc MANIFEST.txt >SHA256SUMS
)

expect_status 0 healthy_snapshot check

printf 'corruption\n' >>"$snapshot_dir/postgres.dump.enc"
expect_status 1 checksum_corruption check
printf 'synthetic test artifact\n' >"$snapshot_dir/postgres.dump.enc"

mv "$snapshot_dir/SHA256SUMS" "$fixture_root/checksums-original"
printf 'malformed inventory\n' >"$snapshot_dir/SHA256SUMS"
expect_status 1 unexpected_checksum_inventory check
mv "$fixture_root/checksums-original" "$snapshot_dir/SHA256SUMS"

chmod 644 "$snapshot_dir/storage-app.tar.gz.enc"
expect_status 1 unsafe_artifact_permissions check
chmod 600 "$snapshot_dir/storage-app.tar.gz.enc"

mv "$snapshot_dir/COMPLETE" "$fixture_root/missing-COMPLETE"
expect_status 1 incomplete_snapshot check
mv "$fixture_root/missing-COMPLETE" "$snapshot_dir/COMPLETE"

LEADOCHAT_BACKUP_MAX_AGE_SECONDS=1
export LEADOCHAT_BACKUP_MAX_AGE_SECONDS
old_id=$(date -u -v-25H +%Y%m%dT%H%M%SZ)
old_dir="$fixture_root/$old_id"
mv "$snapshot_dir" "$old_dir"
printf 'created_utc=%s\n' "$old_id" >"$old_dir/MANIFEST.txt"
(
    cd "$old_dir"
    shasum -a 256 postgres.dump.enc storage-app.tar.gz.enc runtime-config.tar.gz.enc MANIFEST.txt >SHA256SUMS
)
expect_status 1 stale_snapshot check
