<?php

namespace Tests\Unit;

use App\Support\ProviderSecretRedactor;
use PHPUnit\Framework\TestCase;

class ProviderSecretRedactorTest extends TestCase
{
    public function test_it_redacts_sensitive_keys_and_known_values_recursively(): void
    {
        $payload = ProviderSecretRedactor::payload([
            'error' => 'Provider echoed known-secret-token in an error.',
            'access_token' => 'known-secret-token',
            'nested' => [
                'client_secret' => 'client-secret-value',
                'message' => 'Bearer bearer-secret-value',
            ],
        ], ['known-secret-token']);

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('known-secret-token', $encoded);
        $this->assertStringNotContainsString('client-secret-value', $encoded);
        $this->assertStringNotContainsString('bearer-secret-value', $encoded);
        $this->assertStringContainsString('[redacted]', $encoded);
    }

    public function test_it_redacts_tokens_from_provider_error_text(): void
    {
        $error = ProviderSecretRedactor::text(
            'access_token=abc123&message=failed; Authorization: Bearer xyz987',
            ['abc123']
        );

        $this->assertStringNotContainsString('abc123', $error);
        $this->assertStringNotContainsString('xyz987', $error);
        $this->assertStringContainsString('message=failed', $error);
    }
}
