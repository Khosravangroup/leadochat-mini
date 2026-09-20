<?php

namespace App\Support;

final class ProviderSecretRedactor
{
    private const REDACTED = '[redacted]';

    public static function payload(mixed $payload, array $knownSecrets = []): mixed
    {
        if (is_array($payload)) {
            $sanitized = [];

            foreach ($payload as $key => $value) {
                $normalizedKey = strtolower((string) $key);

                if (
                    str_contains($normalizedKey, 'token')
                    || str_contains($normalizedKey, 'secret')
                    || str_contains($normalizedKey, 'authorization')
                    || str_contains($normalizedKey, 'code_verifier')
                ) {
                    $sanitized[$key] = self::REDACTED;

                    continue;
                }

                $sanitized[$key] = self::payload($value, $knownSecrets);
            }

            return $sanitized;
        }

        if (is_string($payload)) {
            return self::text($payload, $knownSecrets);
        }

        return $payload;
    }

    public static function text(string $text, array $knownSecrets = []): string
    {
        foreach (array_unique(array_filter($knownSecrets, fn ($value) => is_string($value) && $value !== '')) as $secret) {
            $text = str_replace([$secret, rawurlencode($secret)], self::REDACTED, $text);
        }

        $text = preg_replace(
            '/\bBearer\s+[A-Za-z0-9._~+\/=:-]+/i',
            'Bearer '.self::REDACTED,
            $text
        ) ?? $text;

        return preg_replace(
            '/((?:access|refresh|id)_token|client_secret|authorization)(["\']?\s*[:=]\s*["\']?)([^&\s,"\'}]+)/i',
            '$1$2'.self::REDACTED,
            $text
        ) ?? $text;
    }
}
