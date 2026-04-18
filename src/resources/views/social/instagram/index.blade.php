<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
                <h2 style="font-size:22px; font-weight:800; color:#0f172a; margin:0;">
                    {{ $pageTitle ?? 'Social' }}
                </h2>
                <div style="margin-top:6px; font-size:13px; color:#64748b;">
                    Manage Instagram posts, comments, stories, and social actions from one dedicated workspace area.
                </div>
            </div>
        </div>
    </x-slot>

    <div style="padding:24px;">
        <style>
            .social-shell {
                display: grid;
                gap: 16px;
            }

            .social-top-card,
            .social-tab-card,
            .social-content-card {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                background: #fff;
                padding: 16px;
            }

            .social-stats {
                margin-top: 14px;
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 12px;
            }

            .social-stat {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #f8fafc;
                padding: 14px;
            }

            .social-stat-label {
                font-size: 12px;
                font-weight: 700;
                color: #64748b;
            }

            .social-stat-value {
                margin-top: 6px;
                font-size: 20px;
                font-weight: 800;
                color: #0f172a;
            }

            .social-connection-summary {
                margin-top: 14px;
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            .social-connection-badge {
                display: inline-flex;
                align-items: center;
                min-height: 30px;
                border-radius: 999px;
                padding: 0 12px;
                font-size: 12px;
                font-weight: 800;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
            }

            .social-connection-badge.connected {
                background: #ecfdf5;
                color: #166534;
                border-color: #86efac;
            }

            .social-connection-badge.missing {
                background: #fff7ed;
                color: #c2410c;
                border-color: #fdba74;
            }

            .social-tabs {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            .social-tab-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 40px;
                border-radius: 12px;
                padding: 0 14px;
                border: 1px solid #e2e8f0;
                background: #fff;
                color: #334155;
                font-size: 13px;
                font-weight: 800;
                text-decoration: none;
            }

            .social-tab-link.is-active {
                background: #eef2ff;
                color: #4338ca;
                border-color: #c7d2fe;
            }

            .social-section-title {
                font-size: 16px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 10px;
            }

            .social-placeholder-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .social-placeholder-box {
                border: 1px dashed #cbd5e1;
                border-radius: 14px;
                background: #f8fafc;
                padding: 16px;
            }

            .social-placeholder-label {
                font-size: 13px;
                font-weight: 800;
                color: #334155;
                margin-bottom: 8px;
            }

            .social-placeholder-text {
                font-size: 13px;
                line-height: 1.7;
                color: #64748b;
            }

            .social-sync-banner {
                margin-bottom: 14px;
                border-radius: 14px;
                padding: 14px 16px;
                font-size: 13px;
                line-height: 1.7;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
            }

            .social-sync-banner.success {
                background: #ecfdf5;
                border-color: #86efac;
                color: #166534;
            }

            .social-sync-banner.error {
                background: #fef2f2;
                border-color: #fca5a5;
                color: #991b1b;
            }

            .social-post-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .social-post-card {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                background: #fff;
                overflow: hidden;
            }

            .social-post-media {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 180px;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                font-size: 13px;
                font-weight: 800;
                color: #64748b;
                text-transform: uppercase;
            }

            .social-post-image {
                display: block;
                width: 100%;
                max-height: 260px;
                object-fit: cover;
                background: #f8fafc;
            }

            .social-post-media-frame {
                position: relative;
                width: 100%;
            }

            .social-post-media-type-badge {
                position: absolute;
                top: 12px;
                left: 12px;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                border-radius: 999px;
                padding: 0 10px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.02em;
                border: 1px solid rgba(15, 23, 42, 0.12);
                background: rgba(255, 255, 255, 0.92);
                color: #0f172a;
                backdrop-filter: blur(6px);
            }

            .social-post-media-type-badge.video {
                background: rgba(239, 246, 255, 0.94);
                color: #1d4ed8;
                border-color: rgba(96, 165, 250, 0.7);
            }

            .social-post-media-type-badge.reel {
                background: rgba(250, 245, 255, 0.94);
                color: #7c3aed;
                border-color: rgba(196, 181, 253, 0.9);
            }

            .social-post-media-type-badge.carousel {
                background: rgba(255, 247, 237, 0.94);
                color: #c2410c;
                border-color: rgba(253, 186, 116, 0.9);
            }

            .social-post-media-type-badge.image {
                background: rgba(240, 253, 244, 0.94);
                color: #166534;
                border-color: rgba(134, 239, 172, 0.9);
            }

            .social-post-fallback {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 220px;
                padding: 24px;
                text-align: center;
                font-size: 13px;
                font-weight: 800;
                color: #475569;
                background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            }

            .social-post-video-indicator {
                position: absolute;
                right: 12px;
                bottom: 12px;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                min-height: 30px;
                border-radius: 999px;
                padding: 0 10px;
                font-size: 11px;
                font-weight: 800;
                background: rgba(15, 23, 42, 0.82);
                color: #fff;
            }

            .social-post-carousel-count {
                position: absolute;
                right: 12px;
                bottom: 12px;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                min-height: 30px;
                border-radius: 999px;
                padding: 0 10px;
                font-size: 11px;
                font-weight: 800;
                background: rgba(15, 23, 42, 0.82);
                color: #fff;
            }

            .social-post-media-stack {
                display: grid;
                gap: 0;
                width: 100%;
            }

            .social-post-carousel-strip {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 6px;
                padding: 8px;
                border-top: 1px solid #e2e8f0;
                background: #fff;
            }

            .social-post-carousel-item {
                min-height: 62px;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 800;
                color: #475569;
                text-transform: uppercase;
                overflow: hidden;
            }

            .social-post-carousel-thumb {
                display: block;
                width: 100%;
                height: 62px;
                object-fit: cover;
                background: #f8fafc;
            }

            .social-post-body {
                padding: 14px;
                display: grid;
                gap: 10px;
            }

            .social-post-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                flex-wrap: wrap;
            }

            .social-post-badge {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                border-radius: 999px;
                padding: 0 10px;
                font-size: 11px;
                font-weight: 800;
                border: 1px solid #cbd5e1;
                background: #f8fafc;
                color: #334155;
            }

            .social-post-caption {
                font-size: 13px;
                line-height: 1.7;
                color: #334155;
            }

            .social-post-stats {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .social-post-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 36px;
                border-radius: 10px;
                padding: 0 12px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #334155;
                font-size: 12px;
                font-weight: 800;
                text-decoration: none;
                width: fit-content;
            }

            .social-post-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            .social-post-action-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 36px;
                border-radius: 10px;
                padding: 0 12px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #334155;
                font-size: 12px;
                font-weight: 800;
                text-decoration: none;
                cursor: pointer;
            }

            .social-post-action-button:hover {
                background: #f8fafc;
            }

            .social-comments-panel {
                margin-top: 12px;
                border-top: 1px solid #e2e8f0;
                padding-top: 12px;
                display: grid;
                gap: 10px;
            }

            .social-comments-summary {
                font-size: 12px;
                color: #64748b;
                line-height: 1.7;
            }

            .social-comment-list {
                display: grid;
                gap: 10px;
                max-height: 255px;
                overflow-y: auto;
                padding-right: 4px;
            }

            .social-comment-card {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                padding: 10px;
                display: grid;
                gap: 6px;
            }

            .social-comment-top {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: nowrap;
                min-width: 0;
            }

            .social-comment-main {
                display: flex;
                align-items: center;
                gap: 8px;
                min-width: 0;
                flex: 1 1 auto;
            }

            .social-comment-avatar {
                width: 26px;
                height: 26px;
                border-radius: 999px;
                background: #e2e8f0;
                color: #334155;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 800;
                flex: 0 0 auto;
                text-transform: uppercase;
            }

            .social-comment-author {
                font-size: 11px;
                font-weight: 800;
                color: #0f172a;
                flex: 0 0 auto;
            }

            .social-comment-date {
                font-size: 10px;
                font-weight: 700;
                color: #64748b;
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .social-comment-text {
                font-size: 11px;
                line-height: 1.4;
                color: #334155;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                min-width: 0;
                flex: 1 1 auto;
            }

            .social-comment-stats {
                display: none;
            }

            .social-comment-reply-note {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
                font-size: 12px;
                line-height: 1.5;
                color: #64748b;
                padding-top: 2px;
            }

            .social-comment-reply-note strong {
                color: #334155;
                font-weight: 800;
            }

            .social-comment-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .social-comment-reply-form {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-top: 2px;
                flex-wrap: wrap;
            }

            .social-comment-reply-textarea {
                flex: 1 1 220px;
                width: 100%;
                min-height: 36px;
                height: 36px;
                max-height: 36px;
                border: 1px solid #cbd5e1;
                border-radius: 9px;
                padding: 7px 10px;
                font-size: 12px;
                line-height: 20px;
                color: #334155;
                background: #fff;
                resize: none;
                box-sizing: border-box;
                overflow: hidden;
            }

            .social-comment-reply-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .social-comment-action-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 30px;
                border-radius: 8px;
                padding: 0 9px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #334155;
                font-size: 11px;
                font-weight: 800;
                cursor: pointer;
            }
            .social-inline-loading {
                opacity: 0.6;
                pointer-events: none;
            }

            .social-comment-action-button[disabled],
            .social-post-action-button[disabled] {
                opacity: 0.55;
                cursor: not-allowed;
            }

            .social-comments-empty {
                border: 1px dashed #cbd5e1;
                border-radius: 12px;
                background: #fff;
                padding: 12px;
                font-size: 12px;
                line-height: 1.7;
                color: #64748b;
            }

            .social-story-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
            }

            .social-story-card {
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #fff;
                overflow: hidden;
            }

            .social-story-media {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 220px;
                background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
                border-bottom: 1px solid #e2e8f0;
            }

            .social-story-image {
                display: block;
                width: 100%;
                height: 220px;
                object-fit: cover;
                background: #f8fafc;
            }

            .social-story-fallback {
                padding: 18px;
                text-align: center;
                font-size: 12px;
                font-weight: 800;
                color: #64748b;
            }

            .social-story-body {
                padding: 12px;
                display: grid;
                gap: 8px;
            }

            .social-story-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                flex-wrap: wrap;
            }

            .social-story-status-badge.published {
                background: #ecfdf5;
                color: #166534;
                border-color: #86efac;
            }

            .social-story-status-badge.deleted {
                background: #fef2f2;
                color: #991b1b;
                border-color: #fca5a5;
            }

            .social-story-status-badge.failed {
                background: #fff7ed;
                color: #c2410c;
                border-color: #fdba74;
            }

            .social-story-status-badge.draft {
                background: #eff6ff;
                color: #1d4ed8;
                border-color: #93c5fd;
            }

            .social-story-form {
                display: grid;
                gap: 12px;
            }

            .social-story-form-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .social-story-field {
                display: grid;
                gap: 6px;
            }

            .social-story-label {
                font-size: 12px;
                font-weight: 800;
                color: #334155;
            }

            .social-story-input,
            .social-story-select,
            .social-story-file {
                width: 100%;
                min-height: 40px;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 0 12px;
                font-size: 13px;
                color: #334155;
                background: #fff;
                box-sizing: border-box;
            }

            .social-story-file {
                padding: 8px 12px;
            }

            .social-story-preview-box {
                border: 1px dashed #cbd5e1;
                border-radius: 12px;
                background: #f8fafc;
                min-height: 240px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                position: relative;
            }

            .social-story-preview-empty {
                padding: 18px;
                text-align: center;
                font-size: 12px;
                line-height: 1.7;
                color: #64748b;
                font-weight: 700;
            }
            .social-story-preview-image,
.social-story-preview-video {
    display: block;
    width: 100%;
    max-height: 320px;
    object-fit: cover;
    background: #f8fafc;
}

.social-story-preview-image[hidden],
.social-story-preview-video[hidden],
.social-story-preview-empty[hidden] {
    display: none !important;
}
            }

            .social-story-help {
                font-size: 12px;
                line-height: 1.7;
                color: #64748b;
            }

            .social-story-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            @media (max-width: 960px) {
                .social-story-grid,
                .social-story-form-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 960px) {
                .social-stats,
                .social-placeholder-grid,
                .social-post-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="social-shell">
            <div class="social-top-card">
                <div style="font-size:16px; font-weight:800; color:#0f172a;">
                    Instagram Social Console
                </div>
                <div style="margin-top:6px; font-size:13px; line-height:1.7; color:#64748b;">
                    This module is dedicated to social content management and stays separate from Inbox. Posts, comments, stories, moderation, and reply-via-DM will live here.
                </div>

                <div class="social-stats">
                    <div class="social-stat">
                        <div class="social-stat-label">Workspace</div>
                        <div class="social-stat-value">{{ $workspace->name }}</div>
                    </div>

                    <div class="social-stat">
                        <div class="social-stat-label">Platform</div>
                        <div class="social-stat-value">Instagram</div>
                    </div>

                    <div class="social-stat">
                        <div class="social-stat-label">Current tab</div>
                        <div class="social-stat-value" style="text-transform:capitalize;">{{ $tab }}</div>
                    </div>

                    <div class="social-stat">
                        <div class="social-stat-label">Connected accounts</div>
                        <div class="social-stat-value">{{ $instagramConnections->count() }}</div>
                    </div>

                    <div class="social-stat">
                        <div class="social-stat-label">Status</div>
                        <div class="social-stat-value">{{ $activeInstagramConnection ? 'Ready' : 'Needs connection' }}</div>
                    </div>
                </div>

                <div class="social-connection-summary">
                    <span class="social-connection-badge {{ $activeInstagramConnection ? 'connected' : 'missing' }}">
                        {{ $activeInstagramConnection ? 'Instagram connected' : 'Instagram connection missing' }}
                    </span>

                    @if ($activeInstagramConnection)
                        <span class="social-connection-badge">
                            Account: {{ $activeInstagramConnection->provider_account_name ?: 'Connected account' }}
                        </span>

                        <span class="social-connection-badge">
                            Status: {{ $activeInstagramConnection->status }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="social-tab-card">
                <div class="social-tabs">
                    <a
                        href="{{ route('social.instagram.posts') }}"
                        class="social-tab-link {{ $tab === 'posts' ? 'is-active' : '' }}"
                    >
                        Posts
                    </a>

                    <a
                        href="{{ route('social.instagram.comments') }}"
                        class="social-tab-link {{ $tab === 'comments' ? 'is-active' : '' }}"
                    >
                        Comments
                    </a>

                    <a
                        href="{{ route('social.instagram.stories') }}"
                        class="social-tab-link {{ $tab === 'stories' ? 'is-active' : '' }}"
                    >
                        Stories
                    </a>
                </div>
            </div>

            <div class="social-content-card">
                @if ($tab === 'posts')
                    <div id="social-posts-root">
                        <h3 class="social-section-title">Posts feed shell — {{ $socialCounts['posts'] }} stored posts</h3>

                    @if (!empty($syncError))
                        <div class="social-sync-banner error">
                            Feed sync failed: {{ $syncError }}
                        </div>
                    @elseif (!empty($syncResult))
                        <div class="social-sync-banner success">
                            Feed sync completed. {{ $syncResult['count'] ?? 0 }} item(s) synced for the active Instagram connection.
                        </div>
                    @endif

                    @if (session('social_success'))
                        <div class="social-sync-banner success">
                            {{ session('social_success') }}
                        </div>
                    @endif

                    @if (session('social_error'))
                        <div class="social-sync-banner error">
                            {{ session('social_error') }}
                        </div>
                    @endif


                    @if (($posts ?? collect())->isEmpty())
                        <div class="social-placeholder-grid">
                            <div class="social-placeholder-box">
                                <div class="social-placeholder-label">No posts stored yet</div>
                                <div class="social-placeholder-text">
                                    As soon as an Instagram connection is active, the posts tab will sync the media feed and store post cards here for the current workspace.
                                </div>
                            </div>

                            <div class="social-placeholder-box">
                                <div class="social-placeholder-label">Next phase</div>
                                <div class="social-placeholder-text">
                                    The next step is rendering real post details, comment entry points, filters, and actions for publishing and moderation.
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="social-post-grid">
                            @foreach ($posts as $post)
                                @php
                                    $mediaItems = $post->mediaItems;
                                    $coverMedia = $mediaItems->firstWhere('is_cover', true) ?: $mediaItems->first();
                                    $coverMediaUrl = $coverMedia?->media_url ?: $post->media_url;
                                    $coverThumbnailUrl = $coverMedia?->thumbnail_url ?: $post->thumbnail_url;
                                    $coverMediaType = strtoupper((string) ($coverMedia?->media_type ?: $post->media_type ?: 'MEDIA'));
                                    $resolvedMediaType = strtoupper((string) ($post->media_type ?: $coverMediaType ?: 'MEDIA'));
                                    $isCarousel = $resolvedMediaType === 'CAROUSEL_ALBUM';
                                    $isVideo = $resolvedMediaType === 'VIDEO';
                                    $isReel = $resolvedMediaType === 'REELS';
                                    $isVideoLike = in_array($resolvedMediaType, ['VIDEO', 'REELS'], true);
                                    $typeBadgeClass = $isCarousel
                                        ? 'carousel'
                                        : ($isReel
                                            ? 'reel'
                                            : ($isVideo
                                                ? 'video'
                                                : 'image'));
                                    $typeLabel = $isCarousel
                                        ? 'Carousel'
                                        : ($isReel
                                            ? 'Reel'
                                            : ($isVideo
                                                ? 'Video'
                                                : 'Image'));
                                    $commentsCollapseId = 'post-comments-' . $post->id;
                                    $visibleComments = $post->comments;
                                    $totalCommentsCount = (int) ($post->visible_comments_count ?? $visibleComments->count());
                                    $postCommentSyncError = $commentSyncErrors[$post->id] ?? null;
                                    $currentCommentsLimit = $postCommentsPageSizes[$post->id] ?? ($postCommentsPageSize ?? 10);
                                    $nextCommentsLimit = min($currentCommentsLimit + ($postCommentsPageSize ?? 10), 100);
                                    $loadMoreCommentsUrl = request()->fullUrlWithQuery([
                                        'comments_limit' => array_merge(request()->query('comments_limit', []), [
                                            $post->id => $nextCommentsLimit,
                                        ]),
                                    ]);
                                @endphp
                                <div class="social-post-card">
                                    <div class="social-post-media">
                                        <div class="social-post-media-stack">
                                            <div class="social-post-media-frame">
                                                <span class="social-post-media-type-badge {{ $typeBadgeClass }}">
                                                    {{ $typeLabel }}
                                                </span>

                                                @if ($coverMediaUrl)
                                                    <img
                                                        src="{{ $coverMediaUrl }}"
                                                        alt="Instagram media {{ $post->provider_media_id }}"
                                                        class="social-post-image"
                                                    >
                                                @elseif ($coverThumbnailUrl)
                                                    <img
                                                        src="{{ $coverThumbnailUrl }}"
                                                        alt="Instagram thumbnail {{ $post->provider_media_id }}"
                                                        class="social-post-image"
                                                    >
                                                @else
                                                    <div class="social-post-fallback">
                                                        {{ $typeLabel }} preview is not available yet
                                                    </div>
                                                @endif

                                                @if ($isCarousel)
                                                    <span class="social-post-carousel-count">
                                                        {{ $mediaItems->count() }} slides
                                                    </span>
                                                @elseif ($isReel)
                                                    <span class="social-post-video-indicator">Reel</span>
                                                @elseif ($isVideo)
                                                    <span class="social-post-video-indicator">Video</span>
                                                @endif
                                            </div>

                                            @if ($isCarousel && $mediaItems->isNotEmpty())
                                                <div class="social-post-carousel-strip">
                                                    @foreach ($mediaItems->take(3) as $mediaItem)
                                                        <div class="social-post-carousel-item">
                                                            @if ($mediaItem->media_url)
                                                                <img
                                                                    src="{{ $mediaItem->media_url }}"
                                                                    alt="Carousel media {{ $mediaItem->provider_media_id }}"
                                                                    class="social-post-carousel-thumb"
                                                                >
                                                            @elseif ($mediaItem->thumbnail_url)
                                                                <img
                                                                    src="{{ $mediaItem->thumbnail_url }}"
                                                                    alt="Carousel thumbnail {{ $mediaItem->provider_media_id }}"
                                                                    class="social-post-carousel-thumb"
                                                                >
                                                            @else
                                                                {{ strtoupper((string) ($mediaItem->media_type ?: 'MEDIA')) }}
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="social-post-body">
                                        <div class="social-post-meta">
                                            <span class="social-post-badge">
                                                {{ $resolvedMediaType ?: 'UNKNOWN' }}
                                            </span>

                                            <span class="social-post-badge">
                                                {{ $post->posted_at ? $post->posted_at->format('Y-m-d H:i') : 'No publish time' }}
                                            </span>
                                        </div>

                                        <div class="social-post-caption">
                                            {{ $post->caption ?: 'No caption available for this post yet.' }}
                                        </div>

                                        <div class="social-post-stats">
                                            <span class="social-post-badge">Likes: {{ $post->like_count }}</span>
                                            <span class="social-post-badge">Status: {{ $post->status }}</span>

                                            @if ($isCarousel)
                                                <span class="social-post-badge">Slides: {{ $mediaItems->count() }}</span>
                                            @elseif ($isVideoLike)
                                                <span class="social-post-badge">Playback: {{ $isReel ? 'Reel' : 'Video' }}</span>
                                            @endif
                                        </div>

                                        <div class="social-post-actions">
                                            <button
                                                type="button"
                                                class="social-post-action-button"
                                                onclick="window.socialToggleComments && window.socialToggleComments('{{ $commentsCollapseId }}')"
                                            >
                                                Comments ({{ $totalCommentsCount }})
                                            </button>

                                            @if ($post->permalink)
                                                <a href="{{ $post->permalink }}" target="_blank" rel="noreferrer" class="social-post-link">
                                                    Open on Instagram
                                                </a>
                                            @endif
                                        </div>

                                        <div id="{{ $commentsCollapseId }}" class="social-comments-panel" hidden>
                                            @if ($postCommentSyncError)
                                                <div class="social-sync-banner error" style="margin-bottom:0;">
                                                    Comment sync failed for this post: {{ $postCommentSyncError }}
                                                </div>
                                            @endif

                                            <div class="social-comments-summary">
                                                Showing up to {{ $postCommentsPageSize ?? 10 }} most recent stored comments for this post. Load more, inline reply, hide/unhide, delete, and reply-via-DM actions will be connected in the next phase.
                                            </div>

                                            @if ($visibleComments->isEmpty())
                                                <div class="social-comments-empty">
                                                    No stored comments are available for this post yet.
                                                </div>
                                            @else
                                                <div class="social-comment-list">
                                                    @foreach ($visibleComments as $comment)
                                                    @php
                                                        $replyFormId = 'reply-form-' . $comment->id;
                                                        $commentAuthor = $comment->username ?: 'Instagram user';
                                                        $commentAvatarLetter = mb_strtoupper(mb_substr(trim($commentAuthor), 0, 1));
                                                        $commentRaw = is_array($comment->raw) ? $comment->raw : [];
                                                        $commentReplyBy = null;

                                                        foreach ([
                                                            $commentRaw['reply_actor_name'] ?? null,
                                                            $commentRaw['reply_actor_email'] ?? null,
                                                        ] as $replyActorCandidate) {
                                                            $replyActorCandidate = is_string($replyActorCandidate) ? trim($replyActorCandidate) : null;
                                                            if ($replyActorCandidate !== null && $replyActorCandidate !== '') {
                                                                $commentReplyBy = $replyActorCandidate;
                                                                break;
                                                            }
                                                        }

                                                        if ($commentReplyBy === null && $comment->replied_publicly_at) {
                                                            $commentReplyBy = 'A team member';
                                                        }

                                                        $lastPublicReplyText = is_string($commentRaw['last_public_reply_text'] ?? null)
                                                            ? trim($commentRaw['last_public_reply_text'])
                                                            : null;
                                                        $lastDmReplyText = is_string($commentRaw['last_dm_reply_text'] ?? null)
                                                            ? trim($commentRaw['last_dm_reply_text'])
                                                            : null;
                                                    @endphp
                                                        <div class="social-comment-card">
                                                            <div class="social-comment-top">
                                                                <div class="social-comment-main">
                                                                    <div class="social-comment-avatar">{{ $commentAvatarLetter ?: 'U' }}</div>
                                                                    <div class="social-comment-author">
                                                                        {{ $commentAuthor }}
                                                                    </div>
                                                                    <div class="social-comment-text">
                                                                        {{ $comment->text ?: 'No comment text available.' }}
                                                                    </div>
                                                                </div>

                                                                <div class="social-comment-date">
                                                                    {{ $comment->commented_at ? $comment->commented_at->format('Y-m-d H:i') : 'No date' }}
                                                                </div>
                                                            </div>

                                                            <div class="social-comment-stats"></div>

                                                            @if ($comment->is_hidden)
                                                                <div class="social-comment-reply-note">
                                                                    <span class="social-post-badge">Hidden</span>
                                                                    <span>This comment is currently hidden on Instagram.</span>
                                                                </div>
                                                            @endif

                                                            @if ($comment->replied_publicly_at)
                                                                <div class="social-comment-reply-note">
                                                                    <span class="social-post-badge">Replied</span>
                                                                    <span>
                                                                        Public reply by <strong>{{ $commentReplyBy ?: 'A team member' }}</strong>
                                                                        at {{ $comment->replied_publicly_at->format('Y-m-d H:i') }}
                                                                    </span>
                                                                </div>

                                                                @if ($lastPublicReplyText)
                                                                    <div class="social-comment-reply-note" style="padding-left: 0;">
                                                                        <span>“{{ $lastPublicReplyText }}”</span>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            @if ($comment->replied_via_dm_at)
                                                                <div class="social-comment-reply-note">
                                                                    <span class="social-post-badge">DM sent</span>
                                                                    <span>
                                                                        DM reply by <strong>{{ $commentReplyBy ?: 'A team member' }}</strong>
                                                                        at {{ $comment->replied_via_dm_at->format('Y-m-d H:i') }}
                                                                    </span>
                                                                </div>

                                                                @if ($lastDmReplyText)
                                                                    <div class="social-comment-reply-note" style="padding-left: 0;">
                                                                        <span>“{{ $lastDmReplyText }}”</span>
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            <div class="social-comment-actions">
                                                                <button
                                                                    type="button"
                                                                    class="social-comment-action-button"
                                                                    onclick="const form = document.getElementById('{{ $replyFormId }}'); const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const action = form ? form.querySelector('[data-reply-action-input]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = false; form.action = '{{ route('social.instagram.comments.reply', $comment) }}'; if (mode) mode.textContent = 'Public reply'; if (action) action.value = '{{ route('social.instagram.comments.reply', $comment) }}'; if (button) button.textContent = 'Send reply'; if (input) { input.placeholder = 'Write your public reply here...'; input.focus(); } }"
                                                                >
                                                                    Reply
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    class="social-comment-action-button"
                                                                    onclick="const form = document.getElementById('{{ $replyFormId }}'); const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const action = form ? form.querySelector('[data-reply-action-input]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = false; form.action = '{{ route('social.instagram.comments.reply_dm', $comment) }}'; if (mode) mode.textContent = 'DM reply'; if (action) action.value = '{{ route('social.instagram.comments.reply_dm', $comment) }}'; if (button) button.textContent = 'Send DM reply'; if (input) { input.placeholder = 'Write your private DM reply here...'; input.focus(); } }"
                                                                >
                                                                    Reply via DM
                                                                </button>

                                                                <form
                                                                    method="POST"
                                                                    action="{{ $comment->is_hidden ? route('social.instagram.comments.unhide', $comment) : route('social.instagram.comments.hide', $comment) }}"
                                                                    style="display:inline;"
                                                                    class="social-inline-comment-form"
                                                                    data-panel-id="{{ $commentsCollapseId }}"
                                                                >
                                                                    @csrf
                                                                    <button type="submit" class="social-comment-action-button">
                                                                        {{ $comment->is_hidden ? 'Unhide' : 'Hide' }}
                                                                    </button>
                                                                </form>

                                                                <form method="POST" action="{{ route('social.instagram.comments.delete', $comment) }}" style="display:inline;" class="social-inline-comment-form" data-panel-id="{{ $commentsCollapseId }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="social-comment-action-button">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </div>

                                                            <form
                                                                id="{{ $replyFormId }}"
                                                                method="POST"
                                                                action="{{ route('social.instagram.comments.reply', $comment) }}"
                                                                class="social-comment-reply-form social-inline-comment-form"
                                                                data-panel-id="{{ $commentsCollapseId }}"
                                                                hidden
                                                            >
                                                                @csrf
                                                                <span class="social-post-badge" data-reply-mode-label>Public reply</span>
                                                                <input type="hidden" name="_reply_action" value="{{ route('social.instagram.comments.reply', $comment) }}" data-reply-action-input>
                                                                <input
                                                                    type="text"
                                                                    name="reply_text"
                                                                    class="social-comment-reply-textarea"
                                                                    placeholder="Write your public reply here..."
                                                                    required
                                                                >

                                                                <div class="social-comment-reply-actions">
                                                                    <button type="submit" class="social-comment-action-button" data-reply-submit-button>
                                                                        Send reply
                                                                    </button>

                                                                    <button
                                                                        type="button"
                                                                        class="social-comment-action-button"
                                                                        onclick="const form = document.getElementById('{{ $replyFormId }}'); const action = form ? form.querySelector('[data-reply-action-input]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = true; form.action = '{{ route('social.instagram.comments.reply', $comment) }}'; if (action) action.value = '{{ route('social.instagram.comments.reply', $comment) }}'; if (button) button.textContent = 'Send reply'; if (mode) mode.textContent = 'Public reply'; if (input) { input.value = ''; input.placeholder = 'Write your public reply here...'; } }"
                                                                    >
                                                                        Cancel
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                @if ($totalCommentsCount > $visibleComments->count())
                                                    <div class="social-comments-summary">
                                                        {{ $visibleComments->count() }} of {{ $totalCommentsCount }} comments are currently loaded for this post.
                                                    </div>

                                                    <div class="social-post-actions">
                                                        <a href="{{ $loadMoreCommentsUrl }}" class="social-post-link social-inline-comments-link" data-panel-id="{{ $commentsCollapseId }}">
                                                            Load more comments
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    </div>

                    <script>
                        (function () {
                            window.socialRestorePostsState = function (panelId) {
                                const savedScrollY = Number(sessionStorage.getItem('social_posts_scroll_y') || 0);

                                if (panelId) {
                                    const panel = document.getElementById(panelId);
                                    if (panel) {
                                        panel.hidden = false;
                                        sessionStorage.setItem('social_posts_open_panel', panelId);
                                    }
                                }

                                const restoreScroll = function () {
                                    window.scrollTo({ top: savedScrollY, left: 0, behavior: 'auto' });
                                };

                                restoreScroll();
                                window.requestAnimationFrame(restoreScroll);
                                setTimeout(restoreScroll, 0);
                                setTimeout(restoreScroll, 80);
                            };

                            window.socialReplacePostsRoot = function (html, panelId) {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const nextRoot = doc.getElementById('social-posts-root');
                                const currentRoot = document.getElementById('social-posts-root');

                                if (!nextRoot || !currentRoot) {
                                    window.location.reload();
                                    return;
                                }

                                currentRoot.replaceWith(nextRoot);
                                window.socialBindInlineCommentActions();
                                window.socialRestorePostsState(panelId || sessionStorage.getItem('social_posts_open_panel'));
                            };

                            window.socialSubmitInlineForm = function (form) {
                                const panelId = form.getAttribute('data-panel-id') || sessionStorage.getItem('social_posts_open_panel');
                                sessionStorage.setItem('social_posts_scroll_y', String(window.scrollY || window.pageYOffset || 0));

                                if (panelId) {
                                    sessionStorage.setItem('social_posts_open_panel', panelId);
                                }

                                form.classList.add('social-inline-loading');

                                fetch(form.action, {
                                    method: 'POST',
                                    body: new FormData(form),
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'text/html, application/xhtml+xml'
                                    }
                                })
                                    .then(function (response) {
                                        return response.text();
                                    })
                                    .then(function (html) {
                                        window.socialReplacePostsRoot(html, panelId);
                                    })
                                    .catch(function () {
                                        window.location.reload();
                                    })
                                    .finally(function () {
                                        form.classList.remove('social-inline-loading');
                                    });
                            };

                            window.socialLoadMoreComments = function (url, panelId) {
                                sessionStorage.setItem('social_posts_scroll_y', String(window.scrollY || window.pageYOffset || 0));

                                if (panelId) {
                                    sessionStorage.setItem('social_posts_open_panel', panelId);
                                }

                                fetch(url, {
                                    method: 'GET',
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'text/html, application/xhtml+xml'
                                    }
                                })
                                    .then(function (response) {
                                        return response.text();
                                    })
                                    .then(function (html) {
                                        window.socialReplacePostsRoot(html, panelId);
                                    })
                                    .catch(function () {
                                        window.location.href = url;
                                    });
                            };

                            window.socialBindInlineCommentActions = function () {
                                document.querySelectorAll('.social-inline-comment-form').forEach(function (form) {
                                    if (form.dataset.boundSocialForm === '1') {
                                        return;
                                    }

                                    form.dataset.boundSocialForm = '1';
                                    form.addEventListener('submit', function (event) {
                                        event.preventDefault();
                                        window.socialSubmitInlineForm(form);
                                    });
                                });

                                document.querySelectorAll('.social-inline-comments-link').forEach(function (link) {
                                    if (link.dataset.boundSocialLink === '1') {
                                        return;
                                    }

                                    link.dataset.boundSocialLink = '1';
                                    link.addEventListener('click', function (event) {
                                        event.preventDefault();
                                        window.socialLoadMoreComments(link.href, link.getAttribute('data-panel-id'));
                                    });
                                });
                            };

                            window.socialToggleComments = function (panelId) {
                                const panel = document.getElementById(panelId);
                                if (!panel) {
                                    return;
                                }

                                panel.hidden = !panel.hidden;

                                if (!panel.hidden) {
                                    sessionStorage.setItem('social_posts_open_panel', panelId);
                                } else if (sessionStorage.getItem('social_posts_open_panel') === panelId) {
                                    sessionStorage.removeItem('social_posts_open_panel');
                                }
                            };

                            window.socialBindInlineCommentActions();

                            const openPanelId = sessionStorage.getItem('social_posts_open_panel');
                            if (openPanelId) {
                                const panel = document.getElementById(openPanelId);
                                if (panel) {
                                    panel.hidden = false;
                                }
                            }
                        })();
                    </script>
                @elseif ($tab === 'comments')
                    <h3 class="social-section-title">Comments moderation shell — {{ $socialCounts['comments'] }} stored comments</h3>

                    <div class="social-placeholder-grid">
                        <div class="social-placeholder-box">
                            <div class="social-placeholder-label">Comment stream</div>
                            <div class="social-placeholder-text">
                                This section will list post comments, reply actions, moderation actions, and filters like unreplied, hidden, or selected post.
                            </div>
                        </div>

                        <div class="social-placeholder-box">
                            <div class="social-placeholder-label">Reply via DM bridge</div>
                            <div class="social-placeholder-text">
                                This section will bridge Social and Inbox so a comment can be answered privately via Instagram DM using the messaging pipeline.
                            </div>
                        </div>
                    </div>
                @else
                    <h3 class="social-section-title">Stories — {{ $socialCounts['stories'] }} stored stories</h3>



                    @if (!empty($storySyncError))
                        <div class="social-sync-banner error">
                            Story sync failed: {{ $storySyncError }}
                        </div>
                    @endif

                    <div class="social-placeholder-grid" style="margin-bottom:14px;">
                        <div class="social-placeholder-box">
                            <div class="social-placeholder-label">Publish Story</div>

                            <form
                                method="POST"
                                action="{{ route('social.instagram.stories.publish') }}"
                                class="social-story-form"
                                enctype="multipart/form-data"
                            >
                                @csrf

                                <div class="social-story-form-grid">
                                    <div class="social-story-field">
                                        <label class="social-story-label">Media type</label>
                                        <select
                                            name="media_type"
                                            class="social-story-select"
                                            {{ $storyPublishEnabled ? '' : 'disabled' }}
                                        >
                                            <option value="IMAGE" {{ old('media_type') === 'IMAGE' ? 'selected' : '' }}>IMAGE</option>
                                            <option value="VIDEO" {{ old('media_type') === 'VIDEO' ? 'selected' : '' }}>VIDEO</option>
                                        </select>
                                        @error('media_type')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="social-story-field">
                                        <label class="social-story-label">Story file</label>
                                        <input
                                            type="file"
                                            name="story_file"
                                            class="social-story-file"
                                            accept="image/*,video/mp4,video/quicktime"
                                            {{ $storyPublishEnabled ? '' : 'disabled' }}
                                        >
                                        @error('story_file')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="social-story-form-grid">
                                    <div class="social-story-field">
                                        <label class="social-story-label">Media URL (optional fallback)</label>
                                        <input
                                            type="text"
                                            name="media_url"
                                            class="social-story-input"
                                            placeholder="https://example.com/story-media.jpg"
                                            value="{{ old('media_url') }}"
                                            {{ $storyPublishEnabled ? '' : 'disabled' }}
                                        >
                                        @error('media_url')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="social-story-field">
                                        <label class="social-story-label">Caption</label>
                                        <input
                                            type="text"
                                            name="caption"
                                            class="social-story-input"
                                            placeholder="Optional caption"
                                            value="{{ old('caption') }}"
                                            {{ $storyPublishEnabled ? '' : 'disabled' }}
                                        >
                                        @error('caption')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="social-story-field">
                                    <label class="social-story-label">Story preview</label>
                                    <div class="social-story-preview-box" id="story-preview-box">
                                        <div class="social-story-preview-empty" id="story-preview-empty">
                                            Select an image/video file or provide a media URL to preview the story before publishing.
                                        </div>
                                        <img id="story-preview-image" class="social-story-preview-image" alt="Story preview" hidden>
                                        <video id="story-preview-video" class="social-story-preview-video" controls playsinline hidden></video>
                                    </div>
                                </div>

                                <div class="social-story-help">
                                    Current status: {{ $storyPublishEnabled ? 'ready to publish' : 'Instagram connection required' }}.
                                    First priority is uploaded file. If no file is selected, optional media URL is used.
                                </div>

                                <div class="social-story-actions">
                                    <button type="submit" class="social-post-action-button" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                        Publish story
                                    </button>
                                </div>
                            </form>
                            <script>
                                (function () {
                                    const fileInput = document.querySelector('input[name="story_file"]');
                                    const mediaUrlInput = document.querySelector('input[name="media_url"]');
                                    const mediaTypeInput = document.querySelector('select[name="media_type"]');
                                    const emptyBox = document.getElementById('story-preview-empty');
                                    const imagePreview = document.getElementById('story-preview-image');
                                    const videoPreview = document.getElementById('story-preview-video');

                                    if (!fileInput || !mediaUrlInput || !mediaTypeInput || !emptyBox || !imagePreview || !videoPreview) {
                                        return;
                                    }

                                    const resetPreview = () => {
                                        imagePreview.hidden = true;
                                        imagePreview.removeAttribute('src');
                                        videoPreview.hidden = true;
                                        videoPreview.pause();
                                        videoPreview.removeAttribute('src');
                                        videoPreview.load();
                                        emptyBox.hidden = false;
                                    };
                                    const showImage = (src) => {
    if (!src) {
        resetPreview();
        return;
    }

    videoPreview.hidden = true;
    videoPreview.pause();
    videoPreview.removeAttribute('src');
    videoPreview.load();

    imagePreview.hidden = true;
    imagePreview.removeAttribute('src');
    imagePreview.src = src;
    imagePreview.hidden = false;

    emptyBox.hidden = true;
};

const showVideo = (src) => {
    if (!src) {
        resetPreview();
        return;
    }

    imagePreview.hidden = true;
    imagePreview.removeAttribute('src');

    videoPreview.hidden = true;
    videoPreview.removeAttribute('src');
    videoPreview.src = src;
    videoPreview.hidden = false;

    emptyBox.hidden = true;
};

                                    const detectUrlKind = (url) => {
                                        const cleanUrl = String(url || '').split('?')[0].toLowerCase();

                                        if (/\.(jpg|jpeg|png|webp|gif)$/i.test(cleanUrl)) {
                                            return 'IMAGE';
                                        }

                                        if (/\.(mp4|mov|m4v|webm)$/i.test(cleanUrl)) {
                                            return 'VIDEO';
                                        }

                                        return null;
                                    };

                                    const updatePreview = () => {
                                        const selectedFile = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                                        const selectedMediaType = mediaTypeInput.value;
                                        const mediaUrl = mediaUrlInput.value.trim();

                                        if (selectedFile) {
                                            const objectUrl = URL.createObjectURL(selectedFile);

                                            if (String(selectedFile.type || '').startsWith('video/')) {
                                                showVideo(objectUrl);
                                            } else {
                                                showImage(objectUrl);
                                            }

                                            return;
                                        }

                                        if (mediaUrl !== '') {
                                            const urlKind = detectUrlKind(mediaUrl) || selectedMediaType;

                                            if (urlKind === 'VIDEO') {
                                                showVideo(mediaUrl);
                                            } else {
                                                showImage(mediaUrl);
                                            }

                                            return;
                                        }

                                        resetPreview();
                                    };

                                    fileInput.addEventListener('change', updatePreview);
                                    mediaUrlInput.addEventListener('input', updatePreview);
                                    mediaTypeInput.addEventListener('change', updatePreview);
                                    updatePreview();
                                })();
                            </script>
                        </div>

                        <div class="social-placeholder-box">
                            <div class="social-placeholder-label">Story status</div>
                            <div class="social-placeholder-text">
                                Stored stories are shown below. Publishing is {{ $storyPublishEnabled ? 'enabled for this workspace connection' : 'disabled until Instagram is connected' }}.
                            </div>
                        </div>
                    </div>

                    @if (($stories ?? collect())->isEmpty())
                        <div class="social-placeholder-grid">
                            <div class="social-placeholder-box">
                                <div class="social-placeholder-label">No stories stored yet</div>
                                <div class="social-placeholder-text">
                                    Once stories are synced or published, they will appear here with posting time, expiry time, and status.
                                </div>
                            </div>

                            <div class="social-placeholder-box">
                                <div class="social-placeholder-label">Next step</div>
                                <div class="social-placeholder-text">
                                    After this UI shell, the next step is wiring the real publish story action and storing the result in `social_stories`.
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="social-story-grid">
                            @foreach ($stories as $story)
                                @php
                                    $storyMediaUrl = $story->media_url ?: $story->thumbnail_url;
                                    $storyStatus = strtolower((string) ($story->status ?: 'draft'));
                                @endphp
                                <div class="social-story-card">
                                    <div class="social-story-media">
                                        @if ($storyMediaUrl)
                                            <img
                                                src="{{ $storyMediaUrl }}"
                                                alt="Instagram story {{ $story->provider_story_id }}"
                                                class="social-story-image"
                                            >
                                        @else
                                            <div class="social-story-fallback">
                                                Story preview is not available yet
                                            </div>
                                        @endif
                                    </div>

                                    <div class="social-story-body">
                                        <div class="social-story-meta">
                                            <span class="social-post-badge social-story-status-badge {{ $storyStatus }}">
                                                Status: {{ ucfirst($storyStatus) }}
                                            </span>
                                            <span class="social-post-badge">Instagram Story</span>
                                        </div>

                                        <div class="social-story-meta">
                                            <span class="social-post-badge">
                                                Posted: {{ $story->posted_at ? $story->posted_at->format('Y-m-d H:i') : 'No publish time' }}
                                            </span>
                                        </div>

                                        <div class="social-story-meta">
                                            <span class="social-post-badge">
                                                Expires: {{ $story->expires_at ? $story->expires_at->format('Y-m-d H:i') : 'No expiry time' }}
                                            </span>
                                        </div>

                                        <div class="social-story-actions">
                                            <form method="POST" action="{{ route('social.instagram.stories.delete', $story) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="social-post-action-button">
                                                    Delete story
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
