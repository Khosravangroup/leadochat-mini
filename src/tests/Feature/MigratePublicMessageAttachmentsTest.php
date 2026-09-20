<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigratePublicMessageAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_moves_public_attachment_to_private_storage_and_is_idempotent(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('message-attachments/legacy.pdf', 'legacy-pdf');

        $attachment = $this->createLegacyAttachment();

        $this->artisan('attachments:migrate-private')->assertSuccessful();

        $attachment->refresh();

        $this->assertSame('local', $attachment->meta['disk']);
        $this->assertNull($attachment->url);
        Storage::disk('local')->assertExists('message-attachments/legacy.pdf');
        Storage::disk('public')->assertMissing('message-attachments/legacy.pdf');
        $this->assertSame('legacy-pdf', Storage::disk('local')->get('message-attachments/legacy.pdf'));

        $this->artisan('attachments:migrate-private')->assertSuccessful();
        $this->assertDatabaseCount('message_attachments', 1);
    }

    public function test_dry_run_reports_candidate_without_changing_storage_or_metadata(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('message-attachments/legacy.pdf', 'legacy-pdf');

        $attachment = $this->createLegacyAttachment();

        $this
            ->artisan('attachments:migrate-private', ['--dry-run' => true])
            ->expectsOutputToContain('1 attachment')
            ->assertSuccessful();

        $attachment->refresh();

        $this->assertSame('public', $attachment->meta['disk']);
        $this->assertNotNull($attachment->url);
        Storage::disk('public')->assertExists('message-attachments/legacy.pdf');
        Storage::disk('local')->assertMissing('message-attachments/legacy.pdf');
    }

    public function test_missing_source_fails_without_changing_metadata(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $attachment = $this->createLegacyAttachment();

        $this
            ->artisan('attachments:migrate-private')
            ->expectsOutputToContain('1 failed')
            ->assertFailed();

        $attachment->refresh();

        $this->assertSame('public', $attachment->meta['disk']);
        $this->assertNotNull($attachment->url);
    }

    private function createLegacyAttachment(): MessageAttachment
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Legacy Attachment Workspace',
            'slug' => 'legacy-attachment-workspace-'.$user->id,
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'local',
            'provider_account_type' => 'test_account',
            'provider_account_id' => 'legacy-attachment-account-'.$workspace->id,
            'provider_account_name' => 'Legacy Attachment Account',
            'status' => 'connected',
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'local',
            'provider_conversation_id' => 'legacy-attachment-conversation-'.$workspace->id,
            'type' => 'direct',
            'title' => 'Legacy Attachment Customer',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'provider' => 'local',
            'provider_message_id' => 'legacy-attachment-message-'.$workspace->id,
            'direction' => 'outbound',
            'message_type' => 'file',
            'status' => 'sent',
            'sent_at' => now(),
            'received_at' => now(),
        ]);

        return MessageAttachment::create([
            'message_id' => $message->id,
            'attachment_type' => 'file',
            'url' => 'https://example.test/storage/message-attachments/legacy.pdf',
            'mime_type' => 'application/pdf',
            'file_name' => 'legacy.pdf',
            'file_size' => 10,
            'meta' => [
                'disk' => 'public',
                'path' => 'message-attachments/legacy.pdf',
            ],
        ]);
    }
}
