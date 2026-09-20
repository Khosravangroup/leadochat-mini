<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboxAttachmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const JFIF_FIXTURE = '/9j/4AAQSkZJRgABAgAAAQABAAD//gAQTGF2YzYyLjI4LjEwMAD/2wBDAAgEBAQEBAUFBQUFBQYGBgYGBgYGBgYGBgYHBwcICAgHBwcGBgcHCAgICAkJCQgICAgJCQoKCgwMCwsODg4RERT/xABLAAEBAAAAAAAAAAAAAAAAAAAABwEBAAAAAAAAAAAAAAAAAAAAABABAAAAAAAAAAAAAAAAAAAAABEBAAAAAAAAAAAAAAAAAAAAAP/AABEIAAIAAgMBIgACEQADEQD/2gAMAwEAAhEDEQA/AL+AD//Z';

    private const OPUS_FIXTURE = 'T2dnUwACAAAAAAAAAAC6TwFSAAAAAPBQy3IBE09wdXNIZWFkAQE4AYC7AAAAAABPZ2dTAAAAAAAAAAAAALpPAVIBAAAA4o2fjQE+T3B1c1RhZ3MNAAAATGF2ZjYyLjEyLjEwMAEAAAAdAAAAZW5jb2Rlcj1MYXZjNjIuMjguMTAwIGxpYm9wdXNPZ2dTAASYCgAAAAAAALpPAVICAAAAmE9meAMDAwP4//74//74//4=';

    private const AAC_FIXTURE = '//FMQAOf/N4CAExhdmM2Mi4yOC4xMDAAAjBADv/xTEABf/wBGCAH//FMQAF//AEYIAf/8UxAAX/8ARggBw==';

    public function test_executable_php_attachment_is_rejected_before_storage(): void
    {
        Storage::fake('public');

        [$user, $conversation] = $this->createLocalConversation();
        [$upload, $temporaryPath] = $this->createRealUpload(
            'invoice.php',
            '<?php echo "unsafe";'
        );

        try {
            $response = $this
                ->actingAs($user)
                ->postJson(route('inbox.messages.store', $conversation), [
                    'attachment_files' => [$upload],
                ]);
        } finally {
            @unlink($temporaryPath);
        }

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment_files.0');

        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('message_attachments', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('message-attachments'));
    }

    public function test_php_content_with_double_extension_is_rejected_before_storage(): void
    {
        Storage::fake('public');

        [$user, $conversation] = $this->createLocalConversation();
        [$upload, $temporaryPath] = $this->createRealUpload(
            'invoice.php.jpg',
            '<?php echo "unsafe";'
        );

        try {
            $response = $this
                ->actingAs($user)
                ->postJson(route('inbox.messages.store', $conversation), [
                    'attachment_files' => [$upload],
                ]);
        } finally {
            @unlink($temporaryPath);
        }

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment_files.0');

        $this->assertDatabaseCount('message_attachments', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('message-attachments'));
    }

    public function test_php_content_renamed_as_an_allowed_image_is_rejected_before_storage(): void
    {
        Storage::fake('public');

        [$user, $conversation] = $this->createLocalConversation();
        [$upload, $temporaryPath] = $this->createRealUpload(
            'invoice.jpg',
            '<?php echo "unsafe";'
        );

        try {
            $response = $this
                ->actingAs($user)
                ->postJson(route('inbox.messages.store', $conversation), [
                    'attachment_files' => [$upload],
                ]);
        } finally {
            @unlink($temporaryPath);
        }

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment_files.0');

        $this->assertDatabaseCount('message_attachments', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('message-attachments'));
    }

    public function test_allowed_pdf_attachment_is_stored_and_recorded(): void
    {
        Storage::fake('public');

        [$user, $conversation] = $this->createLocalConversation();

        $response = $this
            ->actingAs($user)
            ->postJson(route('inbox.messages.store', $conversation), [
                'attachment_files' => [
                    UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'),
                ],
            ]);

        $response->assertOk();

        $attachment = MessageAttachment::query()->sole();

        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame('brief.pdf', $attachment->file_name);
        Storage::disk('public')->assertExists($attachment->meta['path']);
    }

    public function test_allowed_image_video_and_audio_aliases_are_stored_and_recorded(): void
    {
        Storage::fake('public');

        [$user, $conversation] = $this->createLocalConversation();
        [$image, $imagePath] = $this->createRealUpload('image.jfif', $this->decodeFixture(self::JFIF_FIXTURE));
        [$audio, $audioPath] = $this->createRealUpload('tone.opus', $this->decodeFixture(self::OPUS_FIXTURE));
        [$aac, $aacPath] = $this->createRealUpload('tone.aac', $this->decodeFixture(self::AAC_FIXTURE));

        try {
            $response = $this
                ->actingAs($user)
                ->postJson(route('inbox.messages.store', $conversation), [
                    'attachment_files' => [
                        $image,
                        UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
                        $audio,
                        $aac,
                    ],
                ]);
        } finally {
            @unlink($imagePath);
            @unlink($audioPath);
            @unlink($aacPath);
        }

        $response->assertOk();

        $attachments = MessageAttachment::query()->orderBy('id')->get();

        $this->assertSame(['image.jfif', 'clip.mp4', 'tone.opus', 'tone.aac'], $attachments->pluck('file_name')->all());
        $this->assertSame(['image/jpeg', 'video/mp4', 'audio/ogg', 'audio/x-hx-aac-adts'], $attachments->pluck('mime_type')->all());

        foreach ($attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment->meta['path']);
        }
    }

    public function test_nginx_storage_locations_never_dispatch_to_php_fpm(): void
    {
        foreach (['default.conf', 'default.prod.conf'] as $configuration) {
            $contents = file_get_contents(base_path('../docker/nginx/'.$configuration));

            $this->assertIsString($contents);
            $this->assertMatchesRegularExpression(
                '/location\s+~\*\s+\^\/storage\/\.\*\\\.\(\?:php\[0-9\]\?\|phtml\?\|phar\).*return\s+404;/s',
                $contents,
                $configuration.' must deny executable script extensions under public storage.'
            );
        }
    }

    /**
     * @return array{UploadedFile, string}
     */
    private function createRealUpload(string $originalName, string $contents): array
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'leadochat-upload-');

        if ($temporaryPath === false) {
            $this->fail('Unable to create the temporary upload fixture.');
        }

        file_put_contents($temporaryPath, $contents);

        return [
            new UploadedFile($temporaryPath, $originalName, null, null, true),
            $temporaryPath,
        ];
    }

    private function decodeFixture(string $fixture): string
    {
        $contents = base64_decode($fixture, true);

        if ($contents === false) {
            $this->fail('Unable to decode the binary upload fixture.');
        }

        return $contents;
    }

    /**
     * @return array{User, Conversation}
     */
    private function createLocalConversation(): array
    {
        $user = User::factory()->create();

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => 'Attachment Security Workspace',
            'slug' => 'attachment-security-workspace',
        ]);

        $workspace->members()->attach($user->id, [
            'role' => 'owner',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'local',
            'provider_account_type' => 'test_account',
            'provider_account_id' => 'local-attachment-account',
            'provider_account_name' => 'Local Attachment Account',
            'status' => 'connected',
        ]);

        $conversation = Conversation::create([
            'workspace_id' => $workspace->id,
            'provider_connection_id' => $connection->id,
            'provider' => 'local',
            'provider_conversation_id' => 'local-attachment-conversation',
            'type' => 'direct',
            'title' => 'Attachment Customer',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $conversation->participants()->create([
            'provider_user_id' => 'local-attachment-account',
            'display_name' => 'Local Attachment Account',
            'role' => 'business',
            'is_self' => true,
        ]);

        return [$user, $conversation];
    }
}
