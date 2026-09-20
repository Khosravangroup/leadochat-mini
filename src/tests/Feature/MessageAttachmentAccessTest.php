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
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MessageAttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_workspace_user_can_stream_private_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('message-attachments/sample.pdf', 'private-pdf');

        [$user, $attachment] = $this->createPrivateAttachment();

        $response = $this
            ->actingAs($user)
            ->get(route('attachments.show', $attachment));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('cache-control', 'no-store, private');

        $this->assertSame(
            'private-pdf',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_anonymous_user_cannot_stream_private_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('message-attachments/sample.pdf', 'private-pdf');

        [, $attachment] = $this->createPrivateAttachment();

        $this
            ->get(route('attachments.show', $attachment))
            ->assertRedirect(route('login'));
    }

    public function test_private_attachment_supports_http_range_requests(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('message-attachments/sample.pdf', 'private-pdf');

        [$user, $attachment] = $this->createPrivateAttachment();

        $this
            ->actingAs($user)
            ->withHeader('Range', 'bytes=0-3')
            ->get(route('attachments.show', $attachment))
            ->assertStatus(206)
            ->assertHeader('content-range', 'bytes 0-3/11');
    }

    public function test_user_from_another_workspace_cannot_stream_private_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('message-attachments/sample.pdf', 'private-pdf');

        [, $attachment] = $this->createPrivateAttachment();
        [$otherUser] = $this->createWorkspaceUser('Other Workspace');

        $this
            ->actingAs($otherUser)
            ->get(route('attachments.show', $attachment))
            ->assertNotFound();
    }

    public function test_valid_temporary_provider_url_can_stream_private_attachment_without_authentication(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('message-attachments/sample.pdf', 'private-pdf');

        $url = URL::temporarySignedRoute(
            'attachments.provider',
            now()->addMinutes(5),
            ['path' => 'message-attachments/sample.pdf']
        );

        $response = $this
            ->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertSame(
            'private-pdf',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_tampered_or_expired_provider_url_is_rejected(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('message-attachments/sample.pdf', 'private-pdf');
        Storage::disk('local')->put('message-attachments/other.pdf', 'other-private-pdf');

        $url = URL::temporarySignedRoute(
            'attachments.provider',
            now()->addMinute(),
            ['path' => 'message-attachments/sample.pdf']
        );

        $this
            ->get(str_replace('sample.pdf', 'other.pdf', $url))
            ->assertForbidden();

        $this->travel(2)->minutes();

        $this
            ->get($url)
            ->assertForbidden();
    }

    public function test_remote_provider_attachment_url_remains_unchanged(): void
    {
        [, $attachment] = $this->createPrivateAttachment([
            'url' => 'https://provider.example/media/remote.jpg',
            'meta' => ['provider' => 'instagram'],
        ]);

        $this->assertSame(
            'https://provider.example/media/remote.jpg',
            $attachment->display_url
        );
    }

    /**
     * @return array{User, MessageAttachment}
     */
    private function createPrivateAttachment(array $overrides = []): array
    {
        [$user, $workspace, $connection] = $this->createWorkspaceUser('Attachment Workspace');

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'local',
            'provider_conversation_id' => 'attachment-access-conversation-'.$workspace->id,
            'type' => 'direct',
            'title' => 'Attachment Customer',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'provider' => 'local',
            'provider_message_id' => 'attachment-access-message-'.$workspace->id,
            'direction' => 'outbound',
            'message_type' => 'file',
            'status' => 'sent',
            'sent_at' => now(),
            'received_at' => now(),
        ]);

        $attachment = MessageAttachment::create(array_merge([
            'message_id' => $message->id,
            'attachment_type' => 'file',
            'url' => null,
            'mime_type' => 'application/pdf',
            'file_name' => 'sample.pdf',
            'file_size' => 11,
            'meta' => [
                'disk' => 'local',
                'path' => 'message-attachments/sample.pdf',
            ],
        ], $overrides));

        return [$user, $attachment];
    }

    /**
     * @return array{User, Workspace, ProviderConnection}
     */
    private function createWorkspaceUser(string $workspaceName): array
    {
        $user = User::factory()->create();

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => $workspaceName,
            'slug' => str($workspaceName)->slug().'-'.$user->id,
        ]);

        $workspace->members()->attach($user->id, ['role' => 'owner']);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'local',
            'provider_account_type' => 'test_account',
            'provider_account_id' => 'attachment-access-account-'.$workspace->id,
            'provider_account_name' => 'Attachment Access Account',
            'status' => 'connected',
        ]);

        return [$user, $workspace, $connection];
    }
}
