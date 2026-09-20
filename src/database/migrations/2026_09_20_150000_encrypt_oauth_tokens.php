<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        $this->transformTokens(function (string $value): string {
            try {
                Crypt::decryptString($value);

                return $value;
            } catch (DecryptException $exception) {
                $this->rejectUnreadableCiphertext($value, $exception);

                return Crypt::encryptString($value);
            }
        });
    }

    public function down(): void
    {
        $this->transformTokens(function (string $value): string {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $exception) {
                $this->rejectUnreadableCiphertext($value, $exception);

                return $value;
            }
        });
    }

    private function transformTokens(callable $transform): void
    {
        DB::table('oauth_tokens')
            ->select(['id', 'access_token', 'refresh_token'])
            ->orderBy('id')
            ->chunkById(100, function ($tokens) use ($transform): void {
                foreach ($tokens as $token) {
                    DB::table('oauth_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'access_token' => $transform((string) $token->access_token),
                            'refresh_token' => $token->refresh_token === null
                                ? null
                                : $transform((string) $token->refresh_token),
                        ]);
                }
            });
    }

    private function rejectUnreadableCiphertext(string $value, DecryptException $exception): void
    {
        $decoded = base64_decode($value, true);
        $payload = is_string($decoded) ? json_decode($decoded, true) : null;

        if (
            is_array($payload)
            && array_key_exists('iv', $payload)
            && array_key_exists('value', $payload)
            && array_key_exists('mac', $payload)
        ) {
            throw new RuntimeException(
                'OAuth token ciphertext cannot be decrypted with the configured application key set.',
                previous: $exception
            );
        }
    }
};
