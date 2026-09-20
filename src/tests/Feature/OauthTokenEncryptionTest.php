<?php

namespace Tests\Feature;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class OauthTokenEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_tokens_are_encrypted_at_rest_and_hidden_from_serialization(): void
    {
        $connection = $this->createConnection();

        $token = OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'access-token-plaintext',
            'refresh_token' => 'refresh-token-plaintext',
            'is_primary' => true,
        ]);

        $raw = DB::table('oauth_tokens')->where('id', $token->id)->first();

        $this->assertNotSame('access-token-plaintext', $raw->access_token);
        $this->assertNotSame('refresh-token-plaintext', $raw->refresh_token);
        $this->assertStringNotContainsString('access-token-plaintext', $raw->access_token);
        $this->assertStringNotContainsString('refresh-token-plaintext', $raw->refresh_token);
        $this->assertSame('access-token-plaintext', $token->fresh()->access_token);
        $this->assertSame('refresh-token-plaintext', $token->fresh()->refresh_token);
        $this->assertArrayNotHasKey('access_token', $token->toArray());
        $this->assertArrayNotHasKey('refresh_token', $token->toArray());
    }

    public function test_null_refresh_token_remains_null(): void
    {
        $token = OauthToken::create([
            'provider_connection_id' => $this->createConnection()->id,
            'token_type' => 'access_token',
            'access_token' => 'access-token-only',
            'refresh_token' => null,
            'is_primary' => true,
        ]);

        $this->assertNull(DB::table('oauth_tokens')->where('id', $token->id)->value('refresh_token'));
        $this->assertNull($token->fresh()->refresh_token);
    }

    public function test_migration_encrypts_plaintext_rows_and_is_idempotent(): void
    {
        $connection = $this->createConnection();
        $tokenId = DB::table('oauth_tokens')->insertGetId([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'legacy-access-token',
            'refresh_token' => 'legacy-refresh-token',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_20_150000_encrypt_oauth_tokens.php');
        $migration->up();

        $firstCiphertext = DB::table('oauth_tokens')->where('id', $tokenId)->value('access_token');

        $this->assertNotSame('legacy-access-token', $firstCiphertext);
        $this->assertSame('legacy-access-token', OauthToken::findOrFail($tokenId)->access_token);
        $this->assertSame('legacy-refresh-token', OauthToken::findOrFail($tokenId)->refresh_token);

        $migration->up();

        $this->assertSame(
            $firstCiphertext,
            DB::table('oauth_tokens')->where('id', $tokenId)->value('access_token')
        );
    }

    public function test_migration_rollback_decrypts_rows_for_application_rollback(): void
    {
        $token = OauthToken::create([
            'provider_connection_id' => $this->createConnection()->id,
            'token_type' => 'access_token',
            'access_token' => 'rollback-access-token',
            'refresh_token' => 'rollback-refresh-token',
            'is_primary' => true,
        ]);
        $migration = require database_path('migrations/2026_09_20_150000_encrypt_oauth_tokens.php');

        $migration->down();

        $raw = DB::table('oauth_tokens')->where('id', $token->id)->first();
        $this->assertSame('rollback-access-token', $raw->access_token);
        $this->assertSame('rollback-refresh-token', $raw->refresh_token);

        $migration->up();

        $this->assertSame('rollback-access-token', $token->fresh()->access_token);
        $this->assertSame('rollback-refresh-token', $token->fresh()->refresh_token);
    }

    public function test_migration_rejects_ciphertext_from_an_unavailable_key_without_reencrypting_it(): void
    {
        $foreignCiphertext = (new Encrypter(str_repeat('x', 32), 'AES-256-CBC'))
            ->encryptString('foreign-key-access-token');
        $tokenId = DB::table('oauth_tokens')->insertGetId([
            'provider_connection_id' => $this->createConnection()->id,
            'token_type' => 'access_token',
            'access_token' => $foreignCiphertext,
            'refresh_token' => null,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $migration = require database_path('migrations/2026_09_20_150000_encrypt_oauth_tokens.php');

        try {
            $migration->up();
            $this->fail('Expected migration to reject unreadable ciphertext.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('configured application key set', $exception->getMessage());
        }

        $this->assertSame(
            $foreignCiphertext,
            DB::table('oauth_tokens')->where('id', $tokenId)->value('access_token')
        );
    }

    public function test_encryption_check_fails_closed_without_printing_tokens(): void
    {
        $connection = $this->createConnection();
        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'encrypted-check-token',
            'is_primary' => true,
        ]);

        $this->artisan('oauth-tokens:check-encryption')
            ->expectsOutputToContain('unencrypted_or_unreadable=0')
            ->doesntExpectOutputToContain('encrypted-check-token')
            ->assertSuccessful();

        DB::table('oauth_tokens')->insert([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'unsafe-plaintext-token',
            'refresh_token' => null,
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('oauth-tokens:check-encryption')
            ->expectsOutputToContain('unencrypted_or_unreadable=1')
            ->doesntExpectOutputToContain('unsafe-plaintext-token')
            ->assertFailed();
    }

    private function createConnection(): ProviderConnection
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Token Encryption Workspace',
            'slug' => 'token-encryption-'.str()->random(8),
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        return ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'token-encryption-'.str()->random(8),
            'provider_account_name' => 'Token Encryption Account',
            'status' => 'connected',
        ]);
    }
}
