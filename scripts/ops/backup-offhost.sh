#!/usr/bin/env bash

# Run on macOS. Streams production data over pinned SSH directly into encrypted
# files; no plaintext backup file is created on either filesystem.
set -euo pipefail
umask 077
export LC_ALL=C

die() {
    printf 'backup-offhost: %s\n' "$*" >&2
    exit 1
}

usage() {
    printf 'Usage: %s check|capture\n' "$0" >&2
    exit 2
}

[[ $# -eq 1 ]] || usage
case "$1" in
    check|capture) action=$1 ;;
    *) usage ;;
esac

[[ $(uname -s) == Darwin ]] || die 'macOS Keychain is required'

for tool in ssh security openssl shasum tar pg_restore stat mktemp; do
    command -v "$tool" >/dev/null || die "missing required tool: $tool"
done

: "${LEADOCHAT_BACKUP_ROOT:?set LEADOCHAT_BACKUP_ROOT}"
: "${LEADOCHAT_BACKUP_SSH_TARGET:?set LEADOCHAT_BACKUP_SSH_TARGET}"
: "${LEADOCHAT_BACKUP_SSH_KEY:?set LEADOCHAT_BACKUP_SSH_KEY}"
: "${LEADOCHAT_BACKUP_EXPECTED_HOST:?set LEADOCHAT_BACKUP_EXPECTED_HOST}"
: "${LEADOCHAT_BACKUP_REMOTE_ROOT:?set LEADOCHAT_BACKUP_REMOTE_ROOT}"

backup_root=${LEADOCHAT_BACKUP_ROOT%/}
ssh_target=$LEADOCHAT_BACKUP_SSH_TARGET
ssh_key=$LEADOCHAT_BACKUP_SSH_KEY
expected_host=$LEADOCHAT_BACKUP_EXPECTED_HOST
remote_root=$LEADOCHAT_BACKUP_REMOTE_ROOT
ssh_port=${LEADOCHAT_BACKUP_SSH_PORT:-22}
keychain_service=${LEADOCHAT_BACKUP_KEYCHAIN_SERVICE:-com.leadochat.codex.backup}
keychain_account=${LEADOCHAT_BACKUP_KEYCHAIN_ACCOUNT:-leadochat-mini-production-backup}

[[ $backup_root == /* && $backup_root != / && -d $backup_root && ! -L $backup_root ]] ||
    die 'backup root must be an existing absolute, non-symlink directory'
[[ $(stat -f '%Lp' "$backup_root") == 700 ]] || die 'backup root must have mode 700'
[[ $(stat -f '%Su' "$backup_root") == "$(id -un)" ]] || die 'backup root must belong to the current user'
[[ $ssh_key == /* && -f $ssh_key && ! -L $ssh_key ]] || die 'SSH identity must be an existing absolute, non-symlink file'
[[ $(stat -f '%Lp' "$ssh_key") == 600 ]] || die 'SSH identity must have mode 600'
[[ $ssh_target =~ ^[A-Za-z0-9._-]+@[A-Za-z0-9._-]+$ ]] || die 'invalid SSH target'
[[ $expected_host =~ ^[A-Za-z0-9._-]+$ ]] || die 'invalid expected hostname'
[[ $remote_root =~ ^(/[A-Za-z0-9._-]+)+$ ]] || die 'invalid remote project root'
[[ $ssh_port =~ ^[1-9][0-9]{0,4}$ ]] || die 'invalid SSH port'
(( ssh_port <= 65535 )) || die 'invalid SSH port'

ssh_options=(-i "$ssh_key" -p "$ssh_port" -o BatchMode=yes -o IdentitiesOnly=yes
    -o StrictHostKeyChecking=yes -o ConnectTimeout=10)

remote() {
    ssh "${ssh_options[@]}" "$ssh_target" "$1"
}

keychain_passphrase() {
    security find-generic-password -s "$keychain_service" -a "$keychain_account" -w
}

actual_host=$(remote hostname)
[[ $actual_host == "$expected_host" ]] || die "remote host identity mismatch: $actual_host"
source_revision=$(remote "git -C $remote_root rev-parse HEAD")
[[ $source_revision =~ ^[0-9a-f]{40}$ ]] || die 'remote Git revision is unavailable'
[[ $(remote "stat -c %a $remote_root/src/.env") == 600 ]] || die 'remote environment file is not mode 600'
remote "test -d $remote_root/src/storage/app && test -f $remote_root/src/.env && test -d $remote_root/docker/nginx/certs && docker exec leadochat-mini-postgres sh -c 'pg_isready -U \"\$POSTGRES_USER\" -d \"\$POSTGRES_DB\"' >/dev/null" >/dev/null ||
    die 'remote backup sources or PostgreSQL are unavailable'

if [[ $action == check ]]; then
    security find-generic-password -s "$keychain_service" -a "$keychain_account" >/dev/null ||
        die 'backup Keychain item is unavailable'
    printf 'backup preflight passed: host=%s revision=%s\n' "$actual_host" "$source_revision"
    exit 0
fi

# Access is checked before writing anything. The secret is never printed or passed
# as a command-line argument; OpenSSL reads it from a private file descriptor.
keychain_passphrase >/dev/null || die 'backup Keychain passphrase is inaccessible'

lock_dir="$backup_root/.capture.lock"
mkdir "$lock_dir" 2>/dev/null || die 'capture already active or stale lock exists'
trap 'rmdir "$lock_dir" 2>/dev/null || true' EXIT

created_utc=$(date -u +%Y%m%dT%H%M%SZ)
final_dir="$backup_root/$created_utc"
[[ ! -e $final_dir ]] || die 'snapshot identifier already exists'
stage_dir=$(mktemp -d "$backup_root/.incomplete.$created_utc.XXXXXXXX")

encrypt_stream() {
    openssl enc -aes-256-cbc -salt -pbkdf2 -iter 600000 -pass fd:3 -out "$1" \
        3< <(keychain_passphrase)
}

decrypt_stream() {
    openssl enc -d -aes-256-cbc -pbkdf2 -iter 600000 -pass fd:3 -in "$1" \
        3< <(keychain_passphrase)
}

db_command="docker exec leadochat-mini-postgres sh -c 'PGPASSWORD=\"\$POSTGRES_PASSWORD\" exec pg_dump -Fc --no-owner --no-acl -U \"\$POSTGRES_USER\" -d \"\$POSTGRES_DB\"'"
remote "$db_command" | encrypt_stream "$stage_dir/postgres.dump.enc"
remote "tar -C $remote_root/src/storage -czf - app" |
    encrypt_stream "$stage_dir/storage-app.tar.gz.enc"
remote "tar -C $remote_root -czf - src/.env docker/nginx/certs" |
    encrypt_stream "$stage_dir/runtime-config.tar.gz.enc"

for artifact in postgres.dump.enc storage-app.tar.gz.enc runtime-config.tar.gz.enc; do
    [[ -s $stage_dir/$artifact ]] || die "empty backup artifact: $artifact"
done

decrypt_stream "$stage_dir/postgres.dump.enc" | pg_restore -l >/dev/null
decrypt_stream "$stage_dir/storage-app.tar.gz.enc" | tar -tzf - >/dev/null
decrypt_stream "$stage_dir/runtime-config.tar.gz.enc" | tar -tzf - >/dev/null

printf 'created_utc=%s\nsource_host=%s\nsource_revision=%s\nsource_path=%s\n' \
    "$created_utc" "$actual_host" "$source_revision" "$remote_root" >"$stage_dir/MANIFEST.txt"
printf 'database_format=postgresql-custom\nstorage_format=tar-gzip\nruntime_config_format=tar-gzip\n' >>"$stage_dir/MANIFEST.txt"
printf 'encryption=aes-256-cbc-pbkdf2-600000\nkeychain_service=%s\nkeychain_account=%s\n' \
    "$keychain_service" "$keychain_account" >>"$stage_dir/MANIFEST.txt"
printf 'stream_verification=passed\nplaintext_backup_files=none\nretention=not_configured\n' >>"$stage_dir/MANIFEST.txt"

(
    cd "$stage_dir"
    shasum -a 256 postgres.dump.enc storage-app.tar.gz.enc runtime-config.tar.gz.enc MANIFEST.txt >SHA256SUMS
    shasum -a 256 -c SHA256SUMS >/dev/null
)
printf 'completed_utc=%s\n' "$(date -u +%Y%m%dT%H%M%SZ)" >"$stage_dir/COMPLETE"
mv "$stage_dir" "$final_dir"
printf 'encrypted snapshot complete: %s revision=%s\n' "$final_dir" "$source_revision"
