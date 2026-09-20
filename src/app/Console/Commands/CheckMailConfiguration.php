<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckMailConfiguration extends Command
{
    protected $signature = 'app:mail-check';

    protected $description = 'Verify that mail configuration can deliver transactional email without printing secrets';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['', 'array', 'log'], true)) {
            $this->error('The configured mail transport is not delivery-capable.');

            return self::FAILURE;
        }

        if ($mailer !== 'smtp') {
            $this->error('The configured mail transport has not been verified for this deployment.');

            return self::FAILURE;
        }

        $missing = [];

        foreach (['host', 'port', 'username', 'password'] as $key) {
            if (blank(config("mail.mailers.smtp.{$key}"))) {
                $missing[] = 'MAIL_'.strtoupper($key);
            }
        }

        $fromAddress = (string) config('mail.from.address');

        if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false || str_ends_with($fromAddress, '@example.com')) {
            $missing[] = 'MAIL_FROM_ADDRESS';
        }

        if ($missing !== []) {
            $this->error('Mail configuration is incomplete: '.implode(', ', $missing).'.');

            return self::FAILURE;
        }

        $this->info('Mail configuration is delivery-capable.');

        return self::SUCCESS;
    }
}
