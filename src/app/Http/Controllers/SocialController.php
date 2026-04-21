<?php

namespace App\Http\Controllers;

use App\Events\WorkspaceRealtimeUpdated;
use App\Models\ProviderConnection;
use App\Models\Message;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\SocialStory;
use App\Services\Meta\Instagram\InstagramService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;


class SocialController extends Controller
{
    protected function buildInstagramPageData(Request $request, string $tab, string $pageTitle): array
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $instagramConnections = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->latest('id')
            ->get();

        $requestedConnectionId = $request->integer('instagram_account') ?: null;
        $activeConnection = $requestedConnectionId
            ? $instagramConnections->firstWhere('id', $requestedConnectionId)
            : null;
        $activeConnection ??= $instagramConnections->first();

        $postCountQuery = SocialPost::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', '!=', 'deleted');

        $commentCountQuery = SocialComment::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram');

        $storyCountQuery = SocialStory::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram');

        if ($activeConnection) {
            $postCountQuery->where('provider_connection_id', $activeConnection->id);
            $commentCountQuery->where('provider_connection_id', $activeConnection->id);
            $storyCountQuery->where('provider_connection_id', $activeConnection->id);
        }

        $instagramAccountTabs = $instagramConnections->map(function (ProviderConnection $connection) use ($workspace) {
            return [
                'id' => $connection->id,
                'name' => $connection->provider_account_name ?: ('Instagram account #' . $connection->id),
                'handle' => $connection->provider_account_name,
                'provider_account_id' => $connection->provider_account_id,
                'post_count' => SocialPost::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('provider', 'instagram')
                    ->where('provider_connection_id', $connection->id)
                    ->where('status', '!=', 'deleted')
                    ->count(),
                'comment_count' => SocialComment::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('provider', 'instagram')
                    ->where('provider_connection_id', $connection->id)
                    ->where('status', '!=', 'deleted')
                    ->count(),
                'story_count' => SocialStory::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('provider', 'instagram')
                    ->where('provider_connection_id', $connection->id)
                    ->where('status', '!=', 'deleted')
                    ->count(),
            ];
        })->values();

        $totalConnectedPosts = SocialPost::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->whereIn('provider_connection_id', $instagramConnections->pluck('id'))
            ->where('status', '!=', 'deleted')
            ->count();

        return [
            'workspace' => $workspace,
            'platform' => 'instagram',
            'tab' => $tab,
            'pageTitle' => $pageTitle,
            'instagramConnections' => $instagramConnections,
            'instagramAccountTabs' => $instagramAccountTabs,
            'activeInstagramConnection' => $activeConnection,
            'selectedInstagramAccountId' => $activeConnection?->id,
            'totalConnectedInstagramPosts' => $totalConnectedPosts,
            'socialCounts' => [
                'posts' => $postCountQuery->count(),
                'comments' => $commentCountQuery->where('status', '!=', 'deleted')->count(),
                'stories' => $storyCountQuery->count(),
            ],
        ];
    }

    protected function resolvePostCommentsPageSize(Request $request, int $postId, int $default = 10): int
    {
        $rawLimits = $request->query('comments_limit', []);

        if (!is_array($rawLimits)) {
            return $default;
        }

        $rawValue = $rawLimits[$postId] ?? $default;
        $limit = (int) $rawValue;

        if ($limit < 1) {
            return $default;
        }

        return min($limit, 100);
    }

    protected function buildPostCommentsPageSizes(Request $request, $posts, int $default = 10): array
    {
        $pageSizes = [];

        foreach ($posts as $post) {
            $pageSizes[$post->id] = $this->resolvePostCommentsPageSize($request, (int) $post->id, $default);
        }

        return $pageSizes;
    }

    protected function loadInstagramPostsData(Request $request, array $pageData): array
    {
        $activeConnection = $pageData['activeInstagramConnection'] ?? null;
        $workspace = $pageData['workspace'];

        $syncResult = null;
        $syncError = null;

        if ($activeConnection && $activeConnection->status === 'connected') {
            try {
                $syncResult = app(InstagramService::class)->syncMediaFeed($activeConnection, [
                    'limit' => 24,
                ]);
            } catch (\Throwable $exception) {
                $syncError = $exception->getMessage();
            }
        }

        $commentSyncErrors = [];
        $defaultCommentsPageSize = 10;

        $posts = SocialPost::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->when($activeConnection, fn ($query) => $query->where('provider_connection_id', $activeConnection->id))
            ->where('status', '!=', 'deleted')
            ->latest('posted_at')
            ->latest('id')
            ->get();

        if ($activeConnection && $activeConnection->status === 'connected') {
            foreach ($posts as $post) {
                try {
                    app(InstagramService::class)->syncMediaComments($activeConnection, $post, [
                        'limit' => 10,
                    ]);
                } catch (\Throwable $exception) {
                    $commentSyncErrors[$post->id] = $exception->getMessage();
                }
            }
        }

        $posts = SocialPost::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->when($activeConnection, fn ($query) => $query->where('provider_connection_id', $activeConnection->id))
            ->where('status', '!=', 'deleted')
            ->with([
                'mediaItems',
                'comments' => function ($query) {
                    $query->where('provider', 'instagram')
                        ->whereNull('parent_provider_comment_id')
                        ->where('status', '!=', 'deleted')
                        ->with([
                            'childComments' => function ($childQuery) {
                                $childQuery->where('provider', 'instagram')
                                    ->where('status', '!=', 'deleted')
                                    ->oldest('commented_at')
                                    ->oldest('id');
                            },
                        ])
                        ->latest('commented_at')
                        ->latest('id');
                },
            ])
            ->withCount([
                'comments as visible_comments_count' => function ($query) {
                    $query->where('provider', 'instagram')
                        ->where('status', '!=', 'deleted');
                },
            ])
            ->latest('posted_at')
            ->latest('id')
            ->get();

        $postCommentsPageSizes = $this->buildPostCommentsPageSizes($request, $posts, $defaultCommentsPageSize);

        $posts->each(function (SocialPost $post) use ($postCommentsPageSizes) {
            $limit = $postCommentsPageSizes[$post->id] ?? 10;
            $post->setRelation('comments', $post->comments->take($limit)->values());
        });

        $pageData['posts'] = $posts;
        $pageData['postCommentsPageSize'] = $defaultCommentsPageSize;
        $pageData['postCommentsPageSizes'] = $postCommentsPageSizes;
        $pageData['syncResult'] = $syncResult;
        $pageData['syncError'] = $syncError;
        $pageData['commentSyncErrors'] = $commentSyncErrors;
        $pageData['socialCounts']['posts'] = $posts->count();

        return $pageData;
    }

    protected function loadInstagramCommentsData(Request $request, array $pageData): array
    {
        $activeConnection = $pageData['activeInstagramConnection'] ?? null;
        $workspace = $pageData['workspace'];
        $syncResult = null;
        $syncError = null;
        $commentSyncErrors = [];

        if ($activeConnection && $activeConnection->status === 'connected') {
            try {
                $syncResult = app(InstagramService::class)->syncMediaFeed($activeConnection, [
                    'limit' => 24,
                ]);
            } catch (\Throwable $exception) {
                $syncError = $exception->getMessage();
            }

            $posts = SocialPost::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider', 'instagram')
                ->where('provider_connection_id', $activeConnection->id)
                ->where('status', '!=', 'deleted')
                ->latest('posted_at')
                ->latest('id')
                ->get();

            foreach ($posts as $post) {
                try {
                    app(InstagramService::class)->syncMediaComments($activeConnection, $post, [
                        'limit' => 50,
                    ]);
                } catch (\Throwable $exception) {
                    $commentSyncErrors[$post->id] = $exception->getMessage();
                }
            }
        }

        $comments = SocialComment::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->when($activeConnection, fn ($query) => $query->where('provider_connection_id', $activeConnection->id))
            ->whereNull('parent_provider_comment_id')
            ->where('status', '!=', 'deleted')
            ->with([
                'socialPost',
                'childComments' => function ($query) {
                    $query->where('provider', 'instagram')
                        ->where('status', '!=', 'deleted')
                        ->oldest('commented_at')
                        ->oldest('id');
                },
            ])
            ->latest('commented_at')
            ->latest('id')
            ->limit(100)
            ->get();

        $pageData['comments'] = $comments;
        $pageData['syncResult'] = $syncResult;
        $pageData['syncError'] = $syncError;
        $pageData['commentSyncErrors'] = $commentSyncErrors;
        $pageData['socialCounts']['comments'] = SocialComment::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->when($activeConnection, fn ($query) => $query->where('provider_connection_id', $activeConnection->id))
            ->where('status', '!=', 'deleted')
            ->count();

        return $pageData;
    }

    protected function resolveWorkspaceInstagramConnection(Request $request): ProviderConnection
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $connections = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->latest('id')
            ->get();

        $requestedConnectionId = $request->integer('instagram_account') ?: null;
        $connection = $requestedConnectionId
            ? $connections->firstWhere('id', $requestedConnectionId)
            : null;
        $connection ??= $connections->first();

        abort_unless($connection, 404, 'Connected Instagram account not found for the current workspace.');

        return $connection;
    }

    protected function resolveWorkspacePost(Request $request, SocialPost $post): SocialPost
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);
        abort_unless(
            $post->workspace_id === $workspace->id && $post->provider === 'instagram',
            404
        );

        return $post;
    }

    protected function resolveInstagramConnectionForPost(Request $request, SocialPost $post): ProviderConnection
    {
        $post = $this->resolveWorkspacePost($request, $post);

        $connection = ProviderConnection::query()
            ->where('workspace_id', $post->workspace_id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereKey($post->provider_connection_id)
            ->first();

        abort_unless($connection, 404, 'Connected Instagram account not found for this post.');

        return $connection;
    }

    protected function resolveWorkspaceComment(Request $request, SocialComment $comment): SocialComment
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);
        abort_unless(
            $comment->workspace_id === $workspace->id && $comment->provider === 'instagram',
            404
        );

        return $comment;
    }

    protected function resolveInstagramConnectionForComment(Request $request, SocialComment $comment): ProviderConnection
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);

        $connection = ProviderConnection::query()
            ->where('workspace_id', $comment->workspace_id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->whereKey($comment->provider_connection_id)
            ->first();

        abort_unless($connection, 404, 'Connected Instagram account not found for this comment.');

        return $connection;
    }

    protected function instagramAccountRouteParams(Request $request, array $extra = []): array
    {
        $accountId = $request->input('instagram_account', $request->query('instagram_account'));

        if ($accountId !== null && $accountId !== '') {
            $extra['instagram_account'] = (int) $accountId;
        }

        return $extra;
    }

    protected function redirectToInstagramPosts(Request $request): RedirectResponse
    {
        return redirect()->route('social.instagram.posts', $this->instagramAccountRouteParams($request));
    }

    protected function redirectToInstagramStories(Request $request): RedirectResponse
    {
        return redirect()->route('social.instagram.stories', $this->instagramAccountRouteParams($request));
    }
    protected function buildCommentReplyActorMeta(Request $request): array
    {
        $user = $request->user();
    
        $replyActorName = null;
    
        if ($user) {
            $candidateNames = [
                $user->name ?? null,
                $user->full_name ?? null,
                $user->display_name ?? null,
                $user->first_name ?? null,
                method_exists($user, 'getName') ? $user->getName() : null,
            ];
    
            foreach ($candidateNames as $candidateName) {
                $candidateName = is_string($candidateName) ? trim($candidateName) : null;
                if ($candidateName !== null && $candidateName !== '') {
                    $replyActorName = $candidateName;
                    break;
                }
            }
    
            if ($replyActorName === null) {
                $email = is_string($user->email ?? null) ? trim((string) $user->email) : null;
                if ($email !== null && $email !== '') {
                    $replyActorName = strstr($email, '@', true) ?: $email;
                }
            }
        }
    
        return [
            'reply_actor_id' => $user?->id,
            'reply_actor_name' => $replyActorName,
            'reply_actor_email' => $user?->email,
            'reply_actor_avatar_url' => $user?->avatar_url,
            'agent_user' => $user?->id ? [
                'id' => $user->id,
                'name' => $replyActorName ?: 'Agent',
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ] : null,
        ];
    }

    protected function respondWithInstagramPosts(Request $request, ?string $successMessage = null, ?string $errorMessage = null): View|RedirectResponse
    {
        if (! $request->ajax()) {
            $response = $this->redirectToInstagramPosts($request);

            if ($successMessage !== null) {
                $response = $response->with('social_success', $successMessage);
            }

            if ($errorMessage !== null) {
                $response = $response->with('social_error', $errorMessage);
            }

            return $response;
        }

        if ($successMessage !== null) {
            session()->flash('social_success', $successMessage);
        }

        if ($errorMessage !== null) {
            session()->flash('social_error', $errorMessage);
        }

        $pageData = $this->buildInstagramPageData(
            $request,
            'posts',
            'Social — Instagram Posts'
        );

        return view('social.instagram.index', $this->loadInstagramPostsData($request, $pageData));
    }

    protected function respondWithInstagramComments(Request $request, ?string $successMessage = null, ?string $errorMessage = null): View|RedirectResponse
    {
        if (! $request->ajax()) {
            $response = redirect()->route('social.instagram.comments', $this->instagramAccountRouteParams($request));

            if ($successMessage !== null) {
                $response = $response->with('social_success', $successMessage);
            }

            if ($errorMessage !== null) {
                $response = $response->with('social_error', $errorMessage);
            }

            return $response;
        }

        if ($successMessage !== null) {
            session()->flash('social_success', $successMessage);
        }

        if ($errorMessage !== null) {
            session()->flash('social_error', $errorMessage);
        }

        $pageData = $this->buildInstagramPageData(
            $request,
            'comments',
            'Social — Instagram Comments'
        );

        return view('social.instagram.index', $this->loadInstagramCommentsData($request, $pageData));
    }

    protected function respondWithInstagramAction(Request $request, ?string $successMessage = null, ?string $errorMessage = null): View|RedirectResponse
    {
        return $request->input('return_tab') === 'comments'
            ? $this->respondWithInstagramComments($request, $successMessage, $errorMessage)
            : $this->respondWithInstagramPosts($request, $successMessage, $errorMessage);
    }

    protected function broadcastSocialUpdate(Request $request, string $action, array $payload = []): void
    {
        $workspace = $request->user()?->currentWorkspace();

        if (! $workspace) {
            return;
        }

        try {
            event(new WorkspaceRealtimeUpdated((int) $workspace->id, 'social', $action, $payload));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function instagramPosts(Request $request): View
    {
        $pageData = $this->buildInstagramPageData(
            $request,
            'posts',
            'Social — Instagram Posts'
        );

        return view('social.instagram.index', $this->loadInstagramPostsData($request, $pageData));
    }

    public function instagramRealtimePosts(Request $request): JsonResponse
    {
        $pageData = $this->buildInstagramPageData($request, 'posts', 'Social — Instagram Posts');
        $activeConnection = $pageData['activeInstagramConnection'] ?? null;
        $workspace = $pageData['workspace'];
        $syncError = null;

        if ($activeConnection && $activeConnection->status === 'connected') {
            try {
                app(InstagramService::class)->syncMediaFeed($activeConnection, [
                    'limit' => 24,
                ]);
            } catch (\Throwable $exception) {
                $syncError = $exception->getMessage();
            }
        }

        $posts = SocialPost::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->when($activeConnection, fn ($query) => $query->where('provider_connection_id', $activeConnection->id))
            ->where('status', '!=', 'deleted')
            ->withCount([
                'comments as visible_comments_count' => function ($query) {
                    $query->where('provider', 'instagram')
                        ->where('status', '!=', 'deleted');
                },
            ])
            ->latest('posted_at')
            ->latest('id')
            ->limit(24)
            ->get();

        return response()->json([
            'ok' => $syncError === null,
            'sync_error' => $syncError,
            'instagram_account' => $activeConnection?->id,
            'posts' => $posts->map(fn (SocialPost $post) => [
                'id' => $post->id,
                'provider_media_id' => $post->provider_media_id,
                'like_count' => (int) $post->like_count,
                'comments_count' => (int) ($post->visible_comments_count ?? $post->comments_count),
                'status' => $post->status,
                'updated_at' => optional($post->updated_at)->toIso8601String(),
            ])->values(),
        ], $syncError === null ? 200 : 207);
    }

    public function instagramComments(Request $request): View
    {
        $pageData = $this->buildInstagramPageData(
            $request,
            'comments',
            'Social — Instagram Comments'
        );

        return view('social.instagram.index', $this->loadInstagramCommentsData($request, $pageData));
    }

    public function instagramStories(Request $request): View
    {
        $pageData = $this->buildInstagramPageData(
            $request,
            'stories',
            'Social — Instagram Stories'
        );

        return view('social.instagram.index', $this->loadInstagramStoriesData($request, $pageData));
    }

    protected function loadInstagramStoriesData(Request $request, array $pageData): array
    {
        $workspace = $pageData['workspace'];
        $activeConnection = $pageData['activeInstagramConnection'] ?? null;
        $syncResult = null;
        $syncError = null;

        if ($activeConnection && $activeConnection->status === 'connected') {
            try {
                $syncResult = app(InstagramService::class)->syncStories($activeConnection, [
                    'limit' => 25,
                ]);

                $this->storeSyncedStories($workspace, $activeConnection, $syncResult['stories'] ?? []);
            } catch (\Throwable $exception) {
                $syncError = $exception->getMessage();
            }
        }

        $stories = SocialStory::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->when($activeConnection, fn ($query) => $query->where('provider_connection_id', $activeConnection->id))
            ->where('status', '!=', 'deleted')
            ->latest('posted_at')
            ->latest('id')
            ->get();

        $pageData['stories'] = $stories;
        $pageData['storyEngagements'] = $this->buildStoryEngagements($workspace, $stories);
        $pageData['storySyncError'] = $syncError;
        $pageData['storySyncResult'] = $syncResult;
        $pageData['storyPublishEnabled'] = (bool) ($activeConnection && $activeConnection->status === 'connected');
        $pageData['socialCounts']['stories'] = $stories->count();

        return $pageData;
    }

    protected function buildStoryEngagements(object $workspace, $stories): array
    {
        $storyIds = $stories
            ->pluck('provider_story_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($storyIds->isEmpty()) {
            return [];
        }

        $messages = Message::query()
            ->where('messages.provider', 'instagram')
            ->where('messages.direction', 'inbound')
            ->whereHas('conversation', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->with('senderParticipant')
            ->latest('messages.created_at')
            ->limit(500)
            ->get()
            ->filter(function (Message $message) use ($storyIds) {
                $meta = is_array($message->meta) ? $message->meta : [];

                return in_array((string) ($meta['story_id'] ?? ''), $storyIds->all(), true);
            });

        $engagements = [];
        $likeEmojis = ['❤️', '❤', '😍', '🔥', '👏', '🙌', '👍'];

        foreach ($storyIds as $storyId) {
            $engagements[$storyId] = [
                'reply_count' => 0,
                'like_count' => 0,
                'likers' => [],
            ];
        }

        foreach ($messages as $message) {
            $meta = is_array($message->meta) ? $message->meta : [];
            $storyId = (string) ($meta['story_id'] ?? '');

            if ($storyId === '' || ! isset($engagements[$storyId])) {
                continue;
            }

            $engagements[$storyId]['reply_count']++;

            $text = trim((string) ($message->text_body ?? ''));
            $reaction = is_array($meta['reaction'] ?? null) ? $meta['reaction'] : [];
            $looksLikeLike = in_array($text, $likeEmojis, true)
                || in_array((string) ($reaction['emoji'] ?? ''), $likeEmojis, true)
                || in_array((string) ($reaction['reaction'] ?? ''), ['love', 'like'], true);

            if (! $looksLikeLike) {
                continue;
            }

            $participant = $message->senderParticipant;
            $actorKey = (string) ($participant?->provider_user_id ?? $participant?->id ?? $message->id);

            if (! isset($engagements[$storyId]['likers'][$actorKey])) {
                $engagements[$storyId]['likers'][$actorKey] = [
                    'name' => $participant?->display_name ?: $participant?->handle ?: 'Instagram user',
                    'handle' => $participant?->handle,
                    'avatar_url' => $participant?->avatar_url,
                ];
            }
        }

        foreach ($engagements as $storyId => $engagement) {
            $engagements[$storyId]['likers'] = array_values($engagement['likers']);
            $engagements[$storyId]['like_count'] = count($engagements[$storyId]['likers']);
        }

        return $engagements;
    }

    protected function storeSyncedStories(object $workspace, ProviderConnection $connection, array $remoteStories): void
    {
        foreach ($remoteStories as $remoteStory) {
            if (! is_array($remoteStory)) {
                continue;
            }

            $providerStoryId = trim((string) ($remoteStory['id'] ?? ''));

            if ($providerStoryId === '') {
                continue;
            }

            $postedAt = ! empty($remoteStory['timestamp'])
                ? \Illuminate\Support\Carbon::parse($remoteStory['timestamp'])
                : now();

            $story = SocialStory::query()->firstOrNew([
                'provider' => 'instagram',
                'provider_story_id' => $providerStoryId,
            ]);

            $story->workspace_id = $workspace->id;
            $story->provider_connection_id = $connection->id;
            $story->provider = 'instagram';
            $story->provider_story_id = $providerStoryId;
            $story->media_url = $remoteStory['media_url'] ?? $story->media_url;
            $story->thumbnail_url = $remoteStory['thumbnail_url'] ?? $story->thumbnail_url;
            $story->posted_at = $postedAt;
            $story->expires_at = $postedAt->copy()->addHours(24);
            $story->status = 'published';
            $story->raw = array_merge(is_array($story->raw) ? $story->raw : [], [
                'source' => 'instagram_stories_sync',
                'remote_story' => $remoteStory,
                'media_type' => $remoteStory['media_type'] ?? null,
                'permalink' => $remoteStory['permalink'] ?? null,
            ]);
            $story->save();
        }
    }

    protected function detectStoryFileKind(?\Illuminate\Http\UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $mime = strtolower((string) ($file->getMimeType() ?? ''));

        if (str_starts_with($mime, 'image/')) {
            return 'IMAGE';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'VIDEO';
        }

        return null;
    }

    protected function detectStoryUrlKind(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            return 'IMAGE';
        }

        if (in_array($extension, ['mp4', 'mov'], true)) {
            return 'VIDEO';
        }

        return null;
    }

    protected function validateStoryUploadForInstagram(?\Illuminate\Http\UploadedFile $file, string $mediaType): ?string
    {
        if (! $file) {
            return null;
        }

        $mime = strtolower((string) ($file->getMimeType() ?? ''));
        $size = (int) $file->getSize();

        if ($mediaType === 'IMAGE') {
            if ($size > 8 * 1024 * 1024) {
                return 'Instagram Story image source uploads must be 8 MB or smaller before conversion.';
            }

            return null;
        }

        if ($size > 100 * 1024 * 1024) {
            return 'Instagram Story video source uploads must be 100 MB or smaller before conversion.';
        }

        return null;
    }

    protected function prepareStoryUploadForInstagram(
        \Illuminate\Http\UploadedFile $file,
        string $mediaType,
        string $fit,
        float $zoom,
        float $offsetX,
        float $offsetY
    ): array {
        return $mediaType === 'VIDEO'
            ? $this->prepareStoryVideoUploadForInstagram($file, $fit)
            : $this->prepareStoryImageUploadForInstagram($file, $fit, $zoom, $offsetX, $offsetY);
    }

    protected function prepareStoryImageUploadForInstagram(
        \Illuminate\Http\UploadedFile $file,
        string $fit,
        float $zoom,
        float $offsetX,
        float $offsetY
    ): array {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source) {
            throw new \RuntimeException('Unsupported image file. Please upload a standard image file.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = 1080;
        $targetHeight = 1920;
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $background = imagecolorallocate($canvas, 0, 0, 0);
        imagefill($canvas, 0, 0, $background);

        $baseScale = $fit === 'contain'
            ? min($targetWidth / $sourceWidth, $targetHeight / $sourceHeight)
            : max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $scale = max(0.5, min($zoom, 2.5)) * $baseScale;
        $drawWidth = (int) round($sourceWidth * $scale);
        $drawHeight = (int) round($sourceHeight * $scale);
        $drawX = (int) round(($targetWidth - $drawWidth) / 2 + (($offsetX / 100) * ($targetWidth / 2)));
        $drawY = (int) round(($targetHeight - $drawHeight) / 2 + (($offsetY / 100) * ($targetHeight / 2)));

        imagecopyresampled($canvas, $source, $drawX, $drawY, 0, 0, $drawWidth, $drawHeight, $sourceWidth, $sourceHeight);

        $storedPath = 'social/stories/' . Str::uuid() . '.jpg';
        $absolutePath = Storage::disk('public')->path($storedPath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        imagejpeg($canvas, $absolutePath, 90);
        imagedestroy($source);
        imagedestroy($canvas);

        return [
            'stored_path' => $storedPath,
            'media_url' => url(Storage::url($storedPath)),
            'media_type' => 'IMAGE',
            'mime_type' => 'image/jpeg',
            'normalized' => true,
        ];
    }

    protected function prepareStoryVideoUploadForInstagram(\Illuminate\Http\UploadedFile $file, string $fit): array
    {
        $storedPath = 'social/stories/' . Str::uuid() . '.mp4';
        $absolutePath = Storage::disk('public')->path($storedPath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        $filter = $fit === 'contain'
            ? 'scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:black'
            : 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920';

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $file->getRealPath(),
            '-vf',
            $filter,
            '-c:v',
            'libx264',
            '-preset',
            'veryfast',
            '-pix_fmt',
            'yuv420p',
            '-c:a',
            'aac',
            '-movflags',
            '+faststart',
            $absolutePath,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Video conversion failed. Please try an MP4 or MOV file.');
        }

        return [
            'stored_path' => $storedPath,
            'media_url' => url(Storage::url($storedPath)),
            'media_type' => 'VIDEO',
            'mime_type' => 'video/mp4',
            'normalized' => true,
        ];
    }

    protected function storyResponsePayload(SocialStory $story): array
    {
        $raw = is_array($story->raw) ? $story->raw : [];

        return [
            'id' => $story->id,
            'provider_story_id' => $story->provider_story_id,
            'media_url' => $story->media_url,
            'thumbnail_url' => $story->thumbnail_url,
            'media_type' => $raw['media_type'] ?? $raw['remote_story']['media_type'] ?? null,
            'status' => $story->status,
            'posted_at' => optional($story->posted_at)->toIso8601String(),
            'expires_at' => optional($story->expires_at)->toIso8601String(),
            'permalink' => $raw['permalink'] ?? $raw['remote_story']['permalink'] ?? null,
        ];
    }

    public function publishInstagramStory(Request $request): RedirectResponse|JsonResponse
    {
        $connection = $this->resolveWorkspaceInstagramConnection($request);
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $validated = $request->validate([
            'media_type' => ['required', 'in:IMAGE,VIDEO'],
            'story_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-matroska', 'max:102400'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'story_fit' => ['nullable', 'string', 'in:cover,contain'],
            'story_zoom' => ['nullable', 'numeric', 'min:0.5', 'max:2.5'],
            'story_offset_x' => ['nullable', 'numeric', 'min:-100', 'max:100'],
            'story_offset_y' => ['nullable', 'numeric', 'min:-100', 'max:100'],
            'story_confirmed' => ['accepted'],
        ]);

        $requestedMediaType = (string) $validated['media_type'];
        $uploadedFile = $request->file('story_file');
        $uploadedFileKind = $this->detectStoryFileKind($uploadedFile);
        $mediaUrlKind = $this->detectStoryUrlKind($validated['media_url'] ?? null);
        $uploadValidationError = $this->validateStoryUploadForInstagram($uploadedFile, $requestedMediaType);
        $storyFit = (string) ($validated['story_fit'] ?? 'cover');
        $storyZoom = (float) ($validated['story_zoom'] ?? 1);
        $storyOffsetX = (float) ($validated['story_offset_x'] ?? 0);
        $storyOffsetY = (float) ($validated['story_offset_y'] ?? 0);

        if (! $uploadedFile && empty($validated['media_url'])) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Story file or media URL is required.'], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', 'Story file or media URL is required.')
                ->withInput();
        }

        if ($uploadValidationError !== null) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $uploadValidationError], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', $uploadValidationError)
                ->withInput();
        }

        if ($uploadedFile && $uploadedFileKind !== null && $uploadedFileKind !== $requestedMediaType) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Selected story file type does not match the selected media type.'], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', 'Selected story file type does not match the selected media type.')
                ->withInput();
        }

        if (! $uploadedFile && !empty($validated['media_url']) && $mediaUrlKind !== null && $mediaUrlKind !== $requestedMediaType) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Media URL type does not match the selected media type.'], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', 'Media URL type does not match the selected media type.')
                ->withInput();
        }

        $resolvedMediaUrl = null;
        $storedPath = null;

        if ($request->hasFile('story_file')) {
            $preparedUpload = $this->prepareStoryUploadForInstagram(
                $request->file('story_file'),
                $requestedMediaType,
                $storyFit,
                $storyZoom,
                $storyOffsetX,
                $storyOffsetY
            );
            $storedPath = $preparedUpload['stored_path'];
            $resolvedMediaUrl = $preparedUpload['media_url'];
        } elseif (!empty($validated['media_url'])) {
            $resolvedMediaUrl = $validated['media_url'];
        }

        if (! $resolvedMediaUrl) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Story file or media URL is required.'], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', 'Story file or media URL is required.')
                ->withInput();
        }

        if (! $uploadedFile && !empty($validated['media_url']) && $mediaUrlKind === null) {
            $message = 'Media URL must end with .jpg, .jpeg, .mp4, or .mov.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', $message)
                ->withInput();
        }

        try {
            $result = app(InstagramService::class)->publishStory($connection, [
                'media_type' => $validated['media_type'],
                'media_url' => $resolvedMediaUrl,
            ]);

            $providerStoryId = (string) ($result['id'] ?? '');
            if ($providerStoryId === '') {
                $providerStoryId = 'story-' . now()->timestamp;
            }

            $story = SocialStory::query()->firstOrNew([
                'provider' => 'instagram',
                'provider_story_id' => $providerStoryId,
            ]);

            $story->workspace_id = $workspace->id;
            $story->provider_connection_id = $connection->id;
            $story->provider = 'instagram';
            $story->provider_story_id = $providerStoryId;
            $story->media_url = $resolvedMediaUrl;
            $story->thumbnail_url = null;
            $story->posted_at = now();
            $story->expires_at = now()->addHours(24);
            $story->status = 'published';
            $story->raw = array_merge($result, [
                'uploaded_file_path' => $storedPath,
                'resolved_media_url' => $resolvedMediaUrl,
                'media_type' => $requestedMediaType,
                'published_from' => 'leadochat_social_stories',
                'preview_settings' => [
                    'fit' => $storyFit,
                    'zoom' => $storyZoom,
                    'offset_x' => $storyOffsetX,
                    'offset_y' => $storyOffsetY,
                ],
            ]);
            $story->save();

            $this->broadcastSocialUpdate($request, 'instagram_story_published', [
                'story_id' => $story->id,
                'provider_story_id' => $story->provider_story_id,
                'provider_connection_id' => $connection->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Story published successfully.',
                    'story' => $this->storyResponsePayload($story),
                    'publish_steps' => [
                        'container_id' => $result['creation_id'] ?? null,
                        'container_status' => $result['container_status'] ?? null,
                        'provider_story_id' => $story->provider_story_id,
                    ],
                ]);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_success', 'Story published successfully.');
        } catch (\Throwable $exception) {
            $message = 'Publish story failed: ' . $exception->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return $this->redirectToInstagramStories($request)
                ->with('social_error', $message)
                ->withInput();
        }
    }

    public function deleteInstagramStory(Request $request, SocialStory $story): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);
        abort_unless(
            $story->workspace_id === $workspace->id && $story->provider === 'instagram',
            404
        );

        try {
            $story->status = 'deleted';
            $story->save();

            return $this->redirectToInstagramStories($request)->with('social_success', 'Story record deleted successfully.');
        } catch (\Throwable $exception) {
            return $this->redirectToInstagramStories($request)->with('social_error', 'Delete story failed: ' . $exception->getMessage());
        }
    }

    public function deleteInstagramPost(Request $request, SocialPost $post): View|RedirectResponse
    {
        $post = $this->resolveWorkspacePost($request, $post);
        $connection = $this->resolveInstagramConnectionForPost($request, $post);

        try {
            $deleteResult = app(InstagramService::class)->deleteMedia($connection, $post->provider_media_id);

            $post->status = 'deleted';
            $post->raw = array_merge(
                is_array($post->raw) ? $post->raw : [],
                [
                    'deleted_from_social_at' => now()->toIso8601String(),
                    'delete_result' => $deleteResult,
                ]
            );
            $post->save();

            $this->broadcastSocialUpdate($request, 'instagram_post_deleted', [
                'social_post_id' => $post->id,
                'provider_media_id' => $post->provider_media_id,
                'provider_connection_id' => $post->provider_connection_id,
            ]);

            return $this->respondWithInstagramPosts($request, 'Post deleted successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramPosts($request, null, 'Delete post failed: ' . $exception->getMessage());
        }
    }

    public function replyInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveInstagramConnectionForComment($request, $comment);
        $replyText = trim((string) $request->input('reply_text', ''));

        if ($replyText === '') {
            return $this->respondWithInstagramAction($request, null, 'Reply text is required.');
        }

        try {
            app(InstagramService::class)->replyToComment($connection, $comment->provider_comment_id, $replyText);

            $comment->replied_publicly_at = now();
            $comment->raw = array_merge(
                is_array($comment->raw) ? $comment->raw : [],
                $this->buildCommentReplyActorMeta($request),
                [
                    'last_public_reply_text' => $replyText,
                    'last_public_reply_at' => now()->toIso8601String(),
                ]
            );
            $comment->save();
            $this->broadcastSocialUpdate($request, 'instagram_comment_replied', [
                'comment_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
            ]);

            return $this->respondWithInstagramAction($request, 'Comment reply sent successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramAction($request, null, 'Comment reply failed: ' . $exception->getMessage());
        }
    }

    public function replyInstagramCommentViaDm(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveInstagramConnectionForComment($request, $comment);
        $replyText = trim((string) $request->input('reply_text', ''));

        if ($replyText === '') {
            return $this->respondWithInstagramAction($request, null, 'DM reply text is required.');
        }

        try {
            $sendResult = app(InstagramService::class)->replyToCommentViaDm($connection, $comment, $replyText, [
                'messaging_type' => 'RESPONSE',
                'agent_meta' => $this->buildCommentReplyActorMeta($request),
            ]);

            $comment->replied_via_dm_at = now();
            $comment->raw = array_merge(
                is_array($comment->raw) ? $comment->raw : [],
                $this->buildCommentReplyActorMeta($request),
                [
                    'last_dm_reply_text' => $replyText,
                    'last_dm_reply_at' => now()->toIso8601String(),
                    'last_dm_reply_inbox_conversation_id' => $sendResult['inbox']['conversation_id'] ?? null,
                    'last_dm_reply_inbox_message_id' => $sendResult['inbox']['message_id'] ?? null,
                    'last_dm_reply_recipient_id' => $sendResult['inbox']['recipient_id'] ?? null,
                ]
            );
            $comment->save();
            $this->broadcastSocialUpdate($request, 'instagram_comment_replied_via_dm', [
                'comment_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
                'conversation_id' => $sendResult['inbox']['conversation_id'] ?? null,
                'message_id' => $sendResult['inbox']['message_id'] ?? null,
            ]);

            return $this->respondWithInstagramAction($request, 'DM reply sent successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramAction($request, null, 'DM reply failed: ' . $exception->getMessage());
        }
    }

    public function hideInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveInstagramConnectionForComment($request, $comment);

        try {
            app(InstagramService::class)->hideComment($connection, $comment->provider_comment_id);

            $comment->is_hidden = true;
            $comment->save();
            $this->broadcastSocialUpdate($request, 'instagram_comment_hidden', [
                'comment_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
            ]);

            return $this->respondWithInstagramAction($request, 'Comment hidden successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramAction($request, null, 'Hide comment failed: ' . $exception->getMessage());
        }
    }

    public function unhideInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveInstagramConnectionForComment($request, $comment);

        try {
            app(InstagramService::class)->unhideComment($connection, $comment->provider_comment_id);

            $comment->is_hidden = false;
            $comment->save();
            $this->broadcastSocialUpdate($request, 'instagram_comment_unhidden', [
                'comment_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
            ]);

            return $this->respondWithInstagramAction($request, 'Comment unhidden successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramAction($request, null, 'Unhide comment failed: ' . $exception->getMessage());
        }
    }

    public function deleteInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveInstagramConnectionForComment($request, $comment);

        try {
            app(InstagramService::class)->deleteComment($connection, $comment->provider_comment_id);

            $comment->status = 'deleted';
            $comment->save();
            $this->broadcastSocialUpdate($request, 'instagram_comment_deleted', [
                'comment_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
            ]);

            return $this->respondWithInstagramAction($request, 'Comment deleted successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramAction($request, null, 'Delete comment failed: ' . $exception->getMessage());
        }
    }
}
