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

            .social-account-grid {
                margin-top: 14px;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 10px;
            }

            .social-account-card {
                display: grid;
                gap: 6px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #f8fafc;
                padding: 12px;
                text-decoration: none;
                color: inherit;
            }

            .social-account-card.is-active {
                background: #eef2ff;
                border-color: #c7d2fe;
            }

            .social-account-name {
                font-size: 14px;
                font-weight: 800;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .social-account-meta {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                font-size: 11px;
                font-weight: 800;
                color: #64748b;
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
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
            }

            .social-post-card {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .social-post-media {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 150px;
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
                height: 165px;
                object-fit: cover;
                background: #f8fafc;
            }

            .social-post-media-frame {
                position: relative;
                width: 100%;
            }

            .social-post-media-type-badge {
                position: absolute;
                top: 8px;
                left: 8px;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                min-height: 24px;
                border-radius: 999px;
                padding: 0 8px;
                font-size: 10px;
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
                min-height: 150px;
                padding: 18px;
                text-align: center;
                font-size: 13px;
                font-weight: 800;
                color: #475569;
                background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            }

            .social-post-video-indicator {
                position: absolute;
                right: 8px;
                bottom: 8px;
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
                right: 8px;
                bottom: 8px;
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
                padding: 10px;
                display: grid;
                gap: 8px;
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
                font-size: 12px;
                line-height: 1.45;
                color: #334155;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .social-post-stats {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .social-post-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 30px;
                border-radius: 10px;
                padding: 0 9px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #334155;
                font-size: 11px;
                font-weight: 800;
                text-decoration: none;
                width: fit-content;
            }

            .social-post-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 6px;
                flex-wrap: wrap;
            }

            .social-post-compact-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                flex-wrap: wrap;
            }

            .social-post-compact-row .social-post-stats,
            .social-post-compact-row .social-post-actions {
                min-width: 0;
            }

            .social-post-action-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 30px;
                border-radius: 10px;
                padding: 0 9px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #334155;
                font-size: 11px;
                font-weight: 800;
                text-decoration: none;
                cursor: pointer;
            }

            .social-post-action-button:hover {
                background: #f8fafc;
            }

            .social-post-action-button.danger {
                color: #991b1b;
                border-color: #fecaca;
                background: #fff;
            }

            .social-post-action-button.danger:hover {
                background: #fef2f2;
            }

            .social-comments-panel {
                margin-top: 8px;
                border-top: 1px solid #e2e8f0;
                padding-top: 8px;
                display: grid;
                gap: 8px;
            }

            .social-comments-summary {
                font-size: 12px;
                color: #64748b;
                line-height: 1.7;
            }

            .social-comment-list {
                display: grid;
                gap: 8px;
                max-height: 255px;
                overflow-y: auto;
                padding-right: 4px;
            }

            .social-comment-card {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                padding: 8px;
                display: grid;
                gap: 5px;
            }

            .social-comment-top {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                flex-wrap: nowrap;
                min-width: 0;
            }

            .social-comment-main {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                min-width: 0;
                flex: 1 1 auto;
                flex-wrap: wrap;
            }

            .social-comment-avatar {
                width: 34px;
                height: 34px;
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
                overflow: hidden;
            }

            .social-comment-avatar img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .social-comment-author {
                font-size: 12px;
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
                font-size: 14px;
                line-height: 1.55;
                font-weight: 650;
                color: #0f172a;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
                min-width: 0;
                flex: 1 1 100%;
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

            .social-comment-thread {
                display: grid;
                gap: 6px;
                padding-left: 42px;
            }

            .social-comment-child {
                display: grid;
                gap: 4px;
                border-left: 2px solid #e2e8f0;
                padding: 6px 0 6px 10px;
            }

            .social-comment-child-top {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
                min-width: 0;
            }

            .social-comment-child-author {
                font-size: 11px;
                font-weight: 800;
                color: #0f172a;
            }

            .social-comment-child-date {
                font-size: 10px;
                font-weight: 700;
                color: #64748b;
            }

            .social-comment-child-text {
                font-size: 13px;
                line-height: 1.5;
                color: #334155;
            }

            .social-comment-actions {
                display: flex;
                align-items: center;
                gap: 4px;
                flex-wrap: wrap;
            }

            .social-comment-reply-form {
                display: grid;
                gap: 5px;
                margin-top: 4px;
                flex-wrap: wrap;
                padding-left: 42px;
            }

            .social-comment-reply-textarea {
                width: 100%;
                min-height: 32px;
                height: 32px;
                max-height: 32px;
                border: 1px solid #cbd5e1;
                border-radius: 999px;
                padding: 6px 10px;
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
                gap: 5px;
                flex-wrap: nowrap;
                width: 100%;
            }

            .social-comment-reply-actions .social-comment-reply-textarea {
                flex: 1 1 auto;
            }

            .social-comment-action-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 26px;
                border-radius: 8px;
                padding: 0 7px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: #334155;
                font-size: 10px;
                font-weight: 800;
                cursor: pointer;
            }

            .social-comment-icon-button {
                width: 28px;
                min-width: 28px;
                padding: 0;
                border-radius: 999px;
                font-size: 13px;
                line-height: 1;
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
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
            }

            .social-story-tray {
                display: flex;
                gap: 14px;
                overflow-x: auto;
                padding: 4px 2px 14px;
                margin-bottom: 12px;
            }

            .social-story-ring {
                width: 76px;
                min-width: 76px;
                display: grid;
                gap: 6px;
                justify-items: center;
                color: #334155;
                border: 0;
                background: transparent;
                padding: 0;
                font-size: 11px;
                font-weight: 800;
                cursor: pointer;
            }

            .social-story-ring-media {
                width: 64px;
                height: 64px;
                border-radius: 999px;
                padding: 3px;
                background: linear-gradient(135deg, #f43f5e, #f59e0b 45%, #7c3aed);
            }

            .social-story-ring-media-inner {
                width: 100%;
                height: 100%;
                border: 3px solid #fff;
                border-radius: 999px;
                overflow: hidden;
                background: #f8fafc;
            }

            .social-story-ring-media-inner img,
            .social-story-ring-media-inner video {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .social-story-composer-grid {
                display: grid;
                grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
                gap: 18px;
                align-items: start;
            }

            .social-story-card {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
            }

            .social-story-media {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                aspect-ratio: 9 / 16;
                background: #0f172a;
                border-bottom: 1px solid #e2e8f0;
            }

            .social-story-image,
            .social-story-video {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
                background: #0f172a;
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

            .social-story-status-badge.archived {
                background: #f8fafc;
                color: #475569;
                border-color: #cbd5e1;
            }

            .social-story-status-badge.removed {
                background: #fef2f2;
                color: #991b1b;
                border-color: #fca5a5;
            }

            .social-story-archive-section {
                margin-top: 20px;
            }

            .social-story-section-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin: 18px 0 12px;
                flex-wrap: wrap;
            }

            .social-story-section-header h4 {
                margin: 0;
                font-size: 14px;
                font-weight: 900;
                color: #0f172a;
            }

            .social-story-section-header span {
                font-size: 12px;
                line-height: 1.6;
                color: #64748b;
                font-weight: 700;
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
                border-radius: 8px;
                padding: 0 12px;
                font-size: 13px;
                color: #334155;
                background: #fff;
                box-sizing: border-box;
            }

            .social-story-file {
                padding: 8px 12px;
            }

            .social-story-select.is-multiple {
                min-height: 112px;
                padding: 8px 10px;
            }

            textarea.social-story-input {
                padding: 10px 12px;
                line-height: 1.6;
                resize: vertical;
            }

            .social-story-preview-box {
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                background: #0f172a;
                width: min(100%, 320px);
                aspect-ratio: 9 / 16;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                position: relative;
                margin: 0 auto;
            }

            .social-post-preview-box {
                width: min(100%, 360px);
                background: #f8fafc;
            }

            .social-post-preview-box.is-post-square {
                aspect-ratio: 1 / 1;
            }

            .social-post-preview-box.is-post-portrait {
                aspect-ratio: 4 / 5;
            }

            .social-post-preview-box.is-post-landscape {
                aspect-ratio: 1.91 / 1;
            }

            .social-post-preview-box.is-post-video {
                aspect-ratio: 9 / 16;
                background: #0f172a;
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
                height: 100%;
                object-fit: cover;
                transform: translate(var(--story-offset-x, 0%), var(--story-offset-y, 0%)) scale(var(--story-zoom, 1));
                background: #0f172a;
            }

            .social-story-preview-box.is-contain .social-story-preview-image,
            .social-story-preview-box.is-contain .social-story-preview-video {
                object-fit: contain;
            }

            .social-story-preview-image[hidden],
            .social-story-preview-video[hidden],
            .social-story-preview-empty[hidden] {
                display: none !important;
            }

            .social-story-limits {
                display: grid;
                gap: 6px;
                padding: 10px 12px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #f8fafc;
                font-size: 12px;
                line-height: 1.6;
                color: #475569;
            }

            .social-story-preview-controls {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .social-story-preview-controls input[type="range"] {
                width: 100%;
            }

            .social-story-confirm {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                font-size: 12px;
                line-height: 1.5;
                color: #334155;
                font-weight: 700;
            }

            .social-story-view-button {
                border: 0;
                padding: 0;
                width: 100%;
                background: transparent;
                cursor: pointer;
            }

            .social-story-viewer {
                position: fixed;
                inset: 0;
                z-index: 60;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .social-story-viewer.is-open {
                display: flex;
            }

            .social-story-viewer-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, .72);
            }

            .social-story-viewer-card {
                position: relative;
                width: min(92vw, 380px);
                background: #020617;
                border-radius: 8px;
                overflow: hidden;
                color: #fff;
                box-shadow: 0 24px 80px rgba(15, 23, 42, .45);
            }

            .social-story-viewer-media {
                width: 100%;
                aspect-ratio: 9 / 16;
                background: #020617;
            }

            .social-story-viewer-media img,
            .social-story-viewer-media video {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
            }

            .social-story-viewer-info {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                padding: 56px 14px 14px;
                background: linear-gradient(180deg, rgba(2, 6, 23, 0), rgba(2, 6, 23, .82));
                display: grid;
                gap: 6px;
                font-size: 12px;
            }

            .social-story-viewer-close {
                position: absolute;
                top: 10px;
                right: 10px;
                z-index: 2;
                width: 32px;
                height: 32px;
                border: 0;
                border-radius: 999px;
                background: rgba(15, 23, 42, .72);
                color: #fff;
                font-size: 20px;
                cursor: pointer;
            }

            .social-story-confirm-modal {
                position: fixed;
                inset: 0;
                z-index: 70;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 18px;
            }

            .social-story-confirm-modal.is-open {
                display: flex;
            }

            .social-story-confirm-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, .46);
                backdrop-filter: blur(2px);
            }

            .social-story-confirm-card {
                position: relative;
                width: min(100%, 420px);
                border-radius: 8px;
                background: #fff;
                border: 1px solid #e2e8f0;
                box-shadow: 0 24px 60px rgba(15, 23, 42, .2);
                padding: 16px;
                display: grid;
                gap: 12px;
            }

            .social-story-confirm-title {
                font-size: 15px;
                font-weight: 900;
                color: #0f172a;
            }

            .social-story-confirm-body {
                font-size: 13px;
                line-height: 1.7;
                color: #475569;
            }

            .social-story-confirm-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .social-story-confirm-button {
                min-height: 38px;
                border-radius: 8px;
                border: 1px solid #dbe3ec;
                background: #fff;
                padding: 0 14px;
                font-size: 13px;
                font-weight: 800;
                color: #334155;
                cursor: pointer;
            }

            .social-story-confirm-button.primary {
                border-color: #b91c1c;
                background: #b91c1c;
                color: #fff;
            }

            .social-story-progress {
                display: none;
                gap: 8px;
                margin-top: 4px;
            }

            .social-story-progress.is-visible {
                display: grid;
            }

            .social-story-progress-track {
                height: 8px;
                border-radius: 999px;
                background: #e2e8f0;
                overflow: hidden;
            }

            .social-story-progress-bar {
                width: 0%;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, #2563eb, #16a34a);
                transition: width .2s ease;
            }

            .social-story-progress-text {
                font-size: 12px;
                font-weight: 800;
                color: #334155;
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
                .social-story-form-grid,
                .social-story-composer-grid {
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

            @media (min-width: 961px) and (max-width: 1280px) {
                .social-post-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }
        </style>

        <div class="social-shell">
            @php
                $accountQuery = $selectedInstagramAccountId ? ['instagram_account' => $selectedInstagramAccountId] : [];
                $connectedAccountCount = $instagramConnections?->count() ?? 0;
            @endphp

            <div class="social-top-card">
                <div style="font-size:16px; font-weight:800; color:#0f172a;">
                    Instagram Social Console
                </div>
                <div style="margin-top:6px; font-size:13px; line-height:1.7; color:#64748b;">
                    Connected Instagram channels.
                </div>

                <div class="social-connection-summary">
                    <span class="social-connection-badge {{ $activeInstagramConnection ? 'connected' : 'missing' }}">
                        {{ $activeInstagramConnection ? 'Connected' : 'No connected Instagram account' }}
                    </span>

                    @if ($activeInstagramConnection)
                        <span class="social-connection-badge">
                            {{ $activeInstagramConnection->provider_account_name ?: 'Instagram account' }}
                        </span>

                        <span class="social-connection-badge">
                            <span data-social-post-total>{{ $socialCounts['posts'] }}</span> posts
                        </span>

                        <span class="social-connection-badge">
                            <span data-social-comment-total>{{ $socialCounts['comments'] }}</span> comments
                        </span>

                        <span class="social-connection-badge">
                            {{ $socialCounts['stories'] }} stories
                        </span>
                    @else
                        <span class="social-connection-badge missing">
                            Connect Instagram to load social content
                        </span>
                    @endif
                </div>

                @if ($connectedAccountCount > 0)
                    <div class="social-account-grid">
                        @foreach ($instagramAccountTabs as $accountTab)
                            <a
                                href="{{ route('social.instagram.' . $tab, ['instagram_account' => $accountTab['id']]) }}"
                                class="social-account-card {{ (int) $selectedInstagramAccountId === (int) $accountTab['id'] ? 'is-active' : '' }}"
                            >
                                <div class="social-account-name">
                                    {{ $accountTab['name'] }}
                                </div>
                                <div class="social-account-meta">
                                    <span>{{ $accountTab['post_count'] }} posts</span>
                                    <span>{{ $accountTab['comment_count'] }} comments</span>
                                    <span>{{ $accountTab['story_count'] ?? 0 }} stories</span>
                                    <span>Connected</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="social-tab-card">
                <div class="social-tabs">
                    <a
                        href="{{ route('social.instagram.posts', $accountQuery) }}"
                        class="social-tab-link {{ $tab === 'posts' ? 'is-active' : '' }}"
                    >
                        Posts
                    </a>

                    <a
                        href="{{ route('social.instagram.comments', $accountQuery) }}"
                        class="social-tab-link {{ $tab === 'comments' ? 'is-active' : '' }}"
                    >
                        Comments
                    </a>

                    <a
                        href="{{ route('social.instagram.stories', $accountQuery) }}"
                        class="social-tab-link {{ $tab === 'stories' ? 'is-active' : '' }}"
                    >
                        Stories
                    </a>
                </div>
            </div>

            <div class="social-content-card">
                @if ($tab === 'posts')
                    <div id="social-posts-root">
                        <h3 class="social-section-title">Posts — {{ $socialCounts['posts'] }} stored posts</h3>

                        @php
                            $postPublishEnabled = $postPublishEnabled ?? (bool) ($activeInstagramConnection && $activeInstagramConnection->status === 'connected');
                        @endphp

                        <div class="social-placeholder-box" style="margin-bottom:14px;">
                            <div class="social-story-composer-grid">
                                <div>
                                    <label class="social-story-label">Post preview</label>
                                    <div class="social-story-preview-box social-post-preview-box is-post-square" id="post-preview-box">
                                        <div class="social-story-preview-empty" id="post-preview-empty">
                                            Instagram feed preview
                                        </div>
                                        <img id="post-preview-image" class="social-story-preview-image" alt="Post preview" hidden>
                                        <video id="post-preview-video" class="social-story-preview-video" controls playsinline hidden></video>
                                    </div>
                                </div>

                                <form
                                    id="social-post-publish-form"
                                    method="POST"
                                    action="{{ route('social.instagram.posts.publish') }}"
                                    class="social-story-form"
                                    enctype="multipart/form-data"
                                >
                                    @csrf
                                    <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                    <input type="hidden" name="post_fit" value="cover">
                                    <input type="hidden" name="post_zoom" value="1">
                                    <input type="hidden" name="post_offset_x" value="0">
                                    <input type="hidden" name="post_offset_y" value="0">

                                    <div class="social-placeholder-label">Publish Post</div>

                                    <div class="social-story-form-grid">
                                        <div class="social-story-field">
                                            <label class="social-story-label">Post type</label>
                                            <select
                                                name="post_media_type"
                                                class="social-story-select"
                                                {{ $postPublishEnabled ? '' : 'disabled' }}
                                            >
                                                <option value="IMAGE" {{ old('post_media_type') === 'IMAGE' ? 'selected' : '' }}>Feed image</option>
                                                <option value="VIDEO" {{ old('post_media_type') === 'VIDEO' ? 'selected' : '' }}>Video Reel in feed</option>
                                            </select>
                                            @error('post_media_type')
                                                <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Post file</label>
                                            <input
                                                type="file"
                                                name="post_file"
                                                class="social-story-file"
                                                accept="image/*,video/*"
                                                {{ $postPublishEnabled ? '' : 'disabled' }}
                                            >
                                            @error('post_file')
                                                <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="social-story-field">
                                        <label class="social-story-label">Caption</label>
                                        <textarea
                                            name="post_caption"
                                            class="social-story-input"
                                            rows="4"
                                            maxlength="2200"
                                            placeholder="Write a caption. Up to 2,200 characters, 30 hashtags, and 20 mentions."
                                            {{ $postPublishEnabled ? '' : 'disabled' }}
                                        >{{ old('post_caption') }}</textarea>
                                        @error('post_caption')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="social-story-field">
                                        <label class="social-story-label">Media URL fallback</label>
                                        <input
                                            type="text"
                                            name="post_media_url"
                                            class="social-story-input"
                                            placeholder="https://example.com/feed-media.jpg"
                                            value="{{ old('post_media_url') }}"
                                            {{ $postPublishEnabled ? '' : 'disabled' }}
                                        >
                                        @error('post_media_url')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="social-story-preview-controls">
                                        <div class="social-story-field">
                                            <label class="social-story-label">Feed frame</label>
                                            <select name="post_aspect" id="post-aspect-control" class="social-story-select" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                                <option value="square">Square 1:1</option>
                                                <option value="portrait">Portrait 4:5</option>
                                                <option value="landscape">Landscape 1.91:1</option>
                                            </select>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Preview fit</label>
                                            <select id="post-fit-control" class="social-story-select" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                                <option value="cover">Fill frame</option>
                                                <option value="contain">Fit whole media</option>
                                            </select>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Size</label>
                                            <input id="post-zoom-control" type="range" min="0.5" max="2.5" step="0.05" value="1" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Horizontal position</label>
                                            <input id="post-offset-x-control" type="range" min="-100" max="100" step="1" value="0" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Vertical position</label>
                                            <input id="post-offset-y-control" type="range" min="-100" max="100" step="1" value="0" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                        </div>
                                    </div>

                                    <div class="social-story-field" id="post-alt-text-field">
                                        <label class="social-story-label">Alt text for image posts</label>
                                        <input
                                            type="text"
                                            name="post_alt_text"
                                            class="social-story-input"
                                            maxlength="1000"
                                            placeholder="Optional accessibility description"
                                            value="{{ old('post_alt_text') }}"
                                            {{ $postPublishEnabled ? '' : 'disabled' }}
                                        >
                                    </div>

                                    <div class="social-story-field">
                                        <label class="social-story-label">Product tags</label>
                                        @php
                                            $selectedPostProductTags = array_map('intval', old('post_product_tags', []));
                                        @endphp

                                        @if (($postTagProducts ?? collect())->isNotEmpty())
                                            <select
                                                name="post_product_tags[]"
                                                class="social-story-select is-multiple"
                                                multiple
                                                size="5"
                                                {{ $postPublishEnabled ? '' : 'disabled' }}
                                            >
                                                @foreach ($postTagProducts as $tagProduct)
                                                    @php
                                                        $tagProductId = trim((string) ($tagProduct->external_product_id ?: $tagProduct->sku));
                                                        $tagProductPrice = $tagProduct->price !== null
                                                            ? number_format((float) $tagProduct->price, 2) . ' ' . strtoupper((string) $tagProduct->currency)
                                                            : 'No price';
                                                    @endphp
                                                    <option
                                                        value="{{ $tagProduct->id }}"
                                                        {{ in_array((int) $tagProduct->id, $selectedPostProductTags, true) ? 'selected' : '' }}
                                                    >
                                                        {{ $tagProduct->title }} · {{ $tagProductId }} · {{ $tagProductPrice }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="social-story-help">
                                                Select up to 5 synced catalog products. Image tags are placed in the center until the visual tag picker is added.
                                            </div>
                                        @else
                                            <div class="social-story-help">
                                                No synced Meta catalog products are ready for this Instagram account yet.
                                            </div>
                                        @endif

                                        @error('post_product_tags')
                                            <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <label class="social-story-confirm">
                                        <input type="checkbox" name="post_confirmed" value="1" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                        <span>I confirm the preview framing and caption are ready to publish.</span>
                                    </label>

                                    <div class="social-story-limits">
                                        <div>Feed images are converted to JPEG and kept inside Instagram’s 4:5 to 1.91:1 aspect range.</div>
                                        <div>Video posts are published as Reels with feed sharing enabled. Videos must be 3 seconds to 15 minutes.</div>
                                        <div>Captions support up to 2,200 characters, 30 hashtags, and 20 @mentions.</div>
                                    </div>

                                    <div class="social-story-progress" id="social-post-progress">
                                        <div class="social-story-progress-track">
                                            <div class="social-story-progress-bar" id="social-post-progress-bar"></div>
                                        </div>
                                        <div class="social-story-progress-text" id="social-post-progress-text">
                                            Waiting for media
                                        </div>
                                    </div>

                                    <div class="social-story-help" id="social-post-message">
                                        Current status: {{ $postPublishEnabled ? 'ready to publish' : 'Instagram connection required' }}.
                                    </div>

                                    <div class="social-story-actions">
                                        <button type="submit" class="social-post-action-button" {{ $postPublishEnabled ? '' : 'disabled' }}>
                                            Publish post
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    @if (($posts ?? collect())->isEmpty())
                        <div class="social-placeholder-grid">
                            <div class="social-placeholder-box">
                                <div class="social-placeholder-label">No posts stored yet</div>
                                <div class="social-placeholder-text">
                                    No posts are stored for this Instagram account yet.
                                </div>
                            </div>

                            <div class="social-placeholder-box">
                                <div class="social-placeholder-label">Sync source</div>
                                <div class="social-placeholder-text">
                                    Posts are loaded separately for each connected Instagram account.
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

                                                @if ($coverMediaUrl && $isVideoLike)
                                                    <video
                                                        src="{{ $coverMediaUrl }}"
                                                        class="social-post-image"
                                                        muted
                                                        playsinline
                                                        preload="metadata"
                                                    ></video>
                                                @elseif ($coverMediaUrl)
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

                                        <div class="social-post-compact-row">
                                            <div class="social-post-stats">
                                                <span class="social-post-badge" data-post-like-count="{{ $post->id }}">♥ {{ $post->like_count }}</span>
                                                <span class="social-post-badge" data-post-comment-count="{{ $post->id }}">💬 {{ $totalCommentsCount }}</span>

                                                @if ($isCarousel)
                                                    <span class="social-post-badge">Slides: {{ $mediaItems->count() }}</span>
                                                @elseif ($isVideoLike)
                                                    <span class="social-post-badge">{{ $isReel ? 'Reel' : 'Video' }}</span>
                                                @endif
                                            </div>

                                            <div class="social-post-actions">
                                                <button
                                                    type="button"
                                                    class="social-post-action-button"
                                                    onclick="window.socialToggleComments && window.socialToggleComments('{{ $commentsCollapseId }}')"
                                                >
                                                    Comments
                                                </button>

                                                @if ($post->permalink)
                                                    <a href="{{ $post->permalink }}" target="_blank" rel="noreferrer" class="social-post-link">
                                                        Instagram
                                                    </a>
                                                @endif

                                                <form
                                                    method="POST"
                                                    action="{{ route('social.instagram.posts.delete', $post) }}"
                                                    class="social-inline-post-form"
                                                    style="display:inline;"
                                                    data-confirm-message="Delete this post from Instagram and remove it from Social?"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                    <button type="submit" class="social-post-action-button danger">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div id="{{ $commentsCollapseId }}" class="social-comments-panel" hidden>
                                            @if ($postCommentSyncError)
                                                <div class="social-sync-banner error" style="margin-bottom:0;">
                                                    Comment sync failed for this post: {{ $postCommentSyncError }}
                                                </div>
                                            @endif

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
                                                        $commentRaw = is_array($comment->raw) ? $comment->raw : [];
                                                        $commentAvatarUrl = $commentRaw['profile_pic']
                                                            ?? $commentRaw['profile_picture_url']
                                                            ?? $commentRaw['from']['profile_pic']
                                                            ?? $commentRaw['from']['profile_picture_url']
                                                            ?? null;
                                                        $commentAvatarUrl = is_string($commentAvatarUrl) && trim($commentAvatarUrl) !== ''
                                                            ? trim($commentAvatarUrl)
                                                            : 'https://ui-avatars.com/api/?name=' . urlencode($commentAuthor) . '&background=e2e8f0&color=334155';
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
                                                        $childReplies = $comment->childComments ?? collect();
                                                    @endphp
                                                        <div class="social-comment-card">
                                                            <div class="social-comment-top">
                                                                <div class="social-comment-main">
                                                                    <div class="social-comment-avatar">
                                                                        <img src="{{ $commentAvatarUrl }}" alt="{{ $commentAuthor }}">
                                                                    </div>
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

                                                                @if ($lastPublicReplyText && $childReplies->isEmpty())
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

                                                            @if ($childReplies->isNotEmpty())
                                                                <div class="social-comment-thread">
                                                                    @foreach ($childReplies as $childReply)
                                                                        @php
                                                                            $childReplyAuthor = $childReply->username ?: ($activeInstagramConnection?->provider_account_name ?: 'Reply');
                                                                        @endphp
                                                                        <div class="social-comment-child">
                                                                            <div class="social-comment-child-top">
                                                                                <span class="social-comment-child-author">{{ $childReplyAuthor }}</span>
                                                                                <span class="social-comment-child-date">
                                                                                    {{ $childReply->commented_at ? $childReply->commented_at->format('Y-m-d H:i') : 'No date' }}
                                                                                </span>
                                                                            </div>
                                                                            <div class="social-comment-child-text">
                                                                                {{ $childReply->text ?: 'No reply text available.' }}
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            <div class="social-comment-actions">
                                                                <button
                                                                    type="button"
                                                                    class="social-comment-action-button social-comment-icon-button"
                                                                    title="Reply"
                                                                    aria-label="Reply"
                                                                    onclick="const form = document.getElementById('{{ $replyFormId }}'); const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const action = form ? form.querySelector('[data-reply-action-input]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = false; form.action = '{{ route('social.instagram.comments.reply', $comment) }}'; if (mode) mode.textContent = 'Public reply'; if (action) action.value = '{{ route('social.instagram.comments.reply', $comment) }}'; if (button) button.textContent = 'Send'; if (input) { input.placeholder = 'Write your public reply here...'; input.focus(); } }"
                                                                >
                                                                    ↩
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    class="social-comment-action-button social-comment-icon-button"
                                                                    title="Reply via DM"
                                                                    aria-label="Reply via DM"
                                                                    onclick="const form = document.getElementById('{{ $replyFormId }}'); const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const action = form ? form.querySelector('[data-reply-action-input]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = false; form.action = '{{ route('social.instagram.comments.reply_dm', $comment) }}'; if (mode) mode.textContent = 'DM reply'; if (action) action.value = '{{ route('social.instagram.comments.reply_dm', $comment) }}'; if (button) button.textContent = 'Send'; if (input) { input.placeholder = 'Write your private DM reply here...'; input.focus(); } }"
                                                                >
                                                                    ✉
                                                                </button>

                                                                <form
                                                                    method="POST"
                                                                    action="{{ $comment->is_hidden ? route('social.instagram.comments.unhide', $comment) : route('social.instagram.comments.hide', $comment) }}"
                                                                    style="display:inline;"
                                                                    class="social-inline-comment-form"
                                                                    data-panel-id="{{ $commentsCollapseId }}"
                                                                >
                                                                    @csrf
                                                                    <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                                    <button
                                                                        type="submit"
                                                                        class="social-comment-action-button social-comment-icon-button"
                                                                        title="{{ $comment->is_hidden ? 'Unhide' : 'Hide' }}"
                                                                        aria-label="{{ $comment->is_hidden ? 'Unhide' : 'Hide' }}"
                                                                    >
                                                                        {{ $comment->is_hidden ? '◉' : '◎' }}
                                                                    </button>
                                                                </form>

                                                                <form method="POST" action="{{ route('social.instagram.comments.delete', $comment) }}" style="display:inline;" class="social-inline-comment-form" data-panel-id="{{ $commentsCollapseId }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                                    <button
                                                                        type="submit"
                                                                        class="social-comment-action-button social-comment-icon-button"
                                                                        title="Delete"
                                                                        aria-label="Delete"
                                                                    >
                                                                        ×
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
                                                                <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                                <span class="social-post-badge" data-reply-mode-label hidden>Public reply</span>
                                                                <input type="hidden" name="_reply_action" value="{{ route('social.instagram.comments.reply', $comment) }}" data-reply-action-input>
                                                                <div class="social-comment-reply-actions">
                                                                    <input
                                                                        type="text"
                                                                        name="reply_text"
                                                                        class="social-comment-reply-textarea"
                                                                        placeholder="Write your public reply here..."
                                                                        required
                                                                    >
                                                                    <button type="submit" class="social-comment-action-button" data-reply-submit-button>
                                                                        Send
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
                                    .catch(function (error) {
                                        console.error('social inline form submit failed', error);
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

                            const ensureSocialActionConfirmModal = function () {
                                let modal = document.getElementById('social-action-confirm-modal');

                                if (!modal) {
                                    modal = document.createElement('div');
                                    modal.id = 'social-action-confirm-modal';
                                    modal.className = 'social-story-confirm-modal';
                                    modal.setAttribute('aria-hidden', 'true');
                                    modal.innerHTML = `
                                        <div class="social-story-confirm-backdrop" data-social-action-confirm-close></div>
                                        <div class="social-story-confirm-card" role="dialog" aria-modal="true" aria-labelledby="social-action-confirm-title">
                                            <div id="social-action-confirm-title" class="social-story-confirm-title">Confirm action</div>
                                            <div id="social-action-confirm-body" class="social-story-confirm-body">Please confirm this action.</div>
                                            <div class="social-story-confirm-actions">
                                                <button type="button" class="social-story-confirm-button" id="social-action-confirm-cancel">Cancel</button>
                                                <button type="button" class="social-story-confirm-button primary" id="social-action-confirm-submit">Confirm</button>
                                            </div>
                                        </div>
                                    `;
                                    document.body.appendChild(modal);
                                }

                                return {
                                    modal,
                                    title: document.getElementById('social-action-confirm-title'),
                                    body: document.getElementById('social-action-confirm-body'),
                                    cancel: document.getElementById('social-action-confirm-cancel'),
                                    submit: document.getElementById('social-action-confirm-submit'),
                                };
                            };

                            window.socialConfirmAction = function (options = {}) {
                                return new Promise(function (resolve) {
                                    const refs = ensureSocialActionConfirmModal();
                                    const openedAt = Date.now();
                                    const confirmDelayMs = 900;

                                    if (!refs.modal || !refs.title || !refs.body || !refs.cancel || !refs.submit) {
                                        resolve(false);
                                        return;
                                    }

                                    const previousKeydownHandler = refs.modal._socialConfirmKeydownHandler;

                                    if (previousKeydownHandler) {
                                        document.removeEventListener('keydown', previousKeydownHandler);
                                    }

                                    let settled = false;
                                    const settle = function (value) {
                                        if (settled) {
                                            return;
                                        }

                                        settled = true;
                                        refs.modal.classList.remove('is-open');
                                        refs.modal.setAttribute('aria-hidden', 'true');
                                        refs.submit.disabled = false;
                                        refs.cancel.onclick = null;
                                        refs.submit.onclick = null;
                                        refs.modal.onclick = null;

                                        if (refs.modal._socialConfirmKeydownHandler) {
                                            document.removeEventListener('keydown', refs.modal._socialConfirmKeydownHandler);
                                            refs.modal._socialConfirmKeydownHandler = null;
                                        }

                                        resolve(value);
                                    };

                                    refs.title.textContent = options.title || 'Confirm action';
                                    refs.body.textContent = options.message || 'Please confirm this action.';
                                    refs.submit.textContent = options.submitText || 'Confirm';
                                    refs.submit.disabled = true;
                                    refs.modal.classList.add('is-open');
                                    refs.modal.setAttribute('aria-hidden', 'false');

                                    setTimeout(function () {
                                        if (!settled && refs.modal.classList.contains('is-open')) {
                                            refs.submit.disabled = false;
                                        }
                                    }, confirmDelayMs);

                                    refs.cancel.onclick = function (event) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        settle(false);
                                    };

                                    refs.submit.onclick = function (event) {
                                        event.preventDefault();
                                        event.stopPropagation();

                                        if (Date.now() - openedAt < confirmDelayMs) {
                                            return;
                                        }

                                        refs.submit.disabled = true;
                                        settle(true);
                                    };

                                    refs.modal.onclick = function (event) {
                                        if (event.target.closest('[data-social-action-confirm-close]')) {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            settle(false);
                                        }
                                    };

                                    refs.modal._socialConfirmKeydownHandler = function (event) {
                                        if (!refs.modal.classList.contains('is-open')) {
                                            return;
                                        }

                                        if (event.key === 'Escape') {
                                            event.preventDefault();
                                            settle(false);
                                        }
                                    };

                                    document.addEventListener('keydown', refs.modal._socialConfirmKeydownHandler);

                                    requestAnimationFrame(function () {
                                        refs.cancel.focus({ preventScroll: true });
                                    });
                                });
                            };

                            window.socialBindInlineCommentActions = function () {
                                document.querySelectorAll('.social-inline-comment-form, .social-inline-post-form').forEach(function (form) {
                                    if (form.dataset.boundSocialForm === '1') {
                                        return;
                                    }

                                    form.dataset.boundSocialForm = '1';
                                    form.addEventListener('submit', async function (event) {
                                        event.preventDefault();
                                        event.stopPropagation();

                                        const confirmMessage = form.getAttribute('data-confirm-message');
                                        if (confirmMessage) {
                                            const confirmed = await window.socialConfirmAction({
                                                title: form.getAttribute('data-confirm-title') || 'Confirm action',
                                                message: confirmMessage,
                                                submitText: form.getAttribute('data-confirm-submit') || 'Confirm',
                                            });

                                            if (!confirmed) {
                                                return;
                                            }
                                        }

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
                    <div id="social-comments-root">
                        <h3 class="social-section-title">Comments moderation — {{ $socialCounts['comments'] }} stored comments</h3>

                        @if (($comments ?? collect())->isEmpty())
                            <div class="social-placeholder-grid">
                                <div class="social-placeholder-box">
                                    <div class="social-placeholder-label">No comments stored yet</div>
                                    <div class="social-placeholder-text">
                                        Instagram is connected and posts can sync, but no comment records are available in this workspace yet. New comment webhooks will be stored here, and page refresh sync will also check recent posts.
                                    </div>
                                </div>

                                <div class="social-placeholder-box">
                                    <div class="social-placeholder-label">Webhook check</div>
                                    <div class="social-placeholder-text">
                                        If real Instagram comments still do not arrive here, the Meta app needs the Instagram webhook fields for comments and messages subscribed for the published app.
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="social-comment-list" style="max-height:none;">
                                @foreach ($comments as $comment)
                                    @php
                                        $replyFormId = 'comments-tab-reply-form-' . $comment->id;
                                        $commentAuthor = $comment->username ?: 'Instagram user';
                                        $commentPost = $comment->socialPost;
                                        $commentRaw = is_array($comment->raw) ? $comment->raw : [];
                                        $commentAvatarUrl = $commentRaw['profile_pic']
                                            ?? $commentRaw['profile_picture_url']
                                            ?? $commentRaw['from']['profile_pic']
                                            ?? $commentRaw['from']['profile_picture_url']
                                            ?? null;
                                        $commentAvatarUrl = is_string($commentAvatarUrl) && trim($commentAvatarUrl) !== ''
                                            ? trim($commentAvatarUrl)
                                            : 'https://ui-avatars.com/api/?name=' . urlencode($commentAuthor) . '&background=e2e8f0&color=334155';
                                        $lastPublicReplyText = is_string($commentRaw['last_public_reply_text'] ?? null)
                                            ? trim($commentRaw['last_public_reply_text'])
                                            : null;
                                        $lastDmReplyText = is_string($commentRaw['last_dm_reply_text'] ?? null)
                                            ? trim($commentRaw['last_dm_reply_text'])
                                            : null;
                                        $childReplies = $comment->childComments ?? collect();
                                    @endphp

                                    <div class="social-comment-card">
                                        <div class="social-comment-top">
                                            <div class="social-comment-main">
                                                <div class="social-comment-avatar">
                                                    <img src="{{ $commentAvatarUrl }}" alt="{{ $commentAuthor }}">
                                                </div>
                                                <div class="social-comment-author">{{ $commentAuthor }}</div>
                                                <div class="social-comment-text">
                                                    {{ $comment->text ?: 'No comment text available.' }}
                                                </div>
                                            </div>

                                            <div class="social-comment-date">
                                                {{ $comment->commented_at ? $comment->commented_at->format('Y-m-d H:i') : 'No date' }}
                                            </div>
                                        </div>

                                        <div class="social-comment-reply-note">
                                            <span class="social-post-badge">
                                                {{ $comment->is_hidden ? 'Hidden' : 'Visible' }}
                                            </span>
                                            <span>
                                                Post: <strong>{{ $commentPost?->caption ? \Illuminate\Support\Str::limit($commentPost->caption, 90) : ($comment->provider_media_id ?: 'Unknown post') }}</strong>
                                            </span>
                                        </div>

                                        @if ($lastPublicReplyText && $childReplies->isEmpty())
                                            <div class="social-comment-reply-note">
                                                <span class="social-post-badge">Public reply</span>
                                                <span>{{ $lastPublicReplyText }}</span>
                                            </div>
                                        @endif

                                        @if ($lastDmReplyText)
                                            <div class="social-comment-reply-note">
                                                <span class="social-post-badge">DM sent</span>
                                                <span>{{ $lastDmReplyText }}</span>
                                            </div>
                                        @endif

                                        @if ($childReplies->isNotEmpty())
                                            <div class="social-comment-thread">
                                                @foreach ($childReplies as $childReply)
                                                    @php
                                                        $childReplyAuthor = $childReply->username ?: ($activeInstagramConnection?->provider_account_name ?: 'Reply');
                                                    @endphp
                                                    <div class="social-comment-child">
                                                        <div class="social-comment-child-top">
                                                            <span class="social-comment-child-author">{{ $childReplyAuthor }}</span>
                                                            <span class="social-comment-child-date">
                                                                {{ $childReply->commented_at ? $childReply->commented_at->format('Y-m-d H:i') : 'No date' }}
                                                            </span>
                                                        </div>
                                                        <div class="social-comment-child-text">
                                                            {{ $childReply->text ?: 'No reply text available.' }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="social-comment-actions">
                                            <button
                                                type="button"
                                                class="social-comment-action-button social-comment-icon-button"
                                                title="Reply"
                                                aria-label="Reply"
                                                onclick="const form = document.getElementById('{{ $replyFormId }}'); const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = false; form.action = '{{ route('social.instagram.comments.reply', $comment) }}'; if (mode) mode.textContent = 'Public reply'; if (button) button.textContent = 'Send'; if (input) { input.placeholder = 'Write your public reply here...'; input.focus(); } }"
                                            >
                                                ↩
                                            </button>

                                            <button
                                                type="button"
                                                class="social-comment-action-button social-comment-icon-button"
                                                title="Reply via DM"
                                                aria-label="Reply via DM"
                                                onclick="const form = document.getElementById('{{ $replyFormId }}'); const mode = form ? form.querySelector('[data-reply-mode-label]') : null; const button = form ? form.querySelector('[data-reply-submit-button]') : null; const input = form ? form.querySelector('input[name=reply_text]') : null; if (form) { form.hidden = false; form.action = '{{ route('social.instagram.comments.reply_dm', $comment) }}'; if (mode) mode.textContent = 'DM reply'; if (button) button.textContent = 'Send'; if (input) { input.placeholder = 'Write your private DM reply here...'; input.focus(); } }"
                                            >
                                                ✉
                                            </button>

                                            <form method="POST" action="{{ $comment->is_hidden ? route('social.instagram.comments.unhide', $comment) : route('social.instagram.comments.hide', $comment) }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="return_tab" value="comments">
                                                <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                <button
                                                    type="submit"
                                                    class="social-comment-action-button social-comment-icon-button"
                                                    title="{{ $comment->is_hidden ? 'Unhide' : 'Hide' }}"
                                                    aria-label="{{ $comment->is_hidden ? 'Unhide' : 'Hide' }}"
                                                >
                                                    {{ $comment->is_hidden ? '◉' : '◎' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('social.instagram.comments.delete', $comment) }}" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_tab" value="comments">
                                                <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                <button
                                                    type="submit"
                                                    class="social-comment-action-button social-comment-icon-button"
                                                    title="Delete"
                                                    aria-label="Delete"
                                                >
                                                    ×
                                                </button>
                                            </form>
                                        </div>

                                        <form
                                            id="{{ $replyFormId }}"
                                            method="POST"
                                            action="{{ route('social.instagram.comments.reply', $comment) }}"
                                            class="social-comment-reply-form"
                                            hidden
                                        >
                                            @csrf
                                            <input type="hidden" name="return_tab" value="comments">
                                            <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                            <span class="social-post-badge" data-reply-mode-label hidden>Public reply</span>
                                            <div class="social-comment-reply-actions">
                                                <input
                                                    type="text"
                                                    name="reply_text"
                                                    class="social-comment-reply-textarea"
                                                    placeholder="Write your public reply here..."
                                                    required
                                                >
                                                <button type="submit" class="social-comment-action-button" data-reply-submit-button>
                                                    Send
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div id="social-stories-root">
                        @php
                            $archivedStories = $archivedStories ?? collect();
                            $storyArchiveCount = $socialCounts['story_archives'] ?? $archivedStories->count();
                        @endphp

                        <h3 class="social-section-title">
                            Stories — {{ $socialCounts['stories'] }} active stories · {{ $storyArchiveCount }} archived
                        </h3>

                        @if (!empty($storySyncError))
                            <div class="social-sync-banner error">
                                Story sync failed: {{ $storySyncError }}
                            </div>
                        @endif

                        @if (($stories ?? collect())->isNotEmpty())
                            <div class="social-story-tray" aria-label="Instagram story tray">
                                @foreach ($stories->take(16) as $story)
                                    @php
                                        $storyRaw = is_array($story->raw) ? $story->raw : [];
                                        $storyMediaUrl = $story->thumbnail_url ?: $story->media_url;
                                        $storyMediaType = strtoupper((string) ($storyRaw['media_type'] ?? $storyRaw['remote_story']['media_type'] ?? 'IMAGE'));
                                        $storyEngagement = ($storyEngagements ?? [])[$story->provider_story_id] ?? ['reply_count' => 0, 'like_count' => 0, 'likers' => []];
                                    @endphp
                                    <button
                                        type="button"
                                        class="social-story-ring"
                                        data-story-view-url="{{ $story->media_url ?: $story->thumbnail_url }}"
                                        data-story-view-type="{{ $storyMediaType }}"
                                        data-story-view-title="{{ $activeInstagramConnection?->provider_account_name ?: 'Instagram Story' }}"
                                        data-story-view-meta="{{ $story->posted_at ? $story->posted_at->format('Y-m-d H:i') : 'Published Story' }}"
                                        data-story-view-likes="{{ $storyEngagement['like_count'] ?? 0 }}"
                                        data-story-view-replies="{{ $storyEngagement['reply_count'] ?? 0 }}"
                                        data-story-view-likers="{{ collect($storyEngagement['likers'] ?? [])->pluck('name')->take(8)->implode(', ') }}"
                                    >
                                        <div class="social-story-ring-media">
                                            <div class="social-story-ring-media-inner">
                                                @if ($storyMediaUrl && $storyMediaType === 'VIDEO')
                                                    <video muted playsinline preload="metadata" src="{{ $storyMediaUrl }}"></video>
                                                @elseif ($storyMediaUrl)
                                                    <img src="{{ $storyMediaUrl }}" alt="Story thumbnail">
                                                @endif
                                            </div>
                                        </div>
                                        <span>{{ $activeInstagramConnection?->provider_account_name ?: 'Story' }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="social-placeholder-box" style="margin-bottom:14px;">
                            <div class="social-story-composer-grid">
                                <div>
                                    <label class="social-story-label">Story preview</label>
                                    <div class="social-story-preview-box" id="story-preview-box">
                                        <div class="social-story-preview-empty" id="story-preview-empty">
                                            9:16 Instagram Story preview
                                        </div>
                                        <img id="story-preview-image" class="social-story-preview-image" alt="Story preview" hidden>
                                        <video id="story-preview-video" class="social-story-preview-video" controls playsinline hidden></video>
                                    </div>
                                </div>

                                <form
                                    id="social-story-publish-form"
                                    method="POST"
                                    action="{{ route('social.instagram.stories.publish') }}"
                                    class="social-story-form"
                                    enctype="multipart/form-data"
                                >
                                    @csrf
                                    <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                    <input type="hidden" name="story_fit" value="cover">
                                    <input type="hidden" name="story_zoom" value="1">
                                    <input type="hidden" name="story_offset_x" value="0">
                                    <input type="hidden" name="story_offset_y" value="0">

                                    <div class="social-placeholder-label">Publish Story</div>

                                    <div class="social-story-form-grid">
                                        <div class="social-story-field">
                                            <label class="social-story-label">Media type</label>
                                            <select
                                                name="media_type"
                                                class="social-story-select"
                                                {{ $storyPublishEnabled ? '' : 'disabled' }}
                                            >
                                                <option value="IMAGE" {{ old('media_type') === 'IMAGE' ? 'selected' : '' }}>Image story</option>
                                                <option value="VIDEO" {{ old('media_type') === 'VIDEO' ? 'selected' : '' }}>Video story</option>
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
                                                accept="image/*,video/*"
                                                {{ $storyPublishEnabled ? '' : 'disabled' }}
                                            >
                                            @error('story_file')
                                                <div class="social-story-help" style="color:#b91c1c;">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="social-story-field">
                                        <label class="social-story-label">Media URL fallback</label>
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

                                    <div class="social-story-preview-controls">
                                        <div class="social-story-field">
                                            <label class="social-story-label">Preview fit</label>
                                            <select id="story-fit-control" class="social-story-select" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                                <option value="cover">Fill 9:16 frame</option>
                                                <option value="contain">Fit whole media</option>
                                            </select>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Size</label>
                                            <input id="story-zoom-control" type="range" min="0.5" max="2.5" step="0.05" value="1" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Horizontal position</label>
                                            <input id="story-offset-x-control" type="range" min="-100" max="100" step="1" value="0" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                        </div>

                                        <div class="social-story-field">
                                            <label class="social-story-label">Vertical position</label>
                                            <input id="story-offset-y-control" type="range" min="-100" max="100" step="1" value="0" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                        </div>
                                    </div>

                                    <label class="social-story-confirm">
                                        <input type="checkbox" name="story_confirmed" value="1" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                        <span>I confirm the preview framing is ready to publish.</span>
                                    </label>

                                    <div class="social-story-limits">
                                        <div>Use one media item only. Final media is normalized to 1080 × 1920, 9:16.</div>
                                        <div>Images are converted to JPEG. Videos are converted to MP4 when the server supports conversion.</div>
                                        <div>Instagram API publishing does not support Story text overlays, stickers, links, polls, music, or captions.</div>
                                    </div>

                                    <div class="social-story-progress" id="social-story-progress">
                                        <div class="social-story-progress-track">
                                            <div class="social-story-progress-bar" id="social-story-progress-bar"></div>
                                        </div>
                                        <div class="social-story-progress-text" id="social-story-progress-text">
                                            Waiting for media
                                        </div>
                                    </div>

                                    <div class="social-story-help" id="social-story-message">
                                        Current status: {{ $storyPublishEnabled ? 'ready to publish' : 'Instagram connection required' }}.
                                    </div>

                                    <div class="social-story-actions">
                                        <button type="submit" class="social-post-action-button" {{ $storyPublishEnabled ? '' : 'disabled' }}>
                                            Publish story
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if (($stories ?? collect())->isEmpty() && $archivedStories->isEmpty())
                            <div class="social-placeholder-grid">
                                <div class="social-placeholder-box">
                                    <div class="social-placeholder-label">No active stories yet</div>
                                    <div class="social-placeholder-text">
                                        Your connected account Stories will appear here after publishing or syncing. Instagram Stories expire after 24 hours.
                                    </div>
                                </div>

                                <div class="social-placeholder-box">
                                    <div class="social-placeholder-label">Follower Stories</div>
                                    <div class="social-placeholder-text">
                                        Instagram’s official API does not expose the full follower Story feed. This area stays focused on connected account Stories.
                                    </div>
                                </div>
                            </div>
                        @else
                            @if (($stories ?? collect())->isNotEmpty())
                                <div class="social-story-section-header">
                                    <h4>Active Stories</h4>
                                    <span>Visible Stories published in the last 24 hours.</span>
                                </div>

                                <div class="social-story-grid" id="social-story-grid">
                                    @foreach ($stories as $story)
                                        @php
                                            $storyRaw = is_array($story->raw) ? $story->raw : [];
                                            $storyMediaUrl = $story->media_url ?: $story->thumbnail_url;
                                            $storyMediaType = strtoupper((string) ($storyRaw['media_type'] ?? $storyRaw['remote_story']['media_type'] ?? 'IMAGE'));
                                            $storyStatus = strtolower((string) ($story->status ?: 'draft'));
                                            $storyEngagement = ($storyEngagements ?? [])[$story->provider_story_id] ?? ['reply_count' => 0, 'like_count' => 0, 'likers' => []];
                                        @endphp
                                        <div class="social-story-card">
                                            <div class="social-story-media">
                                                <button
                                                    type="button"
                                                    class="social-story-view-button"
                                                    data-story-view-url="{{ $storyMediaUrl }}"
                                                    data-story-view-type="{{ $storyMediaType }}"
                                                    data-story-view-title="{{ $activeInstagramConnection?->provider_account_name ?: 'Instagram Story' }}"
                                                    data-story-view-meta="{{ $story->posted_at ? $story->posted_at->format('Y-m-d H:i') : 'Published Story' }}"
                                                    data-story-view-likes="{{ $storyEngagement['like_count'] ?? 0 }}"
                                                    data-story-view-replies="{{ $storyEngagement['reply_count'] ?? 0 }}"
                                                    data-story-view-likers="{{ collect($storyEngagement['likers'] ?? [])->pluck('name')->take(8)->implode(', ') }}"
                                                >
                                                    @if ($storyMediaUrl && $storyMediaType === 'VIDEO')
                                                        <video
                                                            src="{{ $storyMediaUrl }}"
                                                            class="social-story-video"
                                                            muted
                                                            playsinline
                                                            preload="metadata"
                                                        ></video>
                                                    @elseif ($storyMediaUrl)
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
                                                </button>
                                            </div>

                                            <div class="social-story-body">
                                                <div class="social-story-meta">
                                                    <span class="social-post-badge social-story-status-badge {{ $storyStatus }}">
                                                        {{ ucfirst($storyStatus) }}
                                                    </span>
                                                    <span class="social-post-badge">{{ ucfirst(strtolower($storyMediaType)) }}</span>
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

                                                <div class="social-story-meta">
                                                    <span class="social-post-badge">♥ {{ $storyEngagement['like_count'] ?? 0 }} captured likes</span>
                                                    <span class="social-post-badge">↩ {{ $storyEngagement['reply_count'] ?? 0 }} replies</span>
                                                </div>

                                                @if (!empty($storyEngagement['likers']))
                                                    <div class="social-story-help">
                                                        Liked by {{ collect($storyEngagement['likers'])->pluck('name')->take(5)->implode(', ') }}
                                                    </div>
                                                @endif

                                                <div class="social-story-actions">
                                                    <form
                                                        method="POST"
                                                        action="{{ route('social.instagram.stories.delete', $story) }}"
                                                        data-story-delete-form
                                                        data-confirm-title="Remove Story"
                                                        data-confirm-message="Remove this Story from the Leadochat list? This will not delete the Story from Instagram or the archive record."
                                                        data-confirm-submit="Remove"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="instagram_account" value="{{ $selectedInstagramAccountId }}">
                                                        <button type="submit" class="social-post-action-button">
                                                            Remove from list
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($archivedStories->isNotEmpty())
                                <div class="social-story-archive-section">
                                    <div class="social-story-section-header">
                                        <h4>Story Archive</h4>
                                        <span>Stories move here automatically after 24 hours and stay available for review.</span>
                                    </div>

                                    <div class="social-story-grid">
                                        @foreach ($archivedStories as $story)
                                            @php
                                                $storyRaw = is_array($story->raw) ? $story->raw : [];
                                                $storyMediaUrl = $story->media_url ?: $story->thumbnail_url;
                                                $storyMediaType = strtoupper((string) ($storyRaw['media_type'] ?? $storyRaw['remote_story']['media_type'] ?? 'IMAGE'));
                                                $storyStatus = strtolower((string) ($story->status ?: 'archived'));
                                                $storyEngagement = ($storyEngagements ?? [])[$story->provider_story_id] ?? ['reply_count' => 0, 'like_count' => 0, 'likers' => []];
                                            @endphp
                                            <div class="social-story-card">
                                                <div class="social-story-media">
                                                    <button
                                                        type="button"
                                                        class="social-story-view-button"
                                                        data-story-view-url="{{ $storyMediaUrl }}"
                                                        data-story-view-type="{{ $storyMediaType }}"
                                                        data-story-view-title="{{ $activeInstagramConnection?->provider_account_name ?: 'Instagram Story' }}"
                                                        data-story-view-meta="{{ $story->posted_at ? $story->posted_at->format('Y-m-d H:i') : 'Archived Story' }}"
                                                        data-story-view-likes="{{ $storyEngagement['like_count'] ?? 0 }}"
                                                        data-story-view-replies="{{ $storyEngagement['reply_count'] ?? 0 }}"
                                                        data-story-view-likers="{{ collect($storyEngagement['likers'] ?? [])->pluck('name')->take(8)->implode(', ') }}"
                                                    >
                                                        @if ($storyMediaUrl && $storyMediaType === 'VIDEO')
                                                            <video
                                                                src="{{ $storyMediaUrl }}"
                                                                class="social-story-video"
                                                                muted
                                                                playsinline
                                                                preload="metadata"
                                                            ></video>
                                                        @elseif ($storyMediaUrl)
                                                            <img
                                                                src="{{ $storyMediaUrl }}"
                                                                alt="Archived Instagram story {{ $story->provider_story_id }}"
                                                                class="social-story-image"
                                                            >
                                                        @else
                                                            <div class="social-story-fallback">
                                                                Story preview is not available yet
                                                            </div>
                                                        @endif
                                                    </button>
                                                </div>

                                                <div class="social-story-body">
                                                    <div class="social-story-meta">
                                                        <span class="social-post-badge social-story-status-badge {{ $storyStatus }}">
                                                            Archived
                                                        </span>
                                                        <span class="social-post-badge">{{ ucfirst(strtolower($storyMediaType)) }}</span>
                                                    </div>

                                                    <div class="social-story-meta">
                                                        <span class="social-post-badge">
                                                            Posted: {{ $story->posted_at ? $story->posted_at->format('Y-m-d H:i') : 'No publish time' }}
                                                        </span>
                                                    </div>

                                                    <div class="social-story-meta">
                                                        <span class="social-post-badge">
                                                            Expired: {{ $story->expires_at ? $story->expires_at->format('Y-m-d H:i') : 'No expiry time' }}
                                                        </span>
                                                    </div>

                                                    <div class="social-story-meta">
                                                        <span class="social-post-badge">♥ {{ $storyEngagement['like_count'] ?? 0 }} captured likes</span>
                                                        <span class="social-post-badge">↩ {{ $storyEngagement['reply_count'] ?? 0 }} replies</span>
                                                    </div>

                                                    @if (!empty($storyEngagement['likers']))
                                                        <div class="social-story-help">
                                                            Liked by {{ collect($storyEngagement['likers'])->pluck('name')->take(5)->implode(', ') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="social-story-viewer" id="social-story-viewer" aria-hidden="true">
                        <div class="social-story-viewer-backdrop" data-story-viewer-close></div>
                        <div class="social-story-viewer-card" role="dialog" aria-modal="true" aria-label="Story viewer">
                            <button type="button" class="social-story-viewer-close" data-story-viewer-close aria-label="Close">×</button>
                            <div class="social-story-viewer-media" id="social-story-viewer-media"></div>
                            <div class="social-story-viewer-info">
                                <strong id="social-story-viewer-title">Instagram Story</strong>
                                <span id="social-story-viewer-meta"></span>
                                <span id="social-story-viewer-engagement"></span>
                                <span id="social-story-viewer-likers"></span>
                            </div>
                        </div>
                    </div>

                    <div class="social-story-confirm-modal" id="social-story-confirm-modal" aria-hidden="true">
                        <div class="social-story-confirm-backdrop" data-story-confirm-close></div>
                        <div class="social-story-confirm-card" role="dialog" aria-modal="true" aria-labelledby="social-story-confirm-title">
                            <div id="social-story-confirm-title" class="social-story-confirm-title">Confirm action</div>
                            <div id="social-story-confirm-body" class="social-story-confirm-body">Please confirm this action.</div>
                            <div class="social-story-confirm-actions">
                                <button type="button" class="social-story-confirm-button" id="social-story-confirm-cancel">Cancel</button>
                                <button type="button" class="social-story-confirm-button primary" id="social-story-confirm-submit">Confirm</button>
                            </div>
                        </div>
                    </div>
                @endif

                <script>
                    (function () {
                        const workspaceId = @json($workspace->id ?? null);
                        const activeTab = @json($tab);
                        const realtimePostsUrl = @json(route('social.instagram.realtime.posts', $accountQuery));

                        let refreshTimer = null;
                        let countersTimer = null;
                        let isRefreshing = false;
                        let refreshQueued = false;
                        let storyPreviewObjectUrl = null;
                        let storyProgressTimers = [];
                        let storyProcessingInterval = null;
                        let postPreviewObjectUrl = null;
                        let postProgressTimers = [];
                        let postProcessingInterval = null;

                        const rememberSocialState = function () {
                            sessionStorage.setItem('social_posts_scroll_y', String(window.scrollY || window.pageYOffset || 0));
                        };

                        const restoreSocialState = function () {
                            if (typeof window.socialRestorePostsState === 'function') {
                                window.socialRestorePostsState(sessionStorage.getItem('social_posts_open_panel'));
                                return;
                            }

                            const savedScrollY = Number(sessionStorage.getItem('social_posts_scroll_y') || 0);
                            window.scrollTo({ top: savedScrollY, left: 0, behavior: 'auto' });
                        };

                        const replaceSocialRootFromHtml = function (html) {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const selectors = ['.social-top-card', '#social-posts-root', '#social-comments-root', '#social-stories-root'];
                            let replaced = false;

                            selectors.forEach(function (selector) {
                                const current = document.querySelector(selector);
                                const next = doc.querySelector(selector);

                                if (current && next) {
                                    current.replaceWith(next);
                                    replaced = true;
                                }
                            });

                            if (!replaced) {
                                return false;
                            }

                            if (typeof window.socialBindInlineCommentActions === 'function') {
                                window.socialBindInlineCommentActions();
                            }

                            bindStoryPublisher();
                            bindPostPublisher();
                            bindStoryViewerAndDeletes();

                            restoreSocialState();
                            return true;
                        };

                        const refreshSocialRoot = async function (options = {}) {
                            if (isRefreshing) {
                                refreshQueued = true;
                                return;
                            }

                            isRefreshing = true;
                            rememberSocialState();

                            try {
                                const response = await fetch(window.location.href, {
                                    method: 'GET',
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'text/html, application/xhtml+xml',
                                    },
                                });

                                if (!response.ok) {
                                    return;
                                }

                                const html = await response.text();
                                replaceSocialRootFromHtml(html);
                            } catch (error) {
                                // Keep the current UI stable; the next websocket or counter tick can retry.
                            } finally {
                                isRefreshing = false;

                                if (refreshQueued) {
                                    refreshQueued = false;
                                    scheduleSocialRefresh(options);
                                }
                            }
                        };

                        const scheduleSocialRefresh = function (options = {}) {
                            clearTimeout(refreshTimer);
                            refreshTimer = setTimeout(function () {
                                refreshSocialRoot(options);
                            }, options.delay || 300);
                        };

                        const updatePostCounters = function (posts) {
                            if (!Array.isArray(posts)) {
                                return;
                            }

                            let missingPost = false;

                            posts.forEach(function (post) {
                                if (!post || !post.id) {
                                    return;
                                }

                                const likeBadge = document.querySelector(`[data-post-like-count="${post.id}"]`);
                                const commentBadge = document.querySelector(`[data-post-comment-count="${post.id}"]`);

                                if (!likeBadge || !commentBadge) {
                                    missingPost = true;
                                    return;
                                }

                                if (typeof post.like_count !== 'undefined') {
                                    likeBadge.textContent = `♥ ${post.like_count}`;
                                }

                                if (typeof post.comments_count !== 'undefined') {
                                    commentBadge.textContent = `💬 ${post.comments_count}`;
                                }
                            });

                            if (missingPost) {
                                scheduleSocialRefresh({ delay: 500 });
                            }
                        };

                        const refreshPostCounters = async function () {
                            if (activeTab !== 'posts' || !realtimePostsUrl) {
                                return;
                            }

                            try {
                                const response = await fetch(realtimePostsUrl, {
                                    method: 'GET',
                                    credentials: 'same-origin',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    },
                                });

                                if (!response.ok && response.status !== 207) {
                                    return;
                                }

                                const data = await response.json();
                                updatePostCounters(data.posts || []);
                            } catch (error) {
                                // Counter refresh is a quiet fallback for likes.
                            }
                        };

                        if (activeTab === 'posts') {
                            countersTimer = setInterval(refreshPostCounters, 15000);
                            setTimeout(refreshPostCounters, 2500);
                        }

                        const setPostProgress = function (percent, text) {
                            const progress = document.getElementById('social-post-progress');
                            const bar = document.getElementById('social-post-progress-bar');
                            const label = document.getElementById('social-post-progress-text');

                            if (!progress || !bar || !label) {
                                return;
                            }

                            progress.classList.add('is-visible');
                            bar.style.width = `${Math.max(0, Math.min(percent, 100))}%`;
                            label.textContent = text;
                        };

                        const clearPostProgressTimers = function () {
                            postProgressTimers.forEach(function (timer) {
                                clearTimeout(timer);
                            });
                            postProgressTimers = [];

                            if (postProcessingInterval) {
                                clearInterval(postProcessingInterval);
                                postProcessingInterval = null;
                            }
                        };

                        const setPostMessage = function (message, isError = false) {
                            const messageBox = document.getElementById('social-post-message');

                            if (!messageBox) {
                                return;
                            }

                            messageBox.textContent = message;
                            messageBox.style.color = isError ? '#b91c1c' : '#64748b';
                            messageBox.style.fontWeight = isError ? '800' : '400';
                        };

                        const resetPostPreview = function () {
                            const form = document.getElementById('social-post-publish-form');
                            const emptyBox = document.getElementById('post-preview-empty');
                            const imagePreview = document.getElementById('post-preview-image');
                            const videoPreview = document.getElementById('post-preview-video');

                            if (form) {
                                form.dataset.postVideoInvalid = '0';
                            }

                            if (postPreviewObjectUrl) {
                                URL.revokeObjectURL(postPreviewObjectUrl);
                                postPreviewObjectUrl = null;
                            }

                            if (imagePreview) {
                                imagePreview.hidden = true;
                                imagePreview.removeAttribute('src');
                            }

                            if (videoPreview) {
                                videoPreview.hidden = true;
                                videoPreview.pause();
                                videoPreview.removeAttribute('src');
                                videoPreview.load();
                            }

                            if (emptyBox) {
                                emptyBox.hidden = false;
                            }
                        };

                        const showPostImage = function (src) {
                            const form = document.getElementById('social-post-publish-form');
                            const emptyBox = document.getElementById('post-preview-empty');
                            const imagePreview = document.getElementById('post-preview-image');
                            const videoPreview = document.getElementById('post-preview-video');

                            if (!src || !imagePreview || !videoPreview || !emptyBox) {
                                resetPostPreview();
                                return;
                            }

                            videoPreview.hidden = true;
                            videoPreview.pause();
                            videoPreview.removeAttribute('src');
                            videoPreview.load();
                            videoPreview.onloadedmetadata = null;
                            imagePreview.src = src;
                            imagePreview.hidden = false;
                            emptyBox.hidden = true;

                            if (form) {
                                form.dataset.postVideoInvalid = '0';
                            }
                        };

                        const showPostVideo = function (src) {
                            const form = document.getElementById('social-post-publish-form');
                            const emptyBox = document.getElementById('post-preview-empty');
                            const imagePreview = document.getElementById('post-preview-image');
                            const videoPreview = document.getElementById('post-preview-video');

                            if (!src || !imagePreview || !videoPreview || !emptyBox) {
                                resetPostPreview();
                                return;
                            }

                            imagePreview.hidden = true;
                            imagePreview.removeAttribute('src');
                            videoPreview.src = src;
                            videoPreview.hidden = false;
                            videoPreview.onloadedmetadata = function () {
                                if (!form || !Number.isFinite(videoPreview.duration)) {
                                    return;
                                }

                                const invalidDuration = videoPreview.duration < 3 || videoPreview.duration > 900;
                                form.dataset.postVideoInvalid = invalidDuration ? '1' : '0';

                                if (invalidDuration) {
                                    setPostMessage('Feed videos must be between 3 seconds and 15 minutes.', true);
                                }
                            };
                            emptyBox.hidden = true;
                        };

                        const detectPostUrlKind = function (url) {
                            const cleanUrl = String(url || '').split('?')[0].toLowerCase();

                            if (/\.(jpg|jpeg)$/i.test(cleanUrl)) {
                                return 'IMAGE';
                            }

                            if (/\.(mp4|mov)$/i.test(cleanUrl)) {
                                return 'VIDEO';
                            }

                            return null;
                        };

                        const updatePostPreview = function () {
                            const form = document.getElementById('social-post-publish-form');
                            const fileInput = form?.querySelector('input[name="post_file"]');
                            const mediaUrlInput = form?.querySelector('input[name="post_media_url"]');
                            const mediaTypeInput = form?.querySelector('select[name="post_media_type"]');
                            const previewBox = document.getElementById('post-preview-box');
                            const aspectControl = document.getElementById('post-aspect-control');
                            const fitControl = document.getElementById('post-fit-control');
                            const zoomControl = document.getElementById('post-zoom-control');
                            const offsetXControl = document.getElementById('post-offset-x-control');
                            const offsetYControl = document.getElementById('post-offset-y-control');
                            const altTextField = document.getElementById('post-alt-text-field');
                            const selectedFile = fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null;
                            const mediaUrl = mediaUrlInput ? mediaUrlInput.value.trim() : '';
                            const selectedMediaType = mediaTypeInput ? mediaTypeInput.value : 'IMAGE';
                            const aspect = selectedMediaType === 'VIDEO' ? 'video' : (aspectControl?.value || 'square');
                            const fit = fitControl?.value || 'cover';
                            const zoom = zoomControl?.value || '1';
                            const offsetX = offsetXControl?.value || '0';
                            const offsetY = offsetYControl?.value || '0';

                            const fitInput = form?.querySelector('input[name="post_fit"]');
                            const zoomInput = form?.querySelector('input[name="post_zoom"]');
                            const offsetXInput = form?.querySelector('input[name="post_offset_x"]');
                            const offsetYInput = form?.querySelector('input[name="post_offset_y"]');

                            if (fitInput) fitInput.value = fit;
                            if (zoomInput) zoomInput.value = zoom;
                            if (offsetXInput) offsetXInput.value = offsetX;
                            if (offsetYInput) offsetYInput.value = offsetY;
                            previewBox?.classList.toggle('is-contain', fit === 'contain');
                            previewBox?.classList.toggle('is-post-square', aspect === 'square');
                            previewBox?.classList.toggle('is-post-portrait', aspect === 'portrait');
                            previewBox?.classList.toggle('is-post-landscape', aspect === 'landscape');
                            previewBox?.classList.toggle('is-post-video', aspect === 'video');
                            previewBox?.style.setProperty('--story-zoom', zoom);
                            previewBox?.style.setProperty('--story-offset-x', `${offsetX}%`);
                            previewBox?.style.setProperty('--story-offset-y', `${offsetY}%`);

                            if (aspectControl) {
                                aspectControl.disabled = selectedMediaType === 'VIDEO' || aspectControl.dataset.forceDisabled === '1';
                            }

                            if (altTextField) {
                                altTextField.hidden = selectedMediaType === 'VIDEO';
                            }

                            if (postPreviewObjectUrl) {
                                URL.revokeObjectURL(postPreviewObjectUrl);
                                postPreviewObjectUrl = null;
                            }

                            if (selectedFile) {
                                postPreviewObjectUrl = URL.createObjectURL(selectedFile);

                                if (String(selectedFile.type || '').startsWith('video/')) {
                                    showPostVideo(postPreviewObjectUrl);
                                } else {
                                    showPostImage(postPreviewObjectUrl);
                                }

                                return;
                            }

                            if (mediaUrl !== '') {
                                const urlKind = detectPostUrlKind(mediaUrl) || selectedMediaType;

                                if (urlKind === 'VIDEO') {
                                    showPostVideo(mediaUrl);
                                } else {
                                    showPostImage(mediaUrl);
                                }

                                return;
                            }

                            resetPostPreview();
                        };

                        const countMatches = function (value, pattern) {
                            return (String(value || '').match(pattern) || []).length;
                        };

                        const bindPostPublisher = function () {
                            const form = document.getElementById('social-post-publish-form');

                            if (!form || form.dataset.bound === '1') {
                                return;
                            }

                            form.dataset.bound = '1';
                            const fileInput = form.querySelector('input[name="post_file"]');
                            const mediaUrlInput = form.querySelector('input[name="post_media_url"]');
                            const mediaTypeInput = form.querySelector('select[name="post_media_type"]');
                            const captionInput = form.querySelector('textarea[name="post_caption"]');
                            const aspectControl = document.getElementById('post-aspect-control');
                            const fitControl = document.getElementById('post-fit-control');
                            const zoomControl = document.getElementById('post-zoom-control');
                            const offsetXControl = document.getElementById('post-offset-x-control');
                            const offsetYControl = document.getElementById('post-offset-y-control');
                            const submitButton = form.querySelector('button[type="submit"]');

                            if (aspectControl && aspectControl.disabled) {
                                aspectControl.dataset.forceDisabled = '1';
                            }

                            fileInput?.addEventListener('change', function () {
                                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

                                if (file && String(file.type || '').startsWith('image/')) {
                                    mediaTypeInput.value = 'IMAGE';
                                }

                                if (file && String(file.type || '').startsWith('video/')) {
                                    mediaTypeInput.value = 'VIDEO';
                                }

                                updatePostPreview();
                            });

                            mediaUrlInput?.addEventListener('input', updatePostPreview);
                            mediaTypeInput?.addEventListener('change', updatePostPreview);
                            aspectControl?.addEventListener('change', updatePostPreview);
                            fitControl?.addEventListener('change', updatePostPreview);
                            zoomControl?.addEventListener('input', updatePostPreview);
                            offsetXControl?.addEventListener('input', updatePostPreview);
                            offsetYControl?.addEventListener('input', updatePostPreview);
                            updatePostPreview();

                            form.addEventListener('submit', function (event) {
                                event.preventDefault();
                                clearPostProgressTimers();

                                const selectedFile = fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null;
                                const confirmed = form.querySelector('input[name="post_confirmed"]')?.checked;
                                const caption = captionInput?.value || '';

                                if (!confirmed) {
                                    setPostMessage('Please confirm the post preview before publishing.', true);
                                    return;
                                }

                                if (caption.length > 2200) {
                                    setPostMessage('Instagram captions must be 2,200 characters or shorter.', true);
                                    return;
                                }

                                if (countMatches(caption, /#[\p{L}\p{N}_]+/gu) > 30) {
                                    setPostMessage('Instagram captions can include up to 30 hashtags.', true);
                                    return;
                                }

                                if (countMatches(caption, /(^|[^\p{L}\p{N}_.])@[\p{L}\p{N}._]+/gu) > 20) {
                                    setPostMessage('Instagram captions can include up to 20 @mentions.', true);
                                    return;
                                }

                                if (selectedFile && mediaTypeInput?.value === 'IMAGE' && selectedFile.size > 20 * 1024 * 1024) {
                                    setPostMessage('Image source files must be 20 MB or smaller before conversion.', true);
                                    return;
                                }

                                if (selectedFile && mediaTypeInput?.value === 'VIDEO' && selectedFile.size > 120 * 1024 * 1024) {
                                    setPostMessage('Video source files must be 120 MB or smaller. Use a public MP4/MOV URL for larger Reels.', true);
                                    return;
                                }

                                if (form.dataset.postVideoInvalid === '1') {
                                    setPostMessage('Feed videos must be between 3 seconds and 15 minutes.', true);
                                    return;
                                }

                                const xhr = new XMLHttpRequest();
                                const formData = new FormData(form);

                                if (submitButton) {
                                    submitButton.disabled = true;
                                }

                                setPostMessage('Uploading post media...');
                                setPostProgress(5, 'Preparing upload');

                                xhr.upload.addEventListener('progress', function (progressEvent) {
                                    if (!progressEvent.lengthComputable) {
                                        setPostProgress(35, 'Uploading media');
                                        return;
                                    }

                                    const uploadPercent = Math.round((progressEvent.loaded / progressEvent.total) * 60) + 5;
                                    setPostProgress(uploadPercent, `Uploading media ${Math.round((progressEvent.loaded / progressEvent.total) * 100)}%`);
                                });

                                xhr.addEventListener('load', function () {
                                    let data = {};

                                    try {
                                        data = JSON.parse(xhr.responseText || '{}');
                                    } catch (error) {
                                        data = {};
                                    }

                                    if (xhr.status >= 200 && xhr.status < 300 && data.ok !== false) {
                                        clearPostProgressTimers();
                                        setPostProgress(100, 'Post published');
                                        setPostMessage(data.message || 'Post published successfully.');
                                        form.reset();
                                        resetPostPreview();
                                        scheduleSocialRefresh({ delay: 500 });
                                        return;
                                    }

                                    clearPostProgressTimers();
                                    setPostProgress(100, 'Publish failed');
                                    setPostMessage(data.message || 'Post publish failed.', true);
                                });

                                xhr.addEventListener('error', function () {
                                    clearPostProgressTimers();
                                    setPostProgress(100, 'Publish failed');
                                    setPostMessage('Post publish failed. Please try again.', true);
                                });

                                xhr.addEventListener('loadend', function () {
                                    if (submitButton) {
                                        submitButton.disabled = false;
                                    }
                                });

                                xhr.open('POST', form.getAttribute('action'));
                                xhr.setRequestHeader('Accept', 'application/json');
                                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                xhr.send(formData);
                                postProgressTimers.push(setTimeout(function () {
                                    setPostProgress(68, 'Converting media for Instagram');
                                }, 800));
                                postProgressTimers.push(setTimeout(function () {
                                    setPostProgress(78, 'Creating Instagram post container');
                                }, 2500));
                                postProgressTimers.push(setTimeout(function () {
                                    setPostProgress(88, 'Waiting for Instagram processing');
                                }, 7000));

                                let waitingPercent = 88;
                                postProcessingInterval = setInterval(function () {
                                    waitingPercent = Math.min(waitingPercent + 1, 96);
                                    setPostProgress(waitingPercent, 'Still waiting for Instagram to finish publishing');
                                }, 6000);
                            });
                        };

                        const setStoryProgress = function (percent, text) {
                            const progress = document.getElementById('social-story-progress');
                            const bar = document.getElementById('social-story-progress-bar');
                            const label = document.getElementById('social-story-progress-text');

                            if (!progress || !bar || !label) {
                                return;
                            }

                            progress.classList.add('is-visible');
                            bar.style.width = `${Math.max(0, Math.min(percent, 100))}%`;
                            label.textContent = text;
                        };

                        const clearStoryProgressTimers = function () {
                            storyProgressTimers.forEach(function (timer) {
                                clearTimeout(timer);
                            });
                            storyProgressTimers = [];

                            if (storyProcessingInterval) {
                                clearInterval(storyProcessingInterval);
                                storyProcessingInterval = null;
                            }
                        };

                        const setStoryMessage = function (message, isError = false) {
                            const messageBox = document.getElementById('social-story-message');

                            if (!messageBox) {
                                return;
                            }

                            messageBox.textContent = message;
                            messageBox.style.color = isError ? '#b91c1c' : '#64748b';
                            messageBox.style.fontWeight = isError ? '800' : '400';
                        };

                        const resetStoryPreview = function () {
                            const form = document.getElementById('social-story-publish-form');
                            const emptyBox = document.getElementById('story-preview-empty');
                            const imagePreview = document.getElementById('story-preview-image');
                            const videoPreview = document.getElementById('story-preview-video');

                            if (form) {
                                form.dataset.storyVideoInvalid = '0';
                            }

                            if (storyPreviewObjectUrl) {
                                URL.revokeObjectURL(storyPreviewObjectUrl);
                                storyPreviewObjectUrl = null;
                            }

                            if (imagePreview) {
                                imagePreview.hidden = true;
                                imagePreview.removeAttribute('src');
                            }

                            if (videoPreview) {
                                videoPreview.hidden = true;
                                videoPreview.pause();
                                videoPreview.removeAttribute('src');
                                videoPreview.load();
                            }

                            if (emptyBox) {
                                emptyBox.hidden = false;
                            }
                        };

                        const showStoryImage = function (src) {
                            const form = document.getElementById('social-story-publish-form');
                            const emptyBox = document.getElementById('story-preview-empty');
                            const imagePreview = document.getElementById('story-preview-image');
                            const videoPreview = document.getElementById('story-preview-video');

                            if (!src || !imagePreview || !videoPreview || !emptyBox) {
                                resetStoryPreview();
                                return;
                            }

                            videoPreview.hidden = true;
                            videoPreview.pause();
                            videoPreview.removeAttribute('src');
                            videoPreview.load();
                            videoPreview.onloadedmetadata = null;
                            imagePreview.src = src;
                            imagePreview.hidden = false;
                            emptyBox.hidden = true;

                            if (form) {
                                form.dataset.storyVideoInvalid = '0';
                            }
                        };

                        const showStoryVideo = function (src) {
                            const form = document.getElementById('social-story-publish-form');
                            const emptyBox = document.getElementById('story-preview-empty');
                            const imagePreview = document.getElementById('story-preview-image');
                            const videoPreview = document.getElementById('story-preview-video');

                            if (!src || !imagePreview || !videoPreview || !emptyBox) {
                                resetStoryPreview();
                                return;
                            }

                            imagePreview.hidden = true;
                            imagePreview.removeAttribute('src');
                            videoPreview.src = src;
                            videoPreview.hidden = false;
                            videoPreview.onloadedmetadata = function () {
                                if (!form || !Number.isFinite(videoPreview.duration)) {
                                    return;
                                }

                                const invalidDuration = videoPreview.duration < 3 || videoPreview.duration > 60;
                                form.dataset.storyVideoInvalid = invalidDuration ? '1' : '0';

                                if (invalidDuration) {
                                    setStoryMessage('Video Stories must be between 3 and 60 seconds.', true);
                                }
                            };
                            emptyBox.hidden = true;
                        };

                        const detectStoryUrlKind = function (url) {
                            const cleanUrl = String(url || '').split('?')[0].toLowerCase();

                            if (/\.(jpg|jpeg)$/i.test(cleanUrl)) {
                                return 'IMAGE';
                            }

                            if (/\.(mp4|mov)$/i.test(cleanUrl)) {
                                return 'VIDEO';
                            }

                            return null;
                        };

                        const updateStoryPreview = function () {
                            const form = document.getElementById('social-story-publish-form');
                            const fileInput = form?.querySelector('input[name="story_file"]');
                            const mediaUrlInput = form?.querySelector('input[name="media_url"]');
                            const mediaTypeInput = form?.querySelector('select[name="media_type"]');
                            const previewBox = document.getElementById('story-preview-box');
                            const fitControl = document.getElementById('story-fit-control');
                            const zoomControl = document.getElementById('story-zoom-control');
                            const offsetXControl = document.getElementById('story-offset-x-control');
                            const offsetYControl = document.getElementById('story-offset-y-control');
                            const selectedFile = fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null;
                            const mediaUrl = mediaUrlInput ? mediaUrlInput.value.trim() : '';
                            const selectedMediaType = mediaTypeInput ? mediaTypeInput.value : 'IMAGE';
                            const fit = fitControl?.value || 'cover';
                            const zoom = zoomControl?.value || '1';
                            const offsetX = offsetXControl?.value || '0';
                            const offsetY = offsetYControl?.value || '0';

                            const fitInput = form?.querySelector('input[name="story_fit"]');
                            const zoomInput = form?.querySelector('input[name="story_zoom"]');
                            const offsetXInput = form?.querySelector('input[name="story_offset_x"]');
                            const offsetYInput = form?.querySelector('input[name="story_offset_y"]');

                            if (fitInput) fitInput.value = fit;
                            if (zoomInput) zoomInput.value = zoom;
                            if (offsetXInput) offsetXInput.value = offsetX;
                            if (offsetYInput) offsetYInput.value = offsetY;
                            previewBox?.classList.toggle('is-contain', fit === 'contain');
                            previewBox?.style.setProperty('--story-zoom', zoom);
                            previewBox?.style.setProperty('--story-offset-x', `${offsetX}%`);
                            previewBox?.style.setProperty('--story-offset-y', `${offsetY}%`);

                            if (storyPreviewObjectUrl) {
                                URL.revokeObjectURL(storyPreviewObjectUrl);
                                storyPreviewObjectUrl = null;
                            }

                            if (selectedFile) {
                                storyPreviewObjectUrl = URL.createObjectURL(selectedFile);

                                if (String(selectedFile.type || '').startsWith('video/')) {
                                    showStoryVideo(storyPreviewObjectUrl);
                                } else {
                                    showStoryImage(storyPreviewObjectUrl);
                                }

                                return;
                            }

                            if (mediaUrl !== '') {
                                const urlKind = detectStoryUrlKind(mediaUrl) || selectedMediaType;

                                if (urlKind === 'VIDEO') {
                                    showStoryVideo(mediaUrl);
                                } else {
                                    showStoryImage(mediaUrl);
                                }

                                return;
                            }

                            resetStoryPreview();
                        };

                        const bindStoryPublisher = function () {
                            const form = document.getElementById('social-story-publish-form');

                            if (!form || form.dataset.bound === '1') {
                                return;
                            }

                            form.dataset.bound = '1';
                            const fileInput = form.querySelector('input[name="story_file"]');
                            const mediaUrlInput = form.querySelector('input[name="media_url"]');
                            const mediaTypeInput = form.querySelector('select[name="media_type"]');
                            const fitControl = document.getElementById('story-fit-control');
                            const zoomControl = document.getElementById('story-zoom-control');
                            const offsetXControl = document.getElementById('story-offset-x-control');
                            const offsetYControl = document.getElementById('story-offset-y-control');
                            const submitButton = form.querySelector('button[type="submit"]');

                            fileInput?.addEventListener('change', function () {
                                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

                                if (file && String(file.type || '').startsWith('image/')) {
                                    mediaTypeInput.value = 'IMAGE';
                                }

                                if (file && String(file.type || '').startsWith('video/')) {
                                    mediaTypeInput.value = 'VIDEO';
                                }

                                updateStoryPreview();
                            });

                            mediaUrlInput?.addEventListener('input', updateStoryPreview);
                            mediaTypeInput?.addEventListener('change', updateStoryPreview);
                            fitControl?.addEventListener('change', updateStoryPreview);
                            zoomControl?.addEventListener('input', updateStoryPreview);
                            offsetXControl?.addEventListener('input', updateStoryPreview);
                            offsetYControl?.addEventListener('input', updateStoryPreview);
                            updateStoryPreview();

                            form.addEventListener('submit', function (event) {
                                event.preventDefault();
                                clearStoryProgressTimers();

                                const selectedFile = fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null;
                                const confirmed = form.querySelector('input[name="story_confirmed"]')?.checked;

                                if (!confirmed) {
                                    setStoryMessage('Please confirm the Story preview before publishing.', true);
                                    return;
                                }

                                if (selectedFile && mediaTypeInput?.value === 'IMAGE' && selectedFile.size > 8 * 1024 * 1024) {
                                    setStoryMessage('Image Stories must be 8 MB or smaller.', true);
                                    return;
                                }

                                if (selectedFile && mediaTypeInput?.value === 'VIDEO' && selectedFile.size > 100 * 1024 * 1024) {
                                    setStoryMessage('Video Stories must be 100 MB or smaller.', true);
                                    return;
                                }

                                if (form.dataset.storyVideoInvalid === '1') {
                                    setStoryMessage('Video Stories must be between 3 and 60 seconds.', true);
                                    return;
                                }

                                const xhr = new XMLHttpRequest();
                                const formData = new FormData(form);

                                if (submitButton) {
                                    submitButton.disabled = true;
                                }

                                setStoryMessage('Uploading Story media...');
                                setStoryProgress(5, 'Preparing upload');

                                xhr.upload.addEventListener('progress', function (progressEvent) {
                                    if (!progressEvent.lengthComputable) {
                                        setStoryProgress(35, 'Uploading media');
                                        return;
                                    }

                                    const uploadPercent = Math.round((progressEvent.loaded / progressEvent.total) * 60) + 5;
                                    setStoryProgress(uploadPercent, `Uploading media ${Math.round((progressEvent.loaded / progressEvent.total) * 100)}%`);
                                });

                                xhr.addEventListener('load', function () {
                                    let data = {};

                                    try {
                                        data = JSON.parse(xhr.responseText || '{}');
                                    } catch (error) {
                                        data = {};
                                    }

                                    if (xhr.status >= 200 && xhr.status < 300 && data.ok !== false) {
                                        clearStoryProgressTimers();
                                        setStoryProgress(100, 'Story published');
                                        setStoryMessage(data.message || 'Story published successfully.');
                                        form.reset();
                                        resetStoryPreview();
                                        scheduleSocialRefresh({ delay: 500 });
                                        return;
                                    }

                                    clearStoryProgressTimers();
                                    setStoryProgress(100, 'Publish failed');
                                    setStoryMessage(data.message || 'Story publish failed.', true);
                                });

                                xhr.addEventListener('error', function () {
                                    clearStoryProgressTimers();
                                    setStoryProgress(100, 'Publish failed');
                                    setStoryMessage('Story publish failed. Please try again.', true);
                                });

                                xhr.addEventListener('loadend', function () {
                                    if (submitButton) {
                                        submitButton.disabled = false;
                                    }
                                });

                                xhr.open('POST', form.getAttribute('action'));
                                xhr.setRequestHeader('Accept', 'application/json');
                                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                                xhr.send(formData);
                                storyProgressTimers.push(setTimeout(function () {
                                    setStoryProgress(68, 'Converting media for Instagram');
                                }, 800));
                                storyProgressTimers.push(setTimeout(function () {
                                    setStoryProgress(78, 'Creating Instagram Story container');
                                }, 2500));
                                storyProgressTimers.push(setTimeout(function () {
                                    setStoryProgress(88, 'Waiting for Instagram video processing');
                                }, 7000));

                                let waitingPercent = 88;
                                storyProcessingInterval = setInterval(function () {
                                    waitingPercent = Math.min(waitingPercent + 1, 96);
                                    setStoryProgress(waitingPercent, 'Still waiting for Instagram to finish publishing');
                                }, 6000);
                            });
                        };

                        const bindStoryViewerAndDeletes = function () {
                            const viewer = document.getElementById('social-story-viewer');
                            const mediaBox = document.getElementById('social-story-viewer-media');
                            const title = document.getElementById('social-story-viewer-title');
                            const meta = document.getElementById('social-story-viewer-meta');
                            const engagement = document.getElementById('social-story-viewer-engagement');
                            const likers = document.getElementById('social-story-viewer-likers');
                            const confirmModal = document.getElementById('social-story-confirm-modal');
                            const confirmTitle = document.getElementById('social-story-confirm-title');
                            const confirmBody = document.getElementById('social-story-confirm-body');
                            const confirmCancel = document.getElementById('social-story-confirm-cancel');
                            const confirmSubmit = document.getElementById('social-story-confirm-submit');
                            const confirmDelayMs = 900;
                            let confirmOpenedAt = 0;

                            const closeDeleteModal = function () {
                                if (!confirmModal) {
                                    return;
                                }

                                confirmModal.classList.remove('is-open');
                                confirmModal.setAttribute('aria-hidden', 'true');
                                confirmModal.pendingDeleteForm = null;
                                confirmOpenedAt = 0;
                            };

                            const openDeleteModal = function (form) {
                                if (!confirmModal || !confirmTitle || !confirmBody || !confirmSubmit) {
                                    return;
                                }

                                confirmModal.pendingDeleteForm = form;
                                confirmOpenedAt = Date.now();
                                confirmTitle.textContent = form.getAttribute('data-confirm-title') || 'Remove Story';
                                confirmBody.textContent = form.getAttribute('data-confirm-message') || 'Remove this Story from the Leadochat list?';
                                confirmSubmit.textContent = form.getAttribute('data-confirm-submit') || 'Remove';
                                confirmSubmit.disabled = true;
                                confirmModal.classList.add('is-open');
                                confirmModal.setAttribute('aria-hidden', 'false');

                                setTimeout(function () {
                                    if (confirmModal.pendingDeleteForm === form && confirmModal.classList.contains('is-open')) {
                                        confirmSubmit.disabled = false;
                                    }
                                }, confirmDelayMs);

                                requestAnimationFrame(function () {
                                    confirmCancel?.focus({ preventScroll: true });
                                });
                            };

                            const submitDeleteForm = async function (form) {
                                if (!form) {
                                    closeDeleteModal();
                                    return;
                                }

                                const formData = new FormData(form);

                                if (confirmSubmit) {
                                    confirmSubmit.disabled = true;
                                }

                                try {
                                    const response = await fetch(form.getAttribute('action'), {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        body: formData,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json',
                                        },
                                    });

                                    let data = {};

                                    try {
                                        data = await response.json();
                                    } catch (error) {
                                        data = {};
                                    }

                                    if (!response.ok || data.ok === false) {
                                        setStoryMessage(data.message || 'Remove Story failed.', true);
                                        if (confirmSubmit) {
                                            confirmSubmit.disabled = false;
                                        }
                                        return;
                                    }

                                    setStoryMessage(data.message || 'Story removed from the Leadochat list.');
                                    closeDeleteModal();
                                    scheduleSocialRefresh({ delay: 250 });
                                } catch (error) {
                                    setStoryMessage('Remove Story failed. Please try again.', true);
                                    if (confirmSubmit) {
                                        confirmSubmit.disabled = false;
                                    }
                                }
                            };

                            document.querySelectorAll('[data-story-view-url]').forEach(function (button) {
                                if (button.dataset.bound === '1') {
                                    return;
                                }

                                button.dataset.bound = '1';
                                button.addEventListener('click', function () {
                                    const url = button.getAttribute('data-story-view-url') || '';
                                    const type = button.getAttribute('data-story-view-type') || 'IMAGE';

                                    if (!viewer || !mediaBox || !url) {
                                        return;
                                    }

                                    mediaBox.innerHTML = type === 'VIDEO'
                                        ? `<video src="${url}" controls autoplay playsinline></video>`
                                        : `<img src="${url}" alt="Instagram Story">`;

                                    if (title) title.textContent = button.getAttribute('data-story-view-title') || 'Instagram Story';
                                    if (meta) meta.textContent = button.getAttribute('data-story-view-meta') || '';
                                    if (engagement) {
                                        engagement.textContent = `♥ ${button.getAttribute('data-story-view-likes') || '0'} captured likes · ↩ ${button.getAttribute('data-story-view-replies') || '0'} replies`;
                                    }
                                    if (likers) {
                                        const names = button.getAttribute('data-story-view-likers') || '';
                                        likers.textContent = names ? `Liked by ${names}` : 'No captured Story likes yet';
                                    }

                                    viewer.classList.add('is-open');
                                    viewer.setAttribute('aria-hidden', 'false');
                                });
                            });

                            document.querySelectorAll('[data-story-viewer-close]').forEach(function (closer) {
                                if (closer.dataset.bound === '1') {
                                    return;
                                }

                                closer.dataset.bound = '1';
                                closer.addEventListener('click', function () {
                                    if (!viewer || !mediaBox) {
                                        return;
                                    }

                                    viewer.classList.remove('is-open');
                                    viewer.setAttribute('aria-hidden', 'true');
                                    mediaBox.innerHTML = '';
                                });
                            });

                            document.querySelectorAll('[data-story-delete-form]').forEach(function (form) {
                                if (form.dataset.bound === '1') {
                                    return;
                                }

                                form.dataset.bound = '1';
                                form.addEventListener('submit', function (event) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    openDeleteModal(form);
                                });
                            });

                            document.querySelectorAll('[data-story-confirm-close]').forEach(function (closer) {
                                if (closer.dataset.bound === '1') {
                                    return;
                                }

                                closer.dataset.bound = '1';
                                closer.addEventListener('click', closeDeleteModal);
                            });

                            if (confirmCancel && confirmCancel.dataset.bound !== '1') {
                                confirmCancel.dataset.bound = '1';
                                confirmCancel.addEventListener('click', function (event) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    closeDeleteModal();
                                });
                            }

                            if (confirmSubmit && confirmSubmit.dataset.bound !== '1') {
                                confirmSubmit.dataset.bound = '1';
                                confirmSubmit.addEventListener('click', function (event) {
                                    event.preventDefault();
                                    event.stopPropagation();

                                    if (Date.now() - confirmOpenedAt < confirmDelayMs) {
                                        return;
                                    }

                                    submitDeleteForm(confirmModal?.pendingDeleteForm || null);
                                });
                            }
                        };

                        bindStoryPublisher();
                        bindPostPublisher();
                        bindStoryViewerAndDeletes();

                        if (!workspaceId || !window.Echo) {
                            return;
                        }

                        window.Echo.private(`workspace.${workspaceId}`)
                            .listen('.workspace.updated', function (event) {
                                if (!event || event.domain !== 'social') {
                                    return;
                                }

                                if (event.payload) {
                                    updatePostCounters([{
                                        id: event.payload.social_post_id,
                                        like_count: event.payload.like_count,
                                        comments_count: event.payload.comments_count,
                                    }]);
                                }

                                scheduleSocialRefresh({ delay: 350 });
                            });
                    })();
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
