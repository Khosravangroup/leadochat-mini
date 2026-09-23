<?php

namespace Tests\Unit;

use App\Models\OauthToken;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Meta\Instagram\InstagramCommentService;
use App\Services\Meta\Instagram\InstagramStoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class InstagramReviewApiJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_story_publish_uses_container_status_and_publish_endpoints(): void
    {
        config(['services.instagram.graph_version' => 'v25.0']);

        Http::fake([
            'https://graph.instagram.com/v25.0/review-account/media' => Http::response(['id' => 'story-container-123'], 200),
            'https://graph.instagram.com/v25.0/story-container-123*' => Http::response(['status_code' => 'FINISHED'], 200),
            'https://graph.instagram.com/v25.0/review-account/media_publish' => Http::response(['id' => 'story-123'], 200),
        ]);

        $result = app(InstagramStoryService::class)->publishStory($this->connection(), [
            'media_type' => 'IMAGE',
            'media_url' => 'https://example.test/story.jpg',
        ]);

        $this->assertSame('story-123', $result['id']);
        $this->assertSame('story-container-123', $result['creation_id']);
        $this->assertSame('FINISHED', $result['container_status']);
        $this->assertSame(1, $result['container_status_attempts']);
        Http::assertSentCount(3);

        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'POST' || $request->url() !== 'https://graph.instagram.com/v25.0/review-account/media') {
                return false;
            }

            parse_str($request->body(), $body);

            return ($body['media_type'] ?? null) === 'STORIES'
                && ($body['image_url'] ?? null) === 'https://example.test/story.jpg'
                && ($body['access_token'] ?? null) === 'synthetic-review-token';
        });

        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'POST' || $request->url() !== 'https://graph.instagram.com/v25.0/review-account/media_publish') {
                return false;
            }

            parse_str($request->body(), $body);

            return ($body['creation_id'] ?? null) === 'story-container-123';
        });
    }

    public function test_comment_reply_and_hide_call_the_provider(): void
    {
        config(['services.instagram.graph_version' => 'v25.0']);

        Http::fake([
            'https://graph.instagram.com/v25.0/comment-123/replies' => Http::response(['id' => 'reply-123'], 200),
            'https://graph.instagram.com/v25.0/comment-123' => Http::response(['success' => true], 200),
        ]);

        $connection = $this->connection();
        $reply = app(InstagramCommentService::class)->replyToComment($connection, 'comment-123', '  Thanks!  ');
        $hidden = app(InstagramCommentService::class)->hideComment($connection, 'comment-123');

        $this->assertSame('reply-123', $reply['id']);
        $this->assertTrue($hidden['success']);
        Http::assertSentCount(2);

        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'POST' || $request->url() !== 'https://graph.instagram.com/v25.0/comment-123/replies') {
                return false;
            }

            parse_str($request->body(), $body);

            return ($body['message'] ?? null) === 'Thanks!'
                && ($body['access_token'] ?? null) === 'synthetic-review-token';
        });

        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'POST' || $request->url() !== 'https://graph.instagram.com/v25.0/comment-123') {
                return false;
            }

            parse_str($request->body(), $body);

            return ($body['hide'] ?? null) === 'true';
        });
    }

    public function test_provider_error_does_not_expose_the_access_token(): void
    {
        config(['services.instagram.graph_version' => 'v25.0']);

        Http::fake([
            'https://graph.instagram.com/v25.0/comment-123/replies' => Http::response([
                'error' => ['message' => 'Rejected synthetic-review-token'],
            ], 400),
        ]);

        try {
            app(InstagramCommentService::class)->replyToComment($this->connection(), 'comment-123', 'Thanks!');
            $this->fail('The provider error should stop the comment reply.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('synthetic-review-token', $exception->getMessage());
            $this->assertStringContainsString('[redacted]', $exception->getMessage());
        }
    }

    private function connection(): ProviderConnection
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create([
            'owner_id' => $owner->id,
            'name' => 'Review API Journey Workspace',
            'slug' => 'review-api-journey-workspace',
        ]);

        $connection = ProviderConnection::create([
            'workspace_id' => $workspace->id,
            'provider' => 'instagram',
            'provider_account_type' => 'instagram_account',
            'provider_account_id' => 'review-account',
            'provider_account_name' => 'review_account',
            'status' => 'connected',
        ]);

        OauthToken::create([
            'provider_connection_id' => $connection->id,
            'token_type' => 'access_token',
            'access_token' => 'synthetic-review-token',
            'is_primary' => true,
        ]);

        return $connection;
    }
}
