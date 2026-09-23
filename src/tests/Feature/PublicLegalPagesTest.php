<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    public function test_public_legal_pages_use_one_contact_identity_and_deletion_timeline(): void
    {
        foreach (['privacy-policy', 'policies-and-procedures', 'data-deletion', 'contact'] as $routeName) {
            $response = $this->get(route($routeName));

            $response->assertOk()
                ->assertSee('hello@leadochat.com')
                ->assertDontSee('Global Education LTD')
                ->assertDontSee('Moscow')
                ->assertDontSee('academic MVP')
                ->assertDontSee('reasonable timeframe')
                ->assertDontSee('info@leadochat.com');
        }

        $this->get(route('privacy-policy'))
            ->assertSee('within 7 days')
            ->assertSee('71-75 Shelton Street');

        $this->get(route('data-deletion'))
            ->assertSee('within 7 days')
            ->assertSee('connected Instagram account');
    }
}
