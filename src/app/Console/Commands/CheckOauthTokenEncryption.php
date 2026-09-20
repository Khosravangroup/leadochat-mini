<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CheckOauthTokenEncryption extends Command
{
    protected $signature = 'oauth-tokens:check-encryption';

    protected $description = 'Verify OAuth token columns are encrypted without printing secret values';

    public function handle(): int
    {
        $rows = 0;
        $checkedFields = 0;
        $unencryptedOrUnreadable = 0;

        DB::table('oauth_tokens')
            ->select(['id', 'access_token', 'refresh_token'])
            ->orderBy('id')
            ->chunkById(100, function ($tokens) use (&$rows, &$checkedFields, &$unencryptedOrUnreadable): void {
                foreach ($tokens as $token) {
                    $rows++;

                    foreach ([$token->access_token, $token->refresh_token] as $value) {
                        if ($value === null) {
                            continue;
                        }

                        $checkedFields++;

                        try {
                            Crypt::decryptString((string) $value);
                        } catch (DecryptException) {
                            $unencryptedOrUnreadable++;
                        }
                    }
                }
            });

        $this->line(sprintf(
            'rows=%d checked_fields=%d unencrypted_or_unreadable=%d',
            $rows,
            $checkedFields,
            $unencryptedOrUnreadable
        ));

        if ($unencryptedOrUnreadable > 0) {
            $this->error('OAuth token encryption verification failed. No token values were printed.');

            return self::FAILURE;
        }

        $this->info('OAuth token encryption verification passed.');

        return self::SUCCESS;
    }
}
