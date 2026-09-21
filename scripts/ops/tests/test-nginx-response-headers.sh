#!/usr/bin/env bash

# Isolated Nginx 1.27 check with a disposable certificate and synthetic upstream.
set -euo pipefail
umask 077
export LC_ALL=C

project_root=$(cd "$(dirname "$0")/../../.." && pwd)
fixture_root=$(mktemp -d /tmp/leadochat-mini-nginx-headers.XXXXXXXX)
container_name="leadochat-mini-nginx-headers-test-$$"

cleanup() {
    docker stop "$container_name" >/dev/null 2>&1 || true
    if [[ $fixture_root == /tmp/leadochat-mini-nginx-headers.[A-Za-z0-9]* ]]; then
        rm -r "$fixture_root"
    fi
}
trap cleanup EXIT

mkdir "$fixture_root/certs"
openssl req -x509 -newkey rsa:2048 -nodes -days 1 \
    -subj '/CN=mini.leadochat.com' \
    -keyout "$fixture_root/certs/origin.key" \
    -out "$fixture_root/certs/origin.crt" >/dev/null 2>&1

docker run --rm -d --name "$container_name" \
    --label com.leadochat.task=phase3-nginx-headers \
    --network none \
    --add-host app:127.0.0.1 --add-host reverb:127.0.0.1 \
    -v "$project_root/docker/nginx/default.prod.conf:/etc/nginx/conf.d/default.conf:ro" \
    -v "$project_root/scripts/ops/tests/fixtures/nginx-upstream.conf:/etc/nginx/conf.d/upstream.conf:ro" \
    -v "$project_root/src/public:/var/www/html/public:ro" \
    -v "$fixture_root/certs:/etc/nginx/certs:ro" \
    nginx:1.27-alpine >/dev/null

docker exec "$container_name" nginx -t >/dev/null 2>&1

probe() {
    docker exec "$container_name" curl --insecure --silent --show-error \
        --dump-header - --output /dev/null "https://127.0.0.1$1"
}

assert_status() {
    printf '%s\n' "$2" | grep -Eq "HTTP/[0-9.]+ $1([[:space:]]|$)" || {
        printf 'expected HTTP %s for %s\n' "$1" "$3" >&2
        return 1
    }
}

assert_header() {
    local count
    count=$(printf '%s\n' "$2" | grep -Eic "^[[:space:]]*$1:[[:space:]]*$3[[:space:]]*$" || true)
    [[ $count == 1 ]] || {
        printf 'expected one %s header for %s; got %s\n' "$1" "$4" "$count" >&2
        printf '%s\n' "$2" >&2
        return 1
    }
}

assert_no_header() {
    if printf '%s\n' "$2" | grep -Eiq "^[[:space:]]*$1:"; then
        printf 'unexpected %s header for %s\n' "$1" "$3" >&2
        return 1
    fi
}

assert_baseline() {
    assert_header X-Content-Type-Options "$2" nosniff "$1"
    assert_header X-Frame-Options "$2" SAMEORIGIN "$1"
    assert_header Referrer-Policy "$2" strict-origin-when-cross-origin "$1"
    assert_header Permissions-Policy "$2" 'camera=\(\), microphone=\(\), geolocation=\(\), payment=\(\), usb=\(\)' "$1"
    assert_header Strict-Transport-Security "$2" max-age=86400 "$1"
}

static_response=$(probe /robots.txt)
assert_status 200 "$static_response" static
assert_baseline static "$static_response"
assert_no_header Content-Security-Policy "$static_response" static
printf 'PASS static response headers\n'

error_response=$(probe /missing.php)
assert_status 502 "$error_response" nginx_error
assert_baseline nginx_error "$error_response"
printf 'PASS nginx-generated error headers\n'

denied_response=$(probe /storage/blocked.php)
assert_status 404 "$denied_response" upload_execution_deny
assert_baseline upload_execution_deny "$denied_response"
printf 'PASS upload-execution denial headers\n'

upstream_response=$(probe /app)
assert_status 200 "$upstream_response" upstream
assert_baseline upstream "$upstream_response"
printf 'PASS no duplicate upstream security headers\n'

redirect_response=$(docker exec "$container_name" curl --silent --show-error \
    --dump-header - --output /dev/null http://127.0.0.1/robots.txt)
assert_status 301 "$redirect_response" http_redirect
assert_no_header Strict-Transport-Security "$redirect_response" http_redirect
printf 'PASS HTTP redirect has no HSTS\n'
