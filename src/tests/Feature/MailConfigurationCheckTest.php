<?php

namespace Tests\Feature;

use Tests\TestCase;

class MailConfigurationCheckTest extends TestCase
{
    public function test_non_delivering_mail_transport_fails_the_configuration_check(): void
    {
        config()->set('mail.default', 'log');

        $this->artisan('app:mail-check')
            ->expectsOutputToContain('not delivery-capable')
            ->assertFailed();
    }

    public function test_complete_smtp_configuration_passes_without_printing_credentials(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.host', 'mail.example.test');
        config()->set('mail.mailers.smtp.port', 587);
        config()->set('mail.mailers.smtp.username', 'mailer-user');
        config()->set('mail.mailers.smtp.password', 'super-secret-mail-password');
        config()->set('mail.from.address', 'no-reply@example.test');

        $this->artisan('app:mail-check')
            ->expectsOutputToContain('delivery-capable')
            ->doesntExpectOutputToContain('mailer-user')
            ->doesntExpectOutputToContain('super-secret-mail-password')
            ->assertSuccessful();
    }

    public function test_unverified_transport_fails_closed(): void
    {
        config()->set('mail.default', 'failover');
        config()->set('mail.from.address', 'no-reply@example.test');

        $this->artisan('app:mail-check')
            ->expectsOutputToContain('has not been verified')
            ->assertFailed();
    }
}
