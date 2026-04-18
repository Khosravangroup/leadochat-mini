<?php

namespace App\Http\Controllers;

use App\Models\ProviderConnection;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\SocialStory;
use App\Services\Meta\Instagram\InstagramService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class SocialController extends Controller
{
    protected function buildInstagramPageData(Request $request, string $tab, string $pageTitle): array
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $instagramConnections = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->latest('id')
            ->get();

        $activeConnection = $instagramConnections->first();

        $postCount = SocialPost::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->count();

        $commentCount = SocialComment::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->count();

        $storyCount = SocialStory::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->count();

        return [
            'workspace' => $workspace,
            'platform' => 'instagram',
            'tab' => $tab,
            'pageTitle' => $pageTitle,
            'instagramConnections' => $instagramConnections,
            'activeInstagramConnection' => $activeConnection,
            'socialCounts' => [
                'posts' => $postCount,
                'comments' => $commentCount,
                'stories' => $storyCount,
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
            ->with([
                'mediaItems',
                'comments' => function ($query) {
                    $query->where('provider', 'instagram')
                        ->where('status', '!=', 'deleted')
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

    protected function resolveWorkspaceInstagramConnection(Request $request): ProviderConnection
    {
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $connection = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->latest('id')
            ->first();

        abort_unless($connection, 404, 'Connected Instagram account not found for the current workspace.');

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

    protected function redirectToInstagramPosts(): RedirectResponse
    {
        return redirect()->route('social.instagram.posts');
    }

    protected function redirectToInstagramStories(): RedirectResponse
    {
        return redirect()->route('social.instagram.stories');
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
        ];
    }

    protected function respondWithInstagramPosts(Request $request, ?string $successMessage = null, ?string $errorMessage = null): View|RedirectResponse
    {
        if (! $request->ajax()) {
            $response = $this->redirectToInstagramPosts();

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

    public function instagramPosts(Request $request): View
    {
        $pageData = $this->buildInstagramPageData(
            $request,
            'posts',
            'Social — Instagram Posts'
        );

        return view('social.instagram.index', $this->loadInstagramPostsData($request, $pageData));
    }

    public function instagramComments(Request $request): View
    {
        return view('social.instagram.index', $this->buildInstagramPageData(
            $request,
            'comments',
            'Social — Instagram Comments'
        ));
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

        $stories = SocialStory::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->latest('posted_at')
            ->latest('id')
            ->get();

        $pageData['stories'] = $stories;
        $pageData['storySyncError'] = null;
        $pageData['storyPublishEnabled'] = (bool) ($activeConnection && $activeConnection->status === 'connected');
        $pageData['socialCounts']['stories'] = $stories->count();

        return $pageData;
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

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'IMAGE';
        }

        if (in_array($extension, ['mp4', 'mov', 'm4v', 'webm'], true)) {
            return 'VIDEO';
        }

        return null;
    }

    public function publishInstagramStory(Request $request): RedirectResponse
    {
        $connection = $this->resolveWorkspaceInstagramConnection($request);
        $workspace = $request->user()?->currentWorkspace();

        abort_unless($workspace, 404);

        $validated = $request->validate([
            'media_type' => ['required', 'in:IMAGE,VIDEO'],
            'story_file' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm', 'max:51200'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:2200'],
        ]);

        $requestedMediaType = (string) $validated['media_type'];
        $uploadedFile = $request->file('story_file');
        $uploadedFileKind = $this->detectStoryFileKind($uploadedFile);
        $mediaUrlKind = $this->detectStoryUrlKind($validated['media_url'] ?? null);

        if (! $uploadedFile && empty($validated['media_url'])) {
            return redirect()->route('social.instagram.stories')
                ->with('social_error', 'Story file or media URL is required.')
                ->withInput();
        }

        if ($uploadedFile && $uploadedFileKind !== null && $uploadedFileKind !== $requestedMediaType) {
            return redirect()->route('social.instagram.stories')
                ->with('social_error', 'Selected story file type does not match the selected media type.')
                ->withInput();
        }

        if (! $uploadedFile && !empty($validated['media_url']) && $mediaUrlKind !== null && $mediaUrlKind !== $requestedMediaType) {
            return redirect()->route('social.instagram.stories')
                ->with('social_error', 'Media URL type does not match the selected media type.')
                ->withInput();
        }

        $resolvedMediaUrl = null;
        $storedPath = null;

        if ($request->hasFile('story_file')) {
            $storedPath = $request->file('story_file')->store('social/stories', 'public');
            $resolvedMediaUrl = Storage::disk('public')->url($storedPath);
        } elseif (!empty($validated['media_url'])) {
            $resolvedMediaUrl = $validated['media_url'];
        }

        if (! $resolvedMediaUrl) {
            return redirect()->route('social.instagram.stories')
                ->with('social_error', 'Story file or media URL is required.')
                ->withInput();
        }

        if (! $uploadedFile && !empty($validated['media_url']) && $mediaUrlKind === null) {
            return redirect()->route('social.instagram.stories')
                ->with('social_error', 'Media URL must end with a supported image or video extension.')
                ->withInput();
        }

        try {
            $result = app(InstagramService::class)->publishStory($connection, [
                'media_type' => $validated['media_type'],
                'media_url' => $resolvedMediaUrl,
                'caption' => $validated['caption'] ?? null,
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
            ]);
            $story->save();

            return redirect()->route('social.instagram.stories')
                ->with('social_success', 'Story published successfully.');
        } catch (\Throwable $exception) {
            return redirect()->route('social.instagram.stories')
                ->with('social_error', 'Publish story failed: ' . $exception->getMessage())
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

            return $this->redirectToInstagramStories()->with('social_success', 'Story record deleted successfully.');
        } catch (\Throwable $exception) {
            return $this->redirectToInstagramStories()->with('social_error', 'Delete story failed: ' . $exception->getMessage());
        }
    }

    public function replyInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveWorkspaceInstagramConnection($request);
        $replyText = trim((string) $request->input('reply_text', ''));

        if ($replyText === '') {
            return $this->respondWithInstagramPosts($request, null, 'Reply text is required.');
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

            return $this->respondWithInstagramPosts($request, 'Comment reply sent successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramPosts($request, null, 'Comment reply failed: ' . $exception->getMessage());
        }
    }

    public function replyInstagramCommentViaDm(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveWorkspaceInstagramConnection($request);
        $replyText = trim((string) $request->input('reply_text', ''));

        if ($replyText === '') {
            return $this->respondWithInstagramPosts($request, null, 'DM reply text is required.');
        }

        try {
            $sendResult = app(InstagramService::class)->replyToCommentViaDm($connection, $comment, $replyText, [
                'messaging_type' => 'RESPONSE',
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

            return $this->respondWithInstagramPosts($request, 'DM reply sent successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramPosts($request, null, 'DM reply failed: ' . $exception->getMessage());
        }
    }

    public function hideInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveWorkspaceInstagramConnection($request);

        try {
            app(InstagramService::class)->hideComment($connection, $comment->provider_comment_id);

            $comment->is_hidden = true;
            $comment->save();

            return $this->respondWithInstagramPosts($request, 'Comment hidden successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramPosts($request, null, 'Hide comment failed: ' . $exception->getMessage());
        }
    }

    public function unhideInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveWorkspaceInstagramConnection($request);

        try {
            app(InstagramService::class)->unhideComment($connection, $comment->provider_comment_id);

            $comment->is_hidden = false;
            $comment->save();

            return $this->respondWithInstagramPosts($request, 'Comment unhidden successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramPosts($request, null, 'Unhide comment failed: ' . $exception->getMessage());
        }
    }

    public function deleteInstagramComment(Request $request, SocialComment $comment): View|RedirectResponse
    {
        $comment = $this->resolveWorkspaceComment($request, $comment);
        $connection = $this->resolveWorkspaceInstagramConnection($request);

        try {
            app(InstagramService::class)->deleteComment($connection, $comment->provider_comment_id);

            $comment->status = 'deleted';
            $comment->save();

            return $this->respondWithInstagramPosts($request, 'Comment deleted successfully.');
        } catch (\Throwable $exception) {
            return $this->respondWithInstagramPosts($request, null, 'Delete comment failed: ' . $exception->getMessage());
        }
    }
}
