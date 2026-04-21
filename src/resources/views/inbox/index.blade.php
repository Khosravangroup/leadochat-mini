<x-app-layout>
    @php
        $selectedConversation = $selectedConversation ?? null;
        $selectedCustomer = $selectedConversation?->participants?->firstWhere('is_self', false);
        $messages = $selectedConversation?->messages?->sortBy('created_at') ?? collect();
        $attachmentCount = $messages->sum(fn($m) => $m->attachments->count());
        $replyTarget = null;

        if ($selectedConversation && request()->filled('reply')) {
            $replyTarget = $messages->firstWhere('id', (int) request('reply'));
        }
    @endphp

    <style>
        :root {
            --lc-bg: #f8f8f6;
            --lc-panel: #ffffff;
            --lc-panel-soft: #f5f5f1;
            --lc-border: #edede8;
            --lc-border-strong: #e2e1db;
            --lc-text: #37352f;
            --lc-text-soft: #6d6d6a;
            --lc-text-muted: #9f9e99;
            --lc-primary: #724cda;
            --lc-primary-soft: #f2eefc;
            --lc-outgoing: #eceefc;
            --lc-outgoing-border: #d5d9f0;
            --lc-note: #fff4cf;
            --lc-success: #1bc98e;
            --lc-danger: #e64759;
        }

        .lc-page {
            background: var(--lc-bg);
            height: calc(100vh - 65px);
            min-height: calc(100vh - 65px);
            padding: 0;
            overflow: hidden;
        }

        .lc-shell {
            display: flex;
            height: 100%;
            min-height: 100%;
            background: var(--lc-bg);
            color: var(--lc-text);
            overflow: hidden;
        }

        .lc-sidebar {
            width: 340px;
            min-width: 340px;
            background: var(--lc-panel);
            border-right: 1px solid var(--lc-border);
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            overflow: hidden;
        }

        .lc-sidebar-header {
            padding: 1rem 1.25rem .5rem;
        }

        .lc-sidebar-title-row {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .lc-sidebar-title {
            font-size: 1.5rem;
            line-height: 1.2;
            font-weight: 600;
            color: var(--lc-text);
        }

        .lc-qty {
            background: var(--lc-border-strong);
            border-radius: 999px;
            padding: 1px 8px;
            font-size: .75rem;
            color: var(--lc-text-soft);
            font-weight: 600;
        }

        .lc-search-wrap {
            padding: 0 1.25rem 1rem;
        }

        .lc-search {
            width: 100%;
            height: 40px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel-soft);
            border-radius: 12px;
            padding: 0 .9rem;
            font-size: .875rem;
            outline: none;
            color: var(--lc-text);
        }

        .lc-search::placeholder {
            color: var(--lc-text-muted);
        }

        .lc-conversation-list {
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            border-top: 1px solid var(--lc-border);
        }

        .lc-conversation-item {
            display: block;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid var(--lc-border);
            background: var(--lc-panel);
        }

        .lc-conversation-item.is-active {
            background: var(--lc-primary-soft);
        }

        .lc-conversation-item-inner {
            display: flex;
            gap: .75rem;
            padding: 1rem 1.25rem;
        }

        .lc-avatar-wrap {
            position: relative;
            width: 42px;
            height: 42px;
            min-width: 42px;
            flex: 0 0 42px;
        }

        .lc-avatar {
            width: 100%;
            height: 100%;
            min-width: 0;
            border-radius: 999px;
            object-fit: cover;
            background: #ddd;
        }

        .lc-channel-badge {
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid var(--lc-panel);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, .18);
        }

        .lc-channel-badge.instagram {
            background: linear-gradient(135deg, #f58529 0%, #dd2a7b 55%, #515bd4 100%);
        }

        .lc-channel-badge svg {
            width: 10px;
            height: 10px;
            display: block;
        }

        .lc-conversation-meta {
            min-width: 0;
            flex: 1;
        }

        .lc-conversation-top {
            display: grid;
            grid-template-columns: minmax(0,1fr) auto;
            gap: .5rem;
            align-items: start;
        }

        .lc-conversation-name {
            font-size: .95rem;
            font-weight: 600;
            color: var(--lc-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lc-conversation-time {
            font-size: .75rem;
            color: var(--lc-text-soft);
            white-space: nowrap;
        }

        .lc-conversation-preview {
            margin-top: .3rem;
            font-size: .875rem;
            line-height: 1.35rem;
            color: var(--lc-text-soft);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .lc-conversation-bottom {
            margin-top: .45rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .lc-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 22px;
            border-radius: 999px;
            padding: 0 .5rem;
            font-size: .68rem;
            font-weight: 700;
        }

        .lc-badge.provider {
            background: var(--lc-border-strong);
            color: var(--lc-text-soft);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .lc-badge.unread {
            background: var(--lc-primary);
            color: #fff;
        }

        .lc-badge.story-reply {
            background: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #d8b4fe;
            text-transform: none;
            letter-spacing: 0;
        }

        .lc-badge.comment-reply {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            text-transform: none;
            letter-spacing: 0;
        }

        .lc-story-reply-box {
            margin-bottom: .45rem;
            border: 1px solid #e9d5ff;
            background: #faf5ff;
            border-radius: 8px;
            padding: .55rem .65rem;
            display: grid;
            gap: .28rem;
        }

        .lc-comment-reply-box {
            margin-bottom: .45rem;
            border: 1px solid #bae6fd;
            background: #f0f9ff;
            border-radius: 8px;
            padding: .55rem .65rem;
            display: grid;
            gap: .45rem;
        }

        .lc-story-reply-title {
            font-size: .72rem;
            font-weight: 700;
            color: #7c3aed;
            line-height: 1.2;
        }

        .lc-comment-reply-title {
            font-size: .72rem;
            font-weight: 700;
            color: #0369a1;
            line-height: 1.2;
        }

        .lc-comment-reply-cover {
            overflow: hidden;
            border-radius: 8px;
            background: #dbeafe;
            border: 1px solid #bae6fd;
        }

        .lc-comment-reply-cover img {
            display: block;
            width: 100%;
            max-height: 180px;
            object-fit: cover;
        }

        .lc-story-reply-meta {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .lc-story-reply-pill {
            display: inline-flex;
            align-items: center;
            min-height: 22px;
            border-radius: 999px;
            padding: 0 .5rem;
            font-size: .68rem;
            font-weight: 700;
            background: #ffffff;
            color: #6d28d9;
            border: 1px solid #d8b4fe;
        }

        .lc-comment-reply-pill {
            display: inline-flex;
            align-items: center;
            min-height: 22px;
            border-radius: 999px;
            padding: 0 .5rem;
            font-size: .68rem;
            font-weight: 700;
            background: #ffffff;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .lc-story-reply-text {
            font-size: .76rem;
            line-height: 1.35;
            color: #6d6d6a;
            word-break: break-word;
        }

        .lc-comment-reply-text {
            font-size: .76rem;
            line-height: 1.35;
            color: #075985;
            word-break: break-word;
        }

        .lc-comment-reply-label {
            display: block;
            margin-bottom: .18rem;
            font-size: .66rem;
            font-weight: 800;
            color: #0c4a6e;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .lc-comment-private-reply-card {
            margin-bottom: .45rem;
            border: 1px solid #bae6fd;
            background: linear-gradient(180deg, #f8fdff 0%, #eef8ff 100%);
            border-radius: 12px;
            padding: .7rem;
            display: grid;
            gap: .65rem;
        }

        .lc-comment-private-reply-top {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .lc-comment-private-reply-author {
            font-size: .72rem;
            font-weight: 700;
            color: #0f172a;
        }

        .lc-comment-private-reply-layout {
            display: grid;
            grid-template-columns: 70px minmax(0, 1fr);
            gap: .7rem;
            align-items: start;
        }

        .lc-comment-private-reply-cover {
            width: 70px;
            height: 70px;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid #bae6fd;
            background: #dbeafe;
        }

        .lc-comment-private-reply-cover img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .lc-comment-private-reply-content {
            min-width: 0;
            display: grid;
            gap: .42rem;
        }

        .lc-comment-private-reply-section {
            min-width: 0;
        }

        .lc-comment-private-reply-kicker {
            display: block;
            margin-bottom: .16rem;
            font-size: .64rem;
            font-weight: 800;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .lc-comment-private-reply-copy {
            font-size: .77rem;
            line-height: 1.45;
            color: #0f172a;
            word-break: break-word;
        }

        .lc-comment-private-reply-comment .lc-comment-private-reply-copy {
            color: #075985;
        }

        .lc-comment-private-reply-response {
            border-top: 1px solid #bae6fd;
            padding-top: .5rem;
        }

        .lc-comment-private-reply-link {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #7dd3fc;
            background: #ffffff;
            padding: 0 .7rem;
            font-size: .72rem;
            font-weight: 700;
            color: #0369a1;
            text-decoration: none;
        }

        .lc-main {
            flex: 1;
            min-width: 0;
            display: flex;
            min-height: 0;
            height: 100%;
            overflow: hidden;
        }

        .lc-center {
            flex: 1 1 auto;
            min-width: 350px;
            min-height: 0;
            background: var(--lc-panel);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--lc-border);
            overflow: hidden;
        }

        .lc-center-header {
            height: 80px;
            border-bottom: 1px solid var(--lc-border);
            background: var(--lc-panel);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem 1rem .75rem 1.25rem;
            flex: 0 0 auto;
        }

        .lc-center-header-left {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: .875rem;
        }

        .lc-center-header-text {
            min-width: 0;
        }

        .lc-chat-title {
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.2;
            color: var(--lc-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lc-chat-subtitle {
            margin-top: .2rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            font-size: .875rem;
            color: var(--lc-text-soft);
        }

        .lc-center-actions {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .lc-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel);
            color: var(--lc-text-soft);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            cursor: pointer;
            transition: background .18s ease, color .18s ease, border-color .18s ease, transform .18s ease;
        }

        .lc-icon-btn:hover,
        .lc-icon-btn:focus-visible {
            background: #f8fafc;
            color: #0f172a;
            border-color: #d6d3d1;
            transform: translateY(-1px);
            outline: none;
        }

        .lc-icon-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 1.85;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .lc-icon-btn.archive {
            color: #1d4ed8;
        }

        .lc-icon-btn.restore {
            color: #0f766e;
        }

        .lc-icon-btn.trash {
            color: #dc2626;
        }

        .lc-icon-btn.refresh {
            color: #4f46e5;
        }

        .lc-message-area {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--lc-bg);
            padding: 1rem 1rem .75rem;
            display: block;
            scroll-behavior: auto;
            visibility: hidden;
        }

        .lc-message-area.is-ready {
            visibility: visible;
        }

        .lc-policy {
            margin-bottom: .75rem;
            border-radius: 10px;
            padding: .75rem .875rem;
            background: #fff8e1;
            color: #8a5a00;
            font-size: .875rem;
            line-height: 1.45;
            border: 1px solid #ffe6a0;
        }

        .lc-message-stack {
            display: flex;
            flex-direction: column;
            gap: .9rem;
            margin-top: 0;
            padding-bottom: .25rem;
        }

        .lc-message-row {
            display: flex;
        }

        .lc-message-row.inbound {
            justify-content: flex-start;
        }

        .lc-message-row.outbound {
            justify-content: flex-end;
        }

        .lc-message-wrap {
            display: flex;
            align-items: flex-end;
            gap: .65rem;
            max-width: 90%;
        }

        .lc-message-row.outbound .lc-message-wrap {
            flex-direction: row-reverse;
        }

        .lc-message-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 999px;
            object-fit: cover;
        }

        .lc-message-body {
            min-width: 0;
            max-width: 100%;
            position: relative;
        }

        .lc-message-name {
            margin: 0 0 .35rem;
            font-size: .78rem;
            font-weight: 600;
            color: var(--lc-text-soft);
        }

        .lc-bubble {
            max-width: 100%;
            border-radius: 14px;
            padding: .8rem .9rem;
            font-size: .95rem;
            line-height: 1.55;
            word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,.03);
        }

        .lc-bubble.inbound {
            background: var(--lc-panel);
            border: 1px solid var(--lc-border);
            color: var(--lc-text);
        }

        .lc-bubble.outbound {
            background: var(--lc-outgoing);
            border: 1px solid var(--lc-outgoing-border);
            color: var(--lc-text);
        }

        .lc-reply {
            margin-bottom: .65rem;
            border-left: 3px solid var(--lc-primary);
            background: rgba(114, 76, 218, .06);
            border-radius: 10px;
            padding: .5rem .65rem;
            font-size: .8rem;
            color: var(--lc-text-soft);
        }

        .lc-image-card {
            overflow: hidden;
            border-radius: 12px;
            margin-bottom: .55rem;
            background: var(--lc-panel-soft);
        }

        .lc-image-card img {
            display: block;
            width: 100%;
            max-height: 360px;
            object-fit: cover;
        }

        .lc-voice-card {
            width: 320px;
            max-width: 100%;
            border-radius: 12px;
            background: var(--lc-panel-soft);
            padding: .75rem;
        }

        .lc-voice-title {
            font-size: .86rem;
            font-weight: 600;
            color: var(--lc-text);
        }

        .lc-voice-sub {
            margin-top: .2rem;
            font-size: .76rem;
            color: var(--lc-text-soft);
        }

        .lc-voice-card audio {
            width: 100%;
            height: 36px;
            margin-top: .55rem;
        }

        .lc-file-card {
            display: flex;
            align-items: center;
            gap: .75rem;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel-soft);
            border-radius: 12px;
            padding: .75rem;
            margin-bottom: .55rem;
        }

        .lc-file-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 10px;
            background: var(--lc-panel);
            border: 1px solid var(--lc-border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .lc-file-meta {
            min-width: 0;
            flex: 1;
        }

        .lc-file-name {
            font-size: .86rem;
            font-weight: 600;
            color: var(--lc-text);
            word-break: break-word;
        }

        .lc-file-sub {
            margin-top: .2rem;
            font-size: .75rem;
            color: var(--lc-text-soft);
        }

        .lc-attach-inline {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            cursor: pointer;
        }

        .lc-hidden-file-input {
            display: none;
        }

        .lc-recording-wrap {
            display: none;
            margin-bottom: 10px;
        }

        .lc-recording-wrap.is-visible {
            display: block;
        }

        .lc-recording-box {
            border: 1px solid var(--lc-border);
            background: var(--lc-panel-soft);
            border-radius: 12px;
            padding: .85rem .9rem;
        }

        .lc-recording-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .lc-recording-left {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .lc-recording-dot {
            width: 10px;
            height: 10px;
            min-width: 10px;
            border-radius: 999px;
            background: #ef4444;
            animation: lcPulse 1s infinite;
        }

        @keyframes lcPulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: .45; transform: scale(1.15); }
            100% { opacity: 1; transform: scale(1); }
        }

        .lc-recording-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--lc-text);
        }

        .lc-recording-sub {
            margin-top: .18rem;
            font-size: .76rem;
            color: var(--lc-text-soft);
        }

        .lc-recording-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .lc-rec-btn {
            min-height: 34px;
            border-radius: 10px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel);
            padding: 0 .9rem;
            font-size: .8rem;
            font-weight: 700;
            color: var(--lc-text);
        }

        .lc-rec-btn.cancel {
            color: var(--lc-danger);
        }

        .lc-rec-btn.primary {
            background: var(--lc-primary);
            color: #fff;
            border-color: var(--lc-primary);
        }

        .lc-rec-btn[disabled] {
            opacity: .5;
            cursor: not-allowed;
        }

        .lc-recording-preview {
            margin-top: .75rem;
            display: none;
        }

        .lc-recording-preview.is-visible {
            display: block;
        }

        .lc-recording-preview audio {
            width: 100%;
            height: 40px;
        }

        .lc-recording-speed {
            margin-top: .55rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            font-size: .78rem;
            color: var(--lc-text-soft);
        }

        .lc-recording-speed button {
            min-height: 30px;
            border-radius: 999px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel);
            padding: 0 .7rem;
            font-size: .75rem;
            font-weight: 700;
            color: var(--lc-text);
        }

        .lc-recording-speed button.is-active {
            background: var(--lc-primary);
            color: #fff;
            border-color: var(--lc-primary);
        }

        .lc-emoji-picker {
            display: none;
            margin-bottom: 8px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,.04);
        }

        .lc-emoji-picker.is-visible {
            display: block;
        }

        .lc-emoji-search-wrap {
            padding: .5rem;
            border-bottom: 1px solid var(--lc-border);
            background: var(--lc-panel-soft);
        }

        .lc-emoji-search {
            width: 100%;
            height: 34px;
            border: 1px solid var(--lc-border);
            border-radius: 9px;
            padding: 0 10px;
            font-size: .78rem;
            outline: none;
            background: #fff;
            color: var(--lc-text);
        }

        .lc-emoji-tabs {
            display: flex;
            gap: .3rem;
            flex-wrap: wrap;
            padding: .45rem .5rem;
            border-bottom: 1px solid var(--lc-border);
            background: #fff;
        }

        .lc-emoji-tab {
            min-height: 26px;
            border-radius: 999px;
            border: 1px solid var(--lc-border);
            background: #fff;
            padding: 0 .55rem;
            font-size: .68rem;
            font-weight: 700;
            color: var(--lc-text-soft);
        }

        .lc-emoji-tab.is-active {
            background: var(--lc-primary);
            color: #fff;
            border-color: var(--lc-primary);
        }

        .lc-emoji-body {
            padding: .35rem;
            max-height: 170px;
            overflow-y: auto;
            background: #fff;
        }

        .lc-emoji-grid {
            display: grid;
            grid-template-columns: repeat(10, 28px);
            gap: 2px;
            justify-content: start;
            align-items: center;
        }

        .lc-emoji-btn {
            width: 28px;
            height: 28px;
            border: 0;
            background: transparent;
            border-radius: 6px;
            font-size: 1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .lc-emoji-btn:hover {
            background: #f1f1ec;
        }

        .lc-emoji-footer {
            padding: .35rem .45rem;
            border-top: 1px solid var(--lc-border);
            background: var(--lc-panel-soft);
            display: flex;
            align-items: center;
            gap: .25rem;
            flex-wrap: wrap;
        }

        .lc-skin-tone-btn {
            width: 24px;
            height: 24px;
            min-height: 24px;
            border-radius: 999px;
            border: 1px solid var(--lc-border);
            background: #fff;
            padding: 0;
            font-size: .72rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .lc-skin-tone-btn.is-active {
            background: var(--lc-primary);
            color: #fff;
            border-color: var(--lc-primary);
        }

        .lc-composer-preview-wrap {
            margin-bottom: 10px;
            display: none;
        }

        .lc-composer-preview-wrap.is-visible {
            display: block;
        }

        .lc-composer-preview-list {
            display: grid;
            gap: .65rem;
        }

        .lc-composer-preview {
            border: 1px solid var(--lc-border);
            background: var(--lc-panel-soft);
            border-radius: 12px;
            padding: .75rem;
        }

        .lc-composer-preview-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .lc-composer-preview-main {
            min-width: 0;
            flex: 1;
            display: flex;
            gap: .75rem;
            align-items: center;
        }

        .lc-composer-preview-thumb {
            width: 56px;
            height: 56px;
            min-width: 56px;
            border-radius: 10px;
            overflow: hidden;
            background: var(--lc-panel);
            border: 1px solid var(--lc-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .lc-composer-preview-thumb img,
        .lc-composer-preview-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .lc-composer-preview-meta {
            min-width: 0;
            flex: 1;
        }

        .lc-composer-preview-name {
            font-size: .88rem;
            font-weight: 600;
            color: var(--lc-text);
            word-break: break-word;
        }

        .lc-composer-preview-sub {
            margin-top: .2rem;
            font-size: .76rem;
            color: var(--lc-text-soft);
        }

        .lc-composer-preview-remove {
            border: 0;
            background: transparent;
            color: var(--lc-danger);
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            white-space: nowrap;
        }

        .lc-composer-preview-note {
            margin-top: .5rem;
            font-size: .75rem;
            color: var(--lc-text-muted);
        }

        .lc-composer-preview-summary {
            margin-bottom: .55rem;
            font-size: .78rem;
            color: var(--lc-text-soft);
            font-weight: 600;
        }

        .lc-message-meta {
            margin-top: .35rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            font-size: .74rem;
            color: var(--lc-text-muted);
        }

        .lc-message-row.outbound .lc-message-meta {
            justify-content: flex-end;
        }

        .lc-reaction-strip {
            display: flex;
            gap: .18rem;
            flex-wrap: wrap;
            margin-top: -.35rem;
            margin-left: .6rem;
            min-height: 18px;
            position: relative;
            z-index: 2;
        }

        .lc-message-row.outbound .lc-reaction-strip {
            justify-content: flex-end;
            margin-left: 0;
            margin-right: .6rem;
        }

        .lc-reaction-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 22px;
            border: 1px solid var(--lc-border);
            border-radius: 999px;
            background: var(--lc-panel);
            padding: 0 6px;
            font-size: .78rem;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .1);
        }

        .lc-reaction-pill.agent {
            background: #fff;
            border-color: rgba(114, 76, 218, .18);
        }

        .lc-reaction-form {
            display: inline-flex;
            position: relative;
            margin: 0;
        }

        .lc-reaction-trigger {
            border: 0;
            background: transparent;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--lc-text-muted);
            font-size: .9rem;
            cursor: pointer;
            transition: background .12s ease, color .12s ease, transform .12s ease;
        }

        .lc-reaction-trigger:hover,
        .lc-reaction-trigger.is-active,
        .lc-reaction-form.is-open .lc-reaction-trigger {
            background: #fff;
            color: #d12f69;
            box-shadow: 0 1px 4px rgba(15, 23, 42, .12);
        }

        .lc-reaction-picker {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 8px);
            transform: translateX(-50%) translateY(4px) scale(.96);
            transform-origin: bottom center;
            display: flex;
            align-items: center;
            gap: .35rem;
            min-height: 42px;
            padding: 5px 7px;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 999px;
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 10px 28px rgba(15, 23, 42, .18);
            opacity: 0;
            pointer-events: none;
            z-index: 20;
            transition: opacity .12s ease, transform .12s ease;
        }

        .lc-reaction-form.is-open .lc-reaction-picker {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0) scale(1);
        }

        .lc-reaction-option {
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            line-height: 1;
            cursor: pointer;
            transition: transform .12s ease, background .12s ease;
        }

        .lc-reaction-option:hover,
        .lc-reaction-option:focus-visible {
            background: #f4f4f5;
            transform: translateY(-3px) scale(1.12);
            outline: none;
        }

        .lc-reaction-error {
            display: none;
            color: var(--lc-danger);
            font-size: .72rem;
            font-weight: 600;
        }

        .lc-reaction-form.has-error + .lc-reaction-error {
            display: inline;
        }

        .lc-status-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 14px;
            font-size: .78rem;
            line-height: 1;
            font-weight: 700;
            letter-spacing: -0.08em;
        }

        .lc-status-icon.sent,
        .lc-status-icon.delivered {
            color: var(--lc-text-muted);
        }

        .lc-status-icon.read {
            color: var(--lc-primary);
        }

        .lc-status-icon.failed {
            color: var(--lc-danger);
            letter-spacing: 0;
        }

        .lc-composer-wrap {
            flex: 0 0 auto;
            padding: .6rem 1rem .8rem;
            background: var(--lc-panel);
            border-top: 1px solid var(--lc-border);
        }

        .lc-composer {
            border-top: 1px solid var(--lc-border);
            padding-top: .45rem;
        }

        .lc-message-types {
            display: flex;
            align-items: center;
            gap: .32rem;
            margin-bottom: .4rem;
            flex-wrap: wrap;
            color: var(--lc-text-soft);
            font-size: .74rem;
        }

        .lc-message-type-pill {
            display: inline-flex;
            align-items: center;
            height: 21px;
            border-radius: 999px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel);
            padding: 0 .52rem;
            font-size: .67rem;
            font-weight: 600;
            color: var(--lc-text-soft);
        }

        .lc-editor-box {
            border: 1px solid var(--lc-border-strong);
            border-radius: 14px;
            background: var(--lc-panel);
            overflow: hidden;
        }

        .lc-editor-box textarea {
            width: 100%;
            min-height: 48px;
            border: 0;
            resize: none;
            outline: none;
            padding: .65rem .85rem .45rem;
            font-size: .93rem;
            color: var(--lc-text);
            background: transparent;
        }

        .lc-editor-box textarea::placeholder {
            color: var(--lc-text-muted);
        }

        .lc-editor-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border-top: 1px solid var(--lc-border);
            padding: .52rem .75rem;
            flex-wrap: wrap;
        }

        .lc-editor-icons {
            display: flex;
            align-items: center;
            gap: .85rem;
            color: var(--lc-text-soft);
            flex-wrap: wrap;
        }

        .lc-editor-icon {
            font-size: 1rem;
            line-height: 1;
            cursor: pointer;
        }

        .lc-send {
            min-width: 100px;
            height: 40px;
            border: 0;
            border-radius: 10px;
            background: var(--lc-primary);
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
        }

        .lc-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .lc-modal.is-open {
            display: flex;
        }

        .lc-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .44);
            backdrop-filter: blur(2px);
        }

        .lc-modal-card {
            position: relative;
            width: min(100%, 420px);
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .18);
            padding: 1rem;
            display: grid;
            gap: .8rem;
        }

        .lc-modal-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .lc-modal-body {
            font-size: .9rem;
            line-height: 1.55;
            color: #475569;
        }

        .lc-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: .55rem;
        }

        .lc-modal-btn {
            min-height: 38px;
            border-radius: 8px;
            border: 1px solid #dbe3ec;
            background: #fff;
            padding: 0 .9rem;
            font-size: .85rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
        }

        .lc-modal-btn.primary {
            border-color: #724cda;
            background: #724cda;
            color: #fff;
        }

        .lc-aside {
            width: 300px;
            min-width: 300px;
            background: var(--lc-bg);
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            overflow: hidden;
        }

        .lc-aside-scroll {
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding: .5rem;
        }

        .lc-card {
            background: var(--lc-panel);
            border-radius: 10px;
            padding: .65rem .75rem;
            margin-bottom: .45rem;
            border: 1px solid var(--lc-border);
        }

        .lc-card-header-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            text-align: center;
        }

        .lc-aside-avatar {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            object-fit: cover;
        }

        .lc-card-title {
            font-weight: 600;
            color: var(--lc-text);
            font-size: .88rem;
            line-height: 1.3;
        }

        .lc-card-sub {
            font-size: .72rem;
            color: var(--lc-text-soft);
            line-height: 1.25;
        }

        .lc-section-title {
            margin: 0 0 .4rem;
            font-size: .64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--lc-text-muted);
        }

        .lc-detail-grid {
            display: grid;
            gap: .28rem;
        }

        .lc-detail-row {
            display: grid;
            grid-template-columns: 86px minmax(0,1fr);
            gap: .45rem;
            font-size: .72rem;
            line-height: 1.25;
            align-items: start;
        }

        .lc-detail-key {
            color: var(--lc-text-soft);
        }

        .lc-detail-val {
            color: var(--lc-text);
            text-align: right;
            word-break: break-word;
        }

        .lc-inline-box {
            min-height: 32px;
            border: 1px dashed var(--lc-border-strong);
            border-radius: 8px;
            padding: .5rem .6rem;
            background: var(--lc-panel-soft);
            color: var(--lc-text-soft);
            font-size: .72rem;
            line-height: 1.3;
        }

        .lc-pill-box {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            border-radius: 999px;
            border: 1px solid var(--lc-border);
            background: var(--lc-panel);
            padding: 0 .6rem;
            font-size: .72rem;
            color: var(--lc-text-soft);
            font-weight: 600;
        }

        @media (max-width: 1600px) {
            .lc-sidebar {
                width: 320px;
                min-width: 320px;
            }

            .lc-aside {
                width: 280px;
                min-width: 280px;
            }

            .lc-detail-row {
                grid-template-columns: 80px minmax(0,1fr);
            }
        }

        @media (max-width: 1360px) {
            .lc-sidebar {
                width: 300px;
                min-width: 300px;
            }

            .lc-aside {
                width: 260px;
                min-width: 260px;
            }

            .lc-center-header {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .lc-message-area {
                padding-left: .9rem;
                padding-right: .9rem;
            }

            .lc-composer-wrap {
                padding-left: .9rem;
                padding-right: .9rem;
            }
        }

        @media (max-width: 1200px) {
            .lc-aside {
                display: none;
            }

            .lc-center {
                border-right: 0;
            }

            .lc-message-wrap {
                max-width: 96%;
            }
        }

        @media (max-width: 920px) {
            .lc-page {
                min-height: auto;
            }

            .lc-shell {
                flex-direction: column;
                min-height: auto;
            }

            .lc-sidebar {
                width: 100%;
                min-width: 0;
                min-height: auto;
                max-height: 340px;
                border-right: 0;
                border-bottom: 1px solid var(--lc-border);
            }

            .lc-main {
                min-height: auto;
            }

            .lc-center {
                min-width: 0;
                min-height: calc(100vh - 405px);
            }

            .lc-center-header {
                height: auto;
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .lc-center-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .lc-message-wrap {
                max-width: 100%;
            }

            .lc-message-body {
                min-width: 0;
            }
        }

        @media (max-width: 768px) {
            .lc-emoji-grid {
                grid-template-columns: repeat(8, 26px);
                gap: 2px;
            }

            .lc-emoji-btn {
                width: 26px;
                height: 26px;
                font-size: .95rem;
            }

            .lc-emoji-body {
                max-height: 150px;
            }

            .lc-emoji-tab {
                min-height: 24px;
                padding: 0 .45rem;
                font-size: .64rem;
            }
        }

        @media (max-width: 640px) {
            .lc-page,
            .lc-shell,
            .lc-main {
                min-height: auto;
            }

            .lc-sidebar-header,
            .lc-search-wrap,
            .lc-conversation-item-inner {
                padding-left: .9rem;
                padding-right: .9rem;
            }

            .lc-message-area {
                padding: .85rem .9rem .6rem;
            }

            .lc-composer-wrap {
                padding: .55rem .9rem .75rem;
            }

            .lc-sidebar-title {
                font-size: 1.2rem;
            }

            .lc-conversation-item-inner {
                gap: .65rem;
            }

            .lc-center-header {
                padding: .9rem;
            }

            .lc-center-header-left {
                width: 100%;
            }

            .lc-chat-title {
                font-size: 1rem;
            }

            .lc-chat-subtitle {
                font-size: .8rem;
            }

            .lc-message-stack {
                gap: .8rem;
            }

            .lc-message-avatar,
            .lc-avatar,
            .lc-avatar-wrap {
                width: 34px;
                height: 34px;
                min-width: 34px;
            }

            .lc-bubble {
                padding: .7rem .8rem;
                font-size: .9rem;
            }

            .lc-voice-card {
                width: 100%;
            }

            .lc-editor-box textarea {
                min-height: 52px;
                padding: .62rem .8rem .45rem;
            }

            .lc-editor-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .lc-editor-icons {
                justify-content: space-between;
            }

            .lc-send {
                width: 100%;
            }
        }
    </style>

    <div
        id="lcInboxPage"
        class="lc-page"
        data-conversation-tags-save-url="{{ $selectedConversation ? route('inbox.tags.conversation.save', $selectedConversation) : '' }}"
    >
        <div class="lc-shell">

            <aside class="lc-sidebar">
                <div class="lc-sidebar-header">
                    <div class="lc-sidebar-title-row">
                        <div class="lc-sidebar-title">Inbox</div>
                        <span class="lc-qty">{{ $conversations->count() }}</span>
                    </div>
                </div>

                <div style="padding: 0 1.25rem .75rem; display: flex; gap: .5rem; flex-wrap: wrap;">
                    <a
                        href="{{ route('inbox.index') }}"
                        style="display:inline-flex; align-items:center; justify-content:center; min-height:34px; padding:0 14px; border-radius:999px; text-decoration:none; font-size:13px; font-weight:700; {{ ($viewMode ?? 'inbox') === 'inbox' ? 'background:#724cda;color:#fff;' : 'background:#f5f5f1;color:#6d6d6a;border:1px solid #e2e1db;' }}"
                    >
                        Inbox
                    </a>

                    <a
                        href="{{ route('inbox.index', ['view' => 'archived']) }}"
                        style="display:inline-flex; align-items:center; justify-content:center; min-height:34px; padding:0 14px; border-radius:999px; text-decoration:none; font-size:13px; font-weight:700; {{ ($viewMode ?? 'inbox') === 'archived' ? 'background:#724cda;color:#fff;' : 'background:#f5f5f1;color:#6d6d6a;border:1px solid #e2e1db;' }}"
                    >
                        Archived
                    </a>

                    <a
                        href="{{ route('inbox.index', ['view' => 'trash']) }}"
                        style="display:inline-flex; align-items:center; justify-content:center; min-height:34px; padding:0 14px; border-radius:999px; text-decoration:none; font-size:13px; font-weight:700; {{ ($viewMode ?? 'inbox') === 'trash' ? 'background:#724cda;color:#fff;' : 'background:#f5f5f1;color:#6d6d6a;border:1px solid #e2e1db;' }}"
                    >
                        Trash
                    </a>
                </div>

                <div class="lc-search-wrap">
                    <input type="text" class="lc-search" placeholder="Search conversations...">
                </div>

                <div class="lc-conversation-list">
                    @forelse ($conversations as $conversation)
                        @php
                            $customer = $conversation->participants->firstWhere('is_self', false);
                            $isSelected = $selectedConversation && $selectedConversation->id === $conversation->id;
                        @endphp

                        <a href="{{ route('inbox.show', ['conversation' => $conversation->id, 'view' => ($viewMode ?? 'inbox')]) }}" class="lc-conversation-item {{ $isSelected ? 'is-active' : '' }}">
                            <div class="lc-conversation-item-inner">
                                <div class="lc-avatar-wrap">
                                    <img
                                        class="lc-avatar"
                                        src="{{ $customer?->avatar_url ?? $conversation->avatar_url ?? 'https://ui-avatars.com/api/?name=User&background=e2e8f0&color=334155' }}"
                                        alt="Avatar"
                                    >

                                    @if ($conversation->provider === 'instagram')
                                        <span class="lc-channel-badge instagram" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <rect x="4.75" y="4.75" width="14.5" height="14.5" rx="4.25"></rect>
                                                <circle cx="12" cy="12" r="3.35"></circle>
                                                <circle cx="16.6" cy="7.4" r="1.05" fill="currentColor" stroke="none"></circle>
                                            </svg>
                                        </span>
                                    @endif
                                </div>

                                <div class="lc-conversation-meta">
                                    <div class="lc-conversation-top">
                                        <div class="lc-conversation-name">
                                            {{ $conversation->title ?? $customer?->display_name ?? 'Conversation' }}
                                        </div>

                                        <div class="lc-conversation-time">
                                            {{ optional($conversation->last_message_at)->format('M d, Y') ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="lc-conversation-preview">
                                        {{ $conversation->last_message_preview ?? 'No messages yet' }}
                                    </div>

                                    <div class="lc-conversation-bottom">
                                        @if ($conversation->unread_count > 0)
                                            <span class="lc-badge unread">{{ $conversation->unread_count }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-sm text-slate-500">No conversations found.</div>
                    @endforelse
                </div>
            </aside>

            <div class="lc-main">
                <section class="lc-center">
                    @if ($selectedConversation)
                        <div class="lc-center-header">
                            <div class="lc-center-header-left">
                                <div class="lc-avatar-wrap">
                                    <img
                                        class="lc-avatar"
                                        src="{{ $selectedCustomer?->avatar_url ?? $selectedConversation->avatar_url ?? 'https://ui-avatars.com/api/?name=User&background=e2e8f0&color=334155' }}"
                                        alt="Avatar"
                                    >

                                    @if ($selectedConversation->provider === 'instagram')
                                        <span class="lc-channel-badge instagram" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <rect x="4.75" y="4.75" width="14.5" height="14.5" rx="4.25"></rect>
                                                <circle cx="12" cy="12" r="3.35"></circle>
                                                <circle cx="16.6" cy="7.4" r="1.05" fill="currentColor" stroke="none"></circle>
                                            </svg>
                                        </span>
                                    @endif
                                </div>

                                <div class="lc-center-header-text">
                                    <div class="lc-chat-title">
                                        {{ $selectedConversation->title ?? $selectedCustomer?->display_name ?? 'Conversation' }}
                                    </div>

                                    <div class="lc-chat-subtitle">
                                        <span>{{ $selectedCustomer?->handle ?? '@unknown' }}</span>
                                        <span>•</span>
                                        <span>{{ ucfirst($selectedConversation->provider) }}</span>
                                        <span>•</span>
                                        <span>{{ $selectedConversation->type }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-center-actions">
                                @if (($viewMode ?? 'inbox') === 'trash')
                                    <form
                                        class="lc-conversation-action-form"
                                        method="POST"
                                        action="{{ route('inbox.restore', $selectedConversation) }}"
                                        data-confirm-title="Restore conversation"
                                        data-confirm-message="This conversation will return to the inbox."
                                        data-confirm-submit="Restore"
                                        style="display:inline;"
                                    >
                                        @csrf
                                        <button type="submit" class="lc-icon-btn restore" title="Restore" aria-label="Restore conversation">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M9 14 4 9m0 0 5-5M4 9h8a7 7 0 1 1 0 14h-1"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @elseif (($viewMode ?? 'inbox') === 'archived')
                                    <form
                                        class="lc-conversation-action-form"
                                        method="POST"
                                        action="{{ route('inbox.unarchive', $selectedConversation) }}"
                                        data-confirm-title="Unarchive conversation"
                                        data-confirm-message="This conversation will return to the inbox."
                                        data-confirm-submit="Unarchive"
                                        style="display:inline;"
                                    >
                                        @csrf
                                        <button type="submit" class="lc-icon-btn restore" title="Unarchive" aria-label="Unarchive conversation">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M9 14 4 9m0 0 5-5M4 9h8a7 7 0 1 1 0 14h-1"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    <form
                                        class="lc-conversation-action-form"
                                        method="POST"
                                        action="{{ route('inbox.trash', $selectedConversation) }}"
                                        data-confirm-title="Move to trash"
                                        data-confirm-message="This conversation will be moved to trash."
                                        data-confirm-submit="Move to trash"
                                        style="display:inline;"
                                    >
                                        @csrf
                                        <button type="submit" class="lc-icon-btn trash" title="Trash" aria-label="Move conversation to trash">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4.75A1.75 1.75 0 0 1 9.75 3h4.5A1.75 1.75 0 0 1 16 4.75V6"></path>
                                                <path d="M19 6l-.85 12.2A2 2 0 0 1 16.15 20H7.85a2 2 0 0 1-1.99-1.8L5 6"></path>
                                                <path d="M10 10.25v5.5M14 10.25v5.5"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <form
                                        class="lc-conversation-action-form"
                                        method="POST"
                                        action="{{ route('inbox.archive', $selectedConversation) }}"
                                        data-confirm-title="Archive conversation"
                                        data-confirm-message="This conversation will be moved out of the active inbox."
                                        data-confirm-submit="Archive"
                                        style="display:inline;"
                                    >
                                        @csrf
                                        <button type="submit" class="lc-icon-btn archive" title="Archive" aria-label="Archive conversation">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M4 7.5h16"></path>
                                                <path d="M5.75 4h12.5A1.75 1.75 0 0 1 20 5.75v2.5A1.75 1.75 0 0 1 18.25 10H5.75A1.75 1.75 0 0 1 4 8.25v-2.5A1.75 1.75 0 0 1 5.75 4Z"></path>
                                                <path d="M7 10v7.25A1.75 1.75 0 0 0 8.75 19h6.5A1.75 1.75 0 0 0 17 17.25V10"></path>
                                                <path d="m10 13 2 2 2-2"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    <form
                                        class="lc-conversation-action-form"
                                        method="POST"
                                        action="{{ route('inbox.trash', $selectedConversation) }}"
                                        data-confirm-title="Move to trash"
                                        data-confirm-message="This conversation will be moved to trash."
                                        data-confirm-submit="Move to trash"
                                        style="display:inline;"
                                    >
                                        @csrf
                                        <button type="submit" class="lc-icon-btn trash" title="Trash" aria-label="Move conversation to trash">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4.75A1.75 1.75 0 0 1 9.75 3h4.5A1.75 1.75 0 0 1 16 4.75V6"></path>
                                                <path d="M19 6l-.85 12.2A2 2 0 0 1 16.15 20H7.85a2 2 0 0 1-1.99-1.8L5 6"></path>
                                                <path d="M10 10.25v5.5M14 10.25v5.5"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <button type="button" id="lcRefreshConversationBtn" class="lc-icon-btn refresh" title="Refresh" aria-label="Refresh conversation">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                                        <path d="M21 3v6h-6"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div id="lcMessageArea" class="lc-message-area">
                            <div class="lc-policy">
                                This is a UI-only policy notice placeholder for channel restrictions and allowed follow-up message rules.
                            </div>

                            <div class="lc-message-stack">
                                @foreach ($messages as $message)
                                    @php
                                        $isOutbound = $message->direction === 'outbound';
                                        $reply = $message->replyToMessage;
                                        $sender = $message->senderParticipant;
                                        $messageMeta = is_array($message->meta) ? $message->meta : [];
                                        $isStoryReplyMessage = (bool) ($messageMeta['is_story_reply'] ?? false);
                                        $storyReplyContextType = is_string($messageMeta['message_context_type'] ?? null)
                                            ? $messageMeta['message_context_type']
                                            : null;
                                        $storyReplyId = is_string($messageMeta['story_id'] ?? null) && $messageMeta['story_id'] !== ''
                                            ? $messageMeta['story_id']
                                            : null;
                                        $storyReplyContext = is_array($messageMeta['story_context'] ?? null)
                                            ? $messageMeta['story_context']
                                            : [];
                                        $storyReplyReferral = is_array($messageMeta['referral'] ?? null)
                                            ? $messageMeta['referral']
                                            : [];
                                        $storyReplyTitle = $storyReplyContext['title']
                                            ?? $storyReplyReferral['title']
                                            ?? $storyReplyReferral['ref']
                                            ?? null;
                                        $isCommentReplyDmMessage = (bool) ($messageMeta['social_comment_reply'] ?? false);
                                        $commentReplyAuthor = is_string($messageMeta['comment_author'] ?? null)
                                            ? $messageMeta['comment_author']
                                            : null;
                                        $commentReplyText = is_string($messageMeta['comment_text'] ?? null)
                                            ? $messageMeta['comment_text']
                                            : null;
                                        $commentReplyPostCaption = is_string($messageMeta['post_caption'] ?? null)
                                            ? $messageMeta['post_caption']
                                            : null;
                                        $commentReplyPostCoverUrl = is_string($messageMeta['post_cover_url'] ?? null)
                                            ? $messageMeta['post_cover_url']
                                            : null;
                                        $commentReplyPostPermalink = is_string($messageMeta['post_permalink'] ?? null)
                                            ? $messageMeta['post_permalink']
                                            : null;
                                        $renderCommentPrivateReplyCard = $isCommentReplyDmMessage && $isOutbound;
                                        $agentReaction = is_array($messageMeta['agent_reaction'] ?? null)
                                            ? $messageMeta['agent_reaction']
                                            : null;
                                        $customerReaction = is_array($messageMeta['customer_reaction'] ?? null)
                                            ? $messageMeta['customer_reaction']
                                            : null;
                                        $agentReactionEmoji = is_string($agentReaction['emoji'] ?? null) ? $agentReaction['emoji'] : null;
                                        $customerReactionEmoji = is_string($customerReaction['emoji'] ?? null) ? $customerReaction['emoji'] : null;
                                        $canReactFromInbox = ! $isOutbound
                                            && $selectedConversation?->provider === 'instagram'
                                            && filled($message->provider_message_id);
                                    @endphp

                                    <div
                                        class="lc-message-row {{ $isOutbound ? 'outbound' : 'inbound' }}"
                                        data-message-id="{{ $message->id }}"
                                        data-provider-message-id="{{ $message->provider_message_id }}"
                                    >
                                        <div class="lc-message-wrap">
                                            <img
                                                class="lc-message-avatar"
                                                src="{{ $sender?->avatar_url ?? ($isOutbound ? 'https://ui-avatars.com/api/?name=LC&background=724cda&color=ffffff' : 'https://ui-avatars.com/api/?name=User&background=e2e8f0&color=334155') }}"
                                                alt="Avatar"
                                            >

                                            <div class="lc-message-body">
                                                @unless ($isOutbound)
                                                    <div class="lc-message-name">
                                                        {{ $sender?->display_name ?? 'User' }}
                                                    </div>
                                                @endunless

                                                @if ($isStoryReplyMessage)
                                                    <div style="margin-bottom: .35rem; display:flex; gap:.35rem; flex-wrap:wrap;">
                                                        <span class="lc-badge story-reply">Story reply</span>
                                                    </div>

                                                    <div class="lc-story-reply-box">
                                                        <div class="lc-story-reply-title">Instagram story context</div>

                                                        <div class="lc-story-reply-meta">
                                                            @if ($storyReplyContextType)
                                                                <span class="lc-story-reply-pill">Type: {{ str_replace('_', ' ', $storyReplyContextType) }}</span>
                                                            @endif

                                                            @if ($storyReplyId)
                                                                <span class="lc-story-reply-pill">Story ID: {{ $storyReplyId }}</span>
                                                            @endif
                                                        </div>

                                                        @if ($storyReplyTitle)
                                                            <div class="lc-story-reply-text">
                                                                {{ $storyReplyTitle }}
                                                            </div>
                                                        @elseif (!empty($storyReplyContext) || !empty($storyReplyReferral))
                                                            <div class="lc-story-reply-text">
                                                                This message was classified as a reply to an Instagram story and includes story-related metadata.
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if ($renderCommentPrivateReplyCard)
                                                    <div class="lc-comment-private-reply-card">
                                                        <div class="lc-comment-private-reply-top">
                                                            <span class="lc-badge comment-reply">Comment DM reply</span>
                                                            @if ($commentReplyAuthor)
                                                                <span class="lc-comment-private-reply-author">{{ $commentReplyAuthor }}</span>
                                                            @endif
                                                        </div>

                                                        <div class="lc-comment-private-reply-layout">
                                                            @if ($commentReplyPostCoverUrl)
                                                                <div class="lc-comment-private-reply-cover">
                                                                    <img src="{{ $commentReplyPostCoverUrl }}" alt="Instagram post cover">
                                                                </div>
                                                            @endif

                                                            <div class="lc-comment-private-reply-content">
                                                                @if ($commentReplyPostCaption)
                                                                    <div class="lc-comment-private-reply-section">
                                                                        <span class="lc-comment-private-reply-kicker">Post caption</span>
                                                                        <div class="lc-comment-private-reply-copy">{{ $commentReplyPostCaption }}</div>
                                                                    </div>
                                                                @endif

                                                                @if ($commentReplyText)
                                                                    <div class="lc-comment-private-reply-section lc-comment-private-reply-comment">
                                                                        <span class="lc-comment-private-reply-kicker">Comment</span>
                                                                        <div class="lc-comment-private-reply-copy">{{ $commentReplyText }}</div>
                                                                    </div>
                                                                @endif

                                                                <div class="lc-comment-private-reply-section lc-comment-private-reply-response">
                                                                    <span class="lc-comment-private-reply-kicker">Reply</span>
                                                                    <div class="lc-comment-private-reply-copy">{{ $message->text_body }}</div>
                                                                </div>

                                                                @if ($commentReplyPostPermalink)
                                                                    <a href="{{ $commentReplyPostPermalink }}" target="_blank" rel="noopener noreferrer" class="lc-comment-private-reply-link">
                                                                        Open post
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @elseif ($isCommentReplyDmMessage)
                                                    <div style="margin-bottom: .35rem; display:flex; gap:.35rem; flex-wrap:wrap;">
                                                        <span class="lc-badge comment-reply">Comment DM reply</span>
                                                    </div>

                                                    <div class="lc-comment-reply-box">
                                                        @if ($commentReplyPostCoverUrl)
                                                            <div class="lc-comment-reply-cover">
                                                                <img src="{{ $commentReplyPostCoverUrl }}" alt="Instagram post cover">
                                                            </div>
                                                        @endif

                                                        @if ($commentReplyText)
                                                            <div class="lc-comment-reply-text">
                                                                <span class="lc-comment-reply-label">Comment</span>
                                                                {{ $commentReplyText }}
                                                            </div>
                                                        @endif

                                                        @if ($commentReplyPostCaption)
                                                            <div class="lc-comment-reply-text">
                                                                <span class="lc-comment-reply-label">Post caption</span>
                                                                {{ $commentReplyPostCaption }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @unless ($renderCommentPrivateReplyCard)
                                                    <div class="lc-bubble {{ $isOutbound ? 'outbound' : 'inbound' }}">
                                                        @if ($reply)
                                                            <div class="lc-reply">
                                                                Reply to: {{ $reply->text_body ?: ($reply->message_type . ' message') }}
                                                            </div>
                                                        @endif

                                                        @if ($message->message_type === 'text')
                                                            <div>{{ $message->text_body }}</div>
                                                        @endif

                                                        @if ($message->message_type === 'image')
                                                            @foreach ($message->attachments as $attachment)
                                                                <div class="lc-image-card">
                                                                    <img src="{{ $attachment->url }}" alt="Image attachment">
                                                                </div>
                                                            @endforeach

                                                            @if ($message->caption)
                                                                <div>{{ $message->caption }}</div>
                                                            @endif
                                                        @endif

                                                        @if ($message->message_type === 'file')
                                                            @foreach ($message->attachments as $attachment)
                                                                <div class="lc-file-card">
                                                                    <div class="lc-file-icon">📎</div>
                                                                    <div class="lc-file-meta">
                                                                        <div class="lc-file-name">
                                                                            <a href="{{ $attachment->url }}" target="_blank" style="color: inherit; text-decoration: none;">
                                                                                {{ $attachment->file_name ?: 'Attachment' }}
                                                                            </a>
                                                                        </div>
                                                                        <div class="lc-file-sub">
                                                                            {{ $attachment->mime_type ?: 'file' }}
                                                                            @if ($attachment->file_size)
                                                                                • {{ number_format($attachment->file_size / 1024, 1) }} KB
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach

                                                            @if ($message->caption)
                                                                <div>{{ $message->caption }}</div>
                                                            @endif
                                                        @endif

                                                        @if ($message->message_type === 'video')
                                                            @foreach ($message->attachments as $attachment)
                                                                <div class="lc-image-card">
                                                                    <video controls playsinline style="display:block;width:100%;max-height:360px;background:#000;">
                                                                        <source src="{{ $attachment->url }}" type="{{ $attachment->mime_type }}">
                                                                    </video>
                                                                </div>
                                                            @endforeach

                                                            @if ($message->caption)
                                                                <div>{{ $message->caption }}</div>
                                                            @endif
                                                        @endif

                                                        @if ($message->message_type === 'voice')
                                                            @foreach ($message->attachments as $attachment)
                                                                <div class="lc-voice-card">
                                                                    <div class="lc-voice-title">Voice message</div>
                                                                    <div class="lc-voice-sub">Duration: {{ $attachment->duration_seconds ?? '-' }} sec</div>
                                                                    <audio controls>
                                                                        <source src="{{ $attachment->url }}" type="{{ $attachment->mime_type }}">
                                                                    </audio>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endunless

                                                <div class="lc-reaction-strip" aria-live="polite">
                                                    <span
                                                        class="lc-reaction-pill customer"
                                                        title="Reaction"
                                                        data-reaction-actor="customer"
                                                        style="{{ $customerReactionEmoji ? '' : 'display:none;' }}"
                                                    >{{ $customerReactionEmoji }}</span>

                                                    <span
                                                        class="lc-reaction-pill agent"
                                                        title="Reaction"
                                                        data-reaction-actor="agent"
                                                        style="{{ $agentReactionEmoji ? '' : 'display:none;' }}"
                                                    >{{ $agentReactionEmoji }}</span>
                                                </div>

                                                <div class="lc-message-meta">
                                                    <span>{{ optional($message->created_at)->format('M d, Y H:i') }}</span>

                                                    @if ($isOutbound)
                                                        @if ($message->status === 'sent')
                                                            <span class="lc-status-icon sent" aria-label="sent">✓</span>
                                                        @elseif ($message->status === 'delivered')
                                                            <span class="lc-status-icon delivered" aria-label="delivered">✓✓</span>
                                                        @elseif ($message->status === 'read')
                                                            <span class="lc-status-icon read" aria-label="read">✓✓</span>
                                                        @elseif ($message->status === 'failed')
                                                            <span class="lc-status-icon failed" aria-label="failed">!</span>
                                                        @endif
                                                    @endif

                                                    <a
                                                        href="{{ route('inbox.show', ['conversation' => $selectedConversation->id, 'reply' => $message->id]) }}"
                                                        style="color: var(--lc-primary); text-decoration: none; font-weight: 600;"
                                                    >
                                                        Reply
                                                    </a>

                                                    @if ($canReactFromInbox)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('inbox.messages.reaction', ['conversation' => $selectedConversation->id, 'message' => $message->id]) }}"
                                                            class="lc-reaction-form"
                                                            data-message-id="{{ $message->id }}"
                                                        >
                                                            @csrf
                                                            <input type="hidden" name="reaction" value="love">
                                                            <input type="hidden" name="action" value="{{ $agentReactionEmoji ? 'unreact' : 'react' }}">
                                                            <button
                                                                type="button"
                                                                class="lc-reaction-trigger {{ $agentReactionEmoji ? 'is-active' : '' }}"
                                                                aria-label="{{ $agentReactionEmoji ? 'Remove reaction' : 'React' }}"
                                                                aria-expanded="false"
                                                                title="{{ $agentReactionEmoji ? 'Remove reaction' : 'React' }}"
                                                            >
                                                                {{ $agentReactionEmoji ? $agentReactionEmoji : '♡' }}
                                                            </button>
                                                            <div class="lc-reaction-picker" role="menu" aria-label="Message reactions">
                                                                <button
                                                                    type="submit"
                                                                    class="lc-reaction-option"
                                                                    name="reaction"
                                                                    value="love"
                                                                    data-reaction-emoji="❤️"
                                                                    title="{{ $agentReactionEmoji ? 'Remove heart' : 'Heart' }}"
                                                                    aria-label="{{ $agentReactionEmoji ? 'Remove heart reaction' : 'React with heart' }}"
                                                                >❤️</button>
                                                            </div>
                                                        </form>
                                                        <span class="lc-reaction-error"></span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="lc-composer-wrap">
                            <div class="lc-composer">
                                <div class="lc-message-types">
                                    <span>Message type:</span>
                                    <span class="lc-message-type-pill">Reply</span>
                                    <span class="lc-message-type-pill">Note</span>
                                    <span class="lc-message-type-pill">Update</span>
                                </div>

                                @if ($errors->has('message_text'))
                                    <div style="margin-bottom: 10px; color: #e64759; font-size: 13px;">
                                        {{ $errors->first('message_text') }}
                                    </div>
                                @endif

                                @if ($errors->has('attachment_files'))
                                    <div style="margin-bottom: 10px; color: #e64759; font-size: 13px;">
                                        {{ $errors->first('attachment_files') }}
                                    </div>
                                @endif

                                @if ($errors->has('attachment_files.*'))
                                    <div style="margin-bottom: 10px; color: #e64759; font-size: 13px;">
                                        {{ $errors->first('attachment_files.*') }}
                                    </div>
                                @endif

                                <form id="lcMessageForm" class="lc-message-form" method="POST" action="{{ route('inbox.messages.store', $selectedConversation) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="reply_to_message_id" value="{{ $replyTarget?->id }}">

                                    @if ($replyTarget)
                                        <div data-reply-preview style="margin-bottom: 10px; border-left: 3px solid var(--lc-primary); background: rgba(114, 76, 218, .06); border-radius: 10px; padding: 10px 12px;">
                                            <div style="font-size: 12px; color: var(--lc-text-soft); margin-bottom: 4px; font-weight: 700;">
                                                Replying to
                                            </div>
                                            <div style="font-size: 13px; color: var(--lc-text); line-height: 1.5;">
                                                {{ $replyTarget->text_body ?: ($replyTarget->message_type . ' message') }}
                                            </div>
                                            <div style="margin-top: 8px;">
                                                <a
                                                    href="{{ route('inbox.show', ['conversation' => $selectedConversation->id, 'reply' => null]) }}"
                                                    style="font-size: 12px; color: var(--lc-danger); text-decoration: none; font-weight: 700;"
                                                >
                                                    Cancel reply
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                    <div
                                        id="lcComposerPreviewWrap"
                                        class="lc-composer-preview-wrap"
                                    >
                                        <div id="lcComposerPreviewSummary" class="lc-composer-preview-summary"></div>
                                        <div id="lcComposerPreviewList" class="lc-composer-preview-list"></div>
                                    </div>

                                    <div id="lcEmojiPicker" class="lc-emoji-picker">
                                        <div class="lc-emoji-search-wrap">
                                            <input type="text" id="lcEmojiSearch" class="lc-emoji-search" placeholder="Search emoji...">
                                        </div>

                                        <div id="lcEmojiTabs" class="lc-emoji-tabs"></div>

                                        <div class="lc-emoji-body">
                                            <div id="lcEmojiGrid" class="lc-emoji-grid"></div>
                                        </div>

                                        <div id="lcEmojiFooter" class="lc-emoji-footer"></div>
                                    </div>

                                    <div class="lc-editor-box">
                                        <textarea
                                            id="lcMessageTextarea"
                                            name="message_text"
                                            placeholder="Write a message..."
                                        >{{ old('message_text') }}</textarea>

                                        <div class="lc-editor-actions">
                                            <div class="lc-editor-icons">
                                                <label class="lc-editor-icon lc-attach-inline" title="Attach file">
                                                    <span>📎</span>
                                                    <input
                                                        type="file"
                                                        name="attachment_files[]"
                                                        id="lcAttachmentInput"
                                                        class="lc-hidden-file-input"
                                                        accept="image/*,video/*,audio/*,application/pdf"
                                                        multiple
                                                    >
                                                </label>
                                                <span class="lc-editor-icon">💬</span>
                                                <button
                                                    type="button"
                                                    id="lcStartRecordingBtn"
                                                    class="lc-editor-icon"
                                                    title="Record voice"
                                                    style="border:0;background:transparent;padding:0;cursor:pointer;"
                                                >
                                                    🎤
                                                </button>
                                                <button
                                                    type="button"
                                                    id="lcEmojiToggleBtn"
                                                    class="lc-editor-icon"
                                                    title="Emoji"
                                                    style="border:0;background:transparent;padding:0;cursor:pointer;"
                                                >
                                                    😊
                                                </button>
                                            </div>

                                            <button type="submit" class="lc-send">Send</button>
                                        </div>
                                    </div>
                                </form>

                                @if ($errors->has('voice_file'))
                                    <div style="margin-top: 10px; color: #e64759; font-size: 13px;">
                                        {{ $errors->first('voice_file') }}
                                    </div>
                                @endif

                                <div id="lcRecordingWrap" class="lc-recording-wrap">
                                    <div class="lc-recording-box">
                                        <div class="lc-recording-top">
                                            <div class="lc-recording-left">
                                                <div class="lc-recording-dot"></div>
                                                <div>
                                                    <div class="lc-recording-title">Recording voice message</div>
                                                    <div id="lcRecordingTimer" class="lc-recording-sub">00:00</div>
                                                </div>
                                            </div>

                                            <div class="lc-recording-actions">
                                                <button type="button" id="lcRecordingCancel" class="lc-rec-btn cancel">Cancel</button>
                                                <button type="button" id="lcRecordingStop" class="lc-rec-btn">Stop</button>
                                                <button type="button" id="lcRecordingSend" class="lc-rec-btn primary" disabled>Send</button>
                                            </div>
                                        </div>

                                        <div id="lcRecordingPreview" class="lc-recording-preview">
                                            <audio id="lcRecordingAudio" controls></audio>

                                            <div class="lc-recording-speed">
                                                <span>Speed:</span>
                                                <button type="button" class="lc-speed-btn is-active" data-speed="1">1x</button>
                                                <button type="button" class="lc-speed-btn" data-speed="1.5">1.5x</button>
                                                <button type="button" class="lc-speed-btn" data-speed="2">2x</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form
                                    id="lcVoiceForm"
                                    class="lc-voice-form"
                                    method="POST"
                                    action="{{ route('inbox.messages.voice', $selectedConversation) }}"
                                    enctype="multipart/form-data"
                                    style="display:none;"
                                >
                                    @csrf
                                    <input type="file" name="voice_file" id="lcVoiceFormInput" accept="audio/*">
                                    <input type="hidden" name="duration_seconds" id="lcVoiceDurationInput">
                                </form>

                            </div>
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center text-sm text-slate-500">
                            Select a conversation
                        </div>
                    @endif
                </section>

                <aside class="lc-aside">
                    @if ($selectedConversation)
                        <div class="lc-aside-scroll">
                            <div class="lc-card">
                                <div class="lc-card-header-center">
                                    <img
                                        class="lc-aside-avatar"
                                        src="{{ $selectedCustomer?->avatar_url ?? $selectedConversation->avatar_url ?? 'https://ui-avatars.com/api/?name=User&background=e2e8f0&color=334155' }}"
                                        alt="Avatar"
                                    >
                                    <div class="lc-card-title">
                                        {{ $selectedConversation->title ?? $selectedCustomer?->display_name ?? 'Conversation' }}
                                    </div>
                                    <div class="lc-card-sub">
                                        {{ $selectedCustomer?->handle ?? '@unknown' }}
                                    </div>
                                </div>
                            </div>

                            <div class="lc-card">
                                <h3 class="lc-section-title">User details</h3>
                                <div class="lc-detail-grid">
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">User ID</div>
                                        <div class="lc-detail-val">{{ $selectedCustomer?->provider_user_id ?? '-' }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Full name</div>
                                        <div class="lc-detail-val">{{ $selectedCustomer?->display_name ?? '-' }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Handle</div>
                                        <div class="lc-detail-val">{{ $selectedCustomer?->handle ?? '-' }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">User type</div>
                                        <div class="lc-detail-val">Lead</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Creation time</div>
                                        <div class="lc-detail-val">{{ optional($selectedConversation->created_at)->format('m/d/y') }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Last activity</div>
                                        <div class="lc-detail-val">{{ optional($selectedConversation->last_message_at)->format('m/d/y') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-card">
                                <h3 class="lc-section-title">Conversation details</h3>
                                <div class="lc-detail-grid">
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Conversation ID</div>
                                        <div class="lc-detail-val">{{ $selectedConversation->id }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Provider</div>
                                        <div class="lc-detail-val">{{ ucfirst($selectedConversation->provider) }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Type</div>
                                        <div class="lc-detail-val">{{ $selectedConversation->type }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Status</div>
                                        <div class="lc-detail-val">{{ $selectedConversation->status }}</div>
                                    </div>
                                    <div class="lc-detail-row">
                                        <div class="lc-detail-key">Unread</div>
                                        <div class="lc-detail-val">{{ $selectedConversation->unread_count }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-card">
                                <h3 class="lc-section-title">Department</h3>

                                @php
                                    $conversationDepartment = $selectedConversation?->department;
                                @endphp

                                <div id="lcConversationDepartmentCurrent" style="margin-bottom:8px;">
                                    @if ($conversationDepartment)
                                        <span
                                            data-department-id="{{ $conversationDepartment->id }}"
                                            style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:{{ $conversationDepartment->color }}20; color:#334155; border:1px solid {{ $conversationDepartment->color }}55; font-size:11px; font-weight:700;"
                                        >
                                            {{ $conversationDepartment->name }}
                                        </span>
                                    @else
                                        <span id="lcConversationDepartmentEmpty" style="font-size:11px; color:#94a3b8;">
                                            No department assigned
                                        </span>
                                    @endif
                                </div>

                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <select
                                        id="lcDepartmentSelect"
                                        style="width:100%; height:34px; border:1px dashed #cbd5e1; border-radius:8px; padding:0 10px; background:#f8fafc; color:#475569; font-size:12px; outline:none;"
                                    >
                                        <option value="">No department</option>
                                        @foreach (($workspaceDepartments ?? collect()) as $department)
                                            <option
                                                value="{{ $department->id }}"
                                                data-color="{{ $department->color }}"
                                                {{ $conversationDepartment && $conversationDepartment->id === $department->id ? 'selected' : '' }}
                                            >
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div id="lcDepartmentError" style="display:none; color:#e64759; font-size:11px;"></div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                        <div id="lcDepartmentSaved" style="display:none; color:#16a34a; font-size:11px; font-weight:700;">
                                            Saved
                                        </div>

                                        <button
                                            id="lcSaveConversationDepartmentBtn"
                                            type="button"
                                            style="min-height:28px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; padding:0 10px; font-size:12px; font-weight:700; color:#334155;"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-card">
                                <h3 class="lc-section-title">Agent</h3>

                                @php
                                    $assignedAgent = $selectedConversation?->assignedAgent;
                                @endphp

                                <div id="lcConversationAgentCurrent" style="margin-bottom:8px;">
                                    @if ($assignedAgent)
                                        <span
                                            data-agent-id="{{ $assignedAgent->id }}"
                                            style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:#eef2ff; color:#334155; border:1px solid #c7d2fe; font-size:11px; font-weight:700;"
                                        >
                                            {{ $assignedAgent->name ?: $assignedAgent->email }}
                                        </span>
                                    @else
                                        <span id="lcConversationAgentEmpty" style="font-size:11px; color:#94a3b8;">
                                            No agent assigned
                                        </span>
                                    @endif
                                </div>

                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <select
                                        id="lcAgentSelect"
                                        style="width:100%; height:34px; border:1px dashed #cbd5e1; border-radius:8px; padding:0 10px; background:#f8fafc; color:#475569; font-size:12px; outline:none;"
                                    >
                                        <option value="">No agent</option>
                                        @foreach (($workspaceMembers ?? collect()) as $member)
                                            <option
                                                value="{{ $member->id }}"
                                                data-email="{{ $member->email }}"
                                                data-role="{{ $member->pivot->role ?: 'member' }}"
                                                {{ $assignedAgent && $assignedAgent->id === $member->id ? 'selected' : '' }}
                                            >
                                                {{ $member->name ?: $member->email }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div id="lcAgentError" style="display:none; color:#e64759; font-size:11px;"></div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                        <div id="lcAgentSaved" style="display:none; color:#16a34a; font-size:11px; font-weight:700;">
                                            Saved
                                        </div>

                                        <button
                                            id="lcSaveConversationAgentBtn"
                                            type="button"
                                            style="min-height:28px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; padding:0 10px; font-size:12px; font-weight:700; color:#334155;"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="lc-card">
                                <h3 class="lc-section-title">Notes</h3>

                                <form
                                    id="lcInternalNoteForm"
                                    method="POST"
                                    action="{{ route('inbox.note.save', $selectedConversation) }}"
                                >
                                    @csrf
                                    <input type="hidden" name="view" value="{{ $viewMode ?? 'inbox' }}">

                                    <textarea
                                        id="lcInternalNoteTextarea"
                                        name="internal_note"
                                        placeholder="Write internal note..."
                                        style="width:100%; min-height:72px; resize:none; border:1px dashed #cbd5e1; border-radius:8px; padding:8px 10px; background:#f8fafc; color:#475569; font-size:12px; line-height:1.4; outline:none;"
                                    >{{ old('internal_note', $selectedConversation->internal_note) }}</textarea>

                                    <div
                                        id="lcInternalNoteError"
                                        style="display:none; margin-top:6px; color:#e64759; font-size:11px;"
                                    ></div>

                                    <div style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                        <div
                                            id="lcInternalNoteSaved"
                                            style="display:none; color:#16a34a; font-size:11px; font-weight:700;"
                                        >
                                            Saved
                                        </div>

                                        <button
                                            id="lcInternalNoteSaveBtn"
                                            type="submit"
                                            style="min-height:28px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; padding:0 10px; font-size:12px; font-weight:700; color:#334155;"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </form>
                            </div>

                          <div class="lc-card">
                                <h3 class="lc-section-title">Tags</h3>

                                @php
                                    $conversationTags = $selectedConversation?->workspaceTags ?? collect();
                                @endphp

                                <div
                                    id="lcConversationTagsSelected"
                                    style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px;"
                                >
                                    @forelse ($conversationTags as $tag)
                                        <span
                                            data-tag-id="{{ $tag->id }}"
                                            style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:{{ $tag->color }}20; color:#334155; border:1px solid {{ $tag->color }}55; font-size:11px; font-weight:700;"
                                        >
                                            {{ $tag->name }}
                                        </span>
                                    @empty
                                        <span id="lcConversationTagsEmpty" style="font-size:11px; color:#94a3b8;">
                                            No tags assigned
                                        </span>
                                    @endforelse
                                </div>

                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <input
                                        id="lcTagsSearchInput"
                                        type="text"
                                        placeholder="Search tags..."
                                        style="width:100%; height:34px; border:1px dashed #cbd5e1; border-radius:8px; padding:0 10px; background:#f8fafc; color:#475569; font-size:12px; outline:none;"
                                    >

                                    <div
                                        id="lcWorkspaceTagsList"
                                        style="display:flex; flex-wrap:wrap; gap:6px; min-height:28px;"
                                    >
                                        @foreach (($workspaceTags ?? collect()) as $tag)
                                            @php
                                                $isSelected = $conversationTags->contains('id', $tag->id);
                                            @endphp
                                            <button
                                                type="button"
                                                class="lc-workspace-tag-option {{ $isSelected ? 'is-selected' : '' }}"
                                                data-tag-id="{{ $tag->id }}"
                                                data-tag-name="{{ strtolower($tag->name) }}"
                                                data-tag-color="{{ $tag->color }}"
                                                style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:{{ $isSelected ? $tag->color.'20' : '#fff' }}; color:#334155; border:1px solid {{ $isSelected ? $tag->color.'66' : '#e2e8f0' }}; font-size:11px; font-weight:700; cursor:pointer;"
                                            >
                                                {{ $tag->name }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <div id="lcTagsError" style="display:none; color:#e64759; font-size:11px;"></div>

                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                        <div id="lcTagsSaved" style="display:none; color:#16a34a; font-size:11px; font-weight:700;">
                                            Saved
                                        </div>

                                        <button
                                            id="lcSaveConversationTagsBtn"
                                            type="button"
                                            style="min-height:28px; border:1px solid #e2e8f0; background:#fff; border-radius:8px; padding:0 10px; font-size:12px; font-weight:700; color:#334155;"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="lc-card">
                                <h3 class="lc-section-title">Attachments</h3>
                                <div class="lc-inline-box">{{ $attachmentCount }} attachment(s)</div>
                            </div>
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center px-4 text-center text-sm text-slate-500">
                            No conversation details
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    <div id="lcConfirmModal" class="lc-modal" aria-hidden="true">
        <div class="lc-modal-backdrop" data-confirm-close></div>

        <div class="lc-modal-card" role="dialog" aria-modal="true" aria-labelledby="lcConfirmModalTitle">
            <div id="lcConfirmModalTitle" class="lc-modal-title">Confirm action</div>
            <div id="lcConfirmModalBody" class="lc-modal-body">Please confirm this action.</div>

            <div class="lc-modal-actions">
                <button type="button" id="lcConfirmModalCancel" class="lc-modal-btn">Cancel</button>
                <button type="button" id="lcConfirmModalSubmit" class="lc-modal-btn primary">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('lcConfirmModal');
            const modalTitle = document.getElementById('lcConfirmModalTitle');
            const modalBody = document.getElementById('lcConfirmModalBody');
            const modalCancel = document.getElementById('lcConfirmModalCancel');
            const modalSubmit = document.getElementById('lcConfirmModalSubmit');

            if (!modal || !modalTitle || !modalBody || !modalCancel || !modalSubmit) {
                return;
            }

            let pendingForm = null;

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
            };

            const openModal = (form) => {
                pendingForm = form;
                modalTitle.textContent = form.getAttribute('data-confirm-title') || 'Confirm action';
                modalBody.textContent = form.getAttribute('data-confirm-message') || 'Please confirm this action.';
                modalSubmit.textContent = form.getAttribute('data-confirm-submit') || 'Confirm';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            };

            document.addEventListener('submit', function (event) {
                const form = event.target.closest('form[data-confirm-title]');

                if (!form) {
                    return;
                }

                if (form.dataset.confirmed === '1') {
                    delete form.dataset.confirmed;
                    return;
                }

                event.preventDefault();
                openModal(form);
            });

            modal.addEventListener('click', function (event) {
                if (event.target.closest('[data-confirm-close]')) {
                    closeModal();
                }
            });

            modalCancel.addEventListener('click', closeModal);

            modalSubmit.addEventListener('click', function () {
                if (!pendingForm) {
                    closeModal();
                    return;
                }

                pendingForm.dataset.confirmed = '1';
                const form = pendingForm;
                closeModal();
                form.requestSubmit();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const messageArea = document.getElementById('lcMessageArea');

            if (!messageArea) {
                return;
            }

            let shouldStickToBottom = true;

            const isNearBottom = () => {
                return messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 80;
            };

            const scrollToBottom = () => {
                messageArea.scrollTop = messageArea.scrollHeight;
            };

            const revealMessageArea = () => {
                messageArea.classList.add('is-ready');
            };

            const syncScrollPosition = () => {
                shouldStickToBottom = isNearBottom();
            };

            messageArea.addEventListener('scroll', syncScrollPosition);

            requestAnimationFrame(() => {
                scrollToBottom();
                requestAnimationFrame(() => {
                    scrollToBottom();
                    revealMessageArea();
                });
            });

            messageArea.querySelectorAll('img, video, audio').forEach((element) => {
                const maybeStickToBottom = () => {
                    if (shouldStickToBottom) {
                        scrollToBottom();
                    }

                    revealMessageArea();
                };

                element.addEventListener('load', maybeStickToBottom);
                element.addEventListener('loadedmetadata', maybeStickToBottom);
                element.addEventListener('loadeddata', maybeStickToBottom);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const currentDepartmentWrap = document.getElementById('lcConversationDepartmentCurrent');
            const departmentSelect = document.getElementById('lcDepartmentSelect');
            const saveDepartmentBtn = document.getElementById('lcSaveConversationDepartmentBtn');
            const departmentError = document.getElementById('lcDepartmentError');
            const departmentSaved = document.getElementById('lcDepartmentSaved');
            const saveDepartmentUrl = @json($selectedConversation ? route('inbox.department.save', $selectedConversation) : null);
            const csrfToken = '{{ csrf_token() }}';

            if (!currentDepartmentWrap || !departmentSelect || !saveDepartmentBtn) {
                return;
            }

            const clearDepartmentState = () => {
                if (departmentError) {
                    departmentError.style.display = 'none';
                    departmentError.textContent = '';
                }
                if (departmentSaved) {
                    departmentSaved.style.display = 'none';
                }
            };

            const showDepartmentError = (message) => {
                if (!departmentError) return;
                departmentError.textContent = message;
                departmentError.style.display = 'block';
            };

            const flashDepartmentSaved = () => {
                if (!departmentSaved) return;
                departmentSaved.style.display = 'block';
                setTimeout(() => {
                    departmentSaved.style.display = 'none';
                }, 1800);
            };

            const renderCurrentDepartment = () => {
                const option = departmentSelect.options[departmentSelect.selectedIndex] || null;
                const departmentId = option ? option.value : '';
                const departmentName = option ? option.textContent.trim() : '';
                const departmentColor = option ? (option.getAttribute('data-color') || '#6366f1') : '#6366f1';

                if (!departmentId) {
                    currentDepartmentWrap.innerHTML = '<span id="lcConversationDepartmentEmpty" style="font-size:11px; color:#94a3b8;">No department assigned</span>';
                    return;
                }

                currentDepartmentWrap.innerHTML = `
                    <span
                        data-department-id="${departmentId}"
                        style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:${departmentColor}20; color:#334155; border:1px solid ${departmentColor}55; font-size:11px; font-weight:700;"
                    >
                        ${departmentName}
                    </span>
                `;
            };

            departmentSelect.addEventListener('change', function () {
                clearDepartmentState();
                renderCurrentDepartment();
            });

            saveDepartmentBtn.addEventListener('click', async function (event) {
                event.preventDefault();
                clearDepartmentState();

                if (!saveDepartmentUrl) {
                    showDepartmentError('No conversation selected.');
                    return;
                }

                const originalText = saveDepartmentBtn.textContent;
                saveDepartmentBtn.disabled = true;
                saveDepartmentBtn.textContent = 'Saving...';

                try {
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('department_id', departmentSelect.value);

                    const response = await fetch(saveDepartmentUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showDepartmentError(
                            data?.errors?.department_id?.[0] ||
                            data?.message ||
                            'Failed to save department.'
                        );
                        return;
                    }

                    if (data && Object.prototype.hasOwnProperty.call(data, 'department')) {
                        if (data.department) {
                            const optionToSelect = Array.from(departmentSelect.options).find((option) => Number(option.value) === Number(data.department.id));
                            if (optionToSelect) {
                                departmentSelect.value = String(data.department.id);
                            }
                        } else {
                            departmentSelect.value = '';
                        }
                    }

                    renderCurrentDepartment();
                    flashDepartmentSaved();
                } catch (error) {
                    showDepartmentError('Failed to save department.');
                } finally {
                    saveDepartmentBtn.disabled = false;
                    saveDepartmentBtn.textContent = originalText;
                }
            });

            renderCurrentDepartment();
        });
    </script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const currentAgentWrap = document.getElementById('lcConversationAgentCurrent');
            const agentSelect = document.getElementById('lcAgentSelect');
            const saveAgentBtn = document.getElementById('lcSaveConversationAgentBtn');
            const agentError = document.getElementById('lcAgentError');
            const agentSaved = document.getElementById('lcAgentSaved');
            const saveAgentUrl = @json($selectedConversation ? route('inbox.agent.save', $selectedConversation) : null);
            const csrfToken = '{{ csrf_token() }}';

            if (!currentAgentWrap || !agentSelect || !saveAgentBtn) {
                return;
            }

            const clearAgentState = () => {
                if (agentError) {
                    agentError.style.display = 'none';
                    agentError.textContent = '';
                }
                if (agentSaved) {
                    agentSaved.style.display = 'none';
                }
            };

            const showAgentError = (message) => {
                if (!agentError) return;
                agentError.textContent = message;
                agentError.style.display = 'block';
            };

            const flashAgentSaved = () => {
                if (!agentSaved) return;
                agentSaved.style.display = 'block';
                setTimeout(() => {
                    agentSaved.style.display = 'none';
                }, 1800);
            };

            const renderCurrentAgent = () => {
                const option = agentSelect.options[agentSelect.selectedIndex] || null;
                const agentId = option ? option.value : '';
                const agentName = option ? option.textContent.trim() : '';

                if (!agentId) {
                    currentAgentWrap.innerHTML = '<span id="lcConversationAgentEmpty" style="font-size:11px; color:#94a3b8;">No agent assigned</span>';
                    return;
                }

                currentAgentWrap.innerHTML = `
                    <span
                        data-agent-id="${agentId}"
                        style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:#eef2ff; color:#334155; border:1px solid #c7d2fe; font-size:11px; font-weight:700;"
                    >
                        ${agentName}
                    </span>
                `;
            };

            agentSelect.addEventListener('change', function () {
                clearAgentState();
                renderCurrentAgent();
            });

            saveAgentBtn.addEventListener('click', async function (event) {
                event.preventDefault();
                clearAgentState();

                if (!saveAgentUrl) {
                    showAgentError('No conversation selected.');
                    return;
                }

                const originalText = saveAgentBtn.textContent;
                saveAgentBtn.disabled = true;
                saveAgentBtn.textContent = 'Saving...';

                try {
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('assigned_user_id', agentSelect.value);

                    const response = await fetch(saveAgentUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showAgentError(
                            data?.errors?.assigned_user_id?.[0] ||
                            data?.message ||
                            'Failed to save agent.'
                        );
                        return;
                    }

                    if (data && Object.prototype.hasOwnProperty.call(data, 'agent')) {
                        if (data.agent) {
                            const optionToSelect = Array.from(agentSelect.options).find((option) => Number(option.value) === Number(data.agent.id));
                            if (optionToSelect) {
                                agentSelect.value = String(data.agent.id);
                            }
                        } else {
                            agentSelect.value = '';
                        }
                    }

                    renderCurrentAgent();
                    flashAgentSaved();
                } catch (error) {
                    showAgentError('Failed to save agent.');
                } finally {
                    saveAgentBtn.disabled = false;
                    saveAgentBtn.textContent = originalText;
                }
            });

            renderCurrentAgent();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('lcAttachmentInput');
            const wrap = document.getElementById('lcComposerPreviewWrap');
            const list = document.getElementById('lcComposerPreviewList');
            const summary = document.getElementById('lcComposerPreviewSummary');

            if (!input || !wrap || !list || !summary) {
                return;
            }

            let selectedFiles = [];
            let objectUrls = [];

            const releaseObjectUrls = () => {
                objectUrls.forEach((url) => URL.revokeObjectURL(url));
                objectUrls = [];
            };

            const syncInputFiles = () => {
                const transfer = new DataTransfer();

                selectedFiles.forEach((file) => {
                    transfer.items.add(file);
                });

                input.files = transfer.files;
            };

            const formatBytes = (bytes) => {
                if (!bytes || bytes <= 0) {
                    return '0 B';
                }

                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let size = bytes;
                let unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size /= 1024;
                    unitIndex++;
                }

                return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
            };

            const buildFileKindLabel = (file) => {
                if (file.type.startsWith('image/')) {
                    return 'Image';
                }

                if (file.type.startsWith('video/')) {
                    return 'Video';
                }

                if (file.type.startsWith('audio/')) {
                    return 'Audio';
                }

                return 'File';
            };

            const buildPreviewThumb = (file, objectUrl) => {
                if (file.type.startsWith('image/')) {
                    return `<img src="${objectUrl}" alt="${file.name}">`;
                }

                if (file.type.startsWith('video/')) {
                    return `<video src="${objectUrl}" muted playsinline></video>`;
                }

                if (file.type.startsWith('audio/')) {
                    return '<span>🎤</span>';
                }

                return '<span>📎</span>';
            };

            const renderPreviewList = () => {
                releaseObjectUrls();
                list.innerHTML = '';

                if (!selectedFiles.length) {
                    wrap.classList.remove('is-visible');
                    summary.textContent = '';
                    syncInputFiles();
                    return;
                }

                wrap.classList.add('is-visible');
                summary.textContent = `${selectedFiles.length} attachment(s) selected`;

                selectedFiles.forEach((file, index) => {
                    const objectUrl = URL.createObjectURL(file);
                    objectUrls.push(objectUrl);

                    const item = document.createElement('div');
                    item.className = 'lc-composer-preview';
                    item.innerHTML = `
                        <div class="lc-composer-preview-top">
                            <div class="lc-composer-preview-main">
                                <div class="lc-composer-preview-thumb">
                                    ${buildPreviewThumb(file, objectUrl)}
                                </div>
                                <div class="lc-composer-preview-meta">
                                    <div class="lc-composer-preview-name">${file.name}</div>
                                    <div class="lc-composer-preview-sub">${buildFileKindLabel(file)} • ${formatBytes(file.size)}</div>
                                </div>
                            </div>
                            <button type="button" class="lc-composer-preview-remove" data-preview-index="${index}">Remove</button>
                        </div>
                    `;

                    list.appendChild(item);
                });

                syncInputFiles();
            };

            input.addEventListener('change', function () {
                const incomingFiles = Array.from(input.files || []);

                if (!incomingFiles.length) {
                    return;
                }

                selectedFiles = selectedFiles.concat(incomingFiles);
                renderPreviewList();
            });

            list.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-preview-index]');
                if (!removeButton) {
                    return;
                }

                const index = Number(removeButton.getAttribute('data-preview-index'));
                if (Number.isNaN(index)) {
                    return;
                }

                selectedFiles.splice(index, 1);
                renderPreviewList();
            });

            document.addEventListener('lc:composer-clear', function () {
                selectedFiles = [];
                renderPreviewList();
            });

            window.addEventListener('beforeunload', releaseObjectUrls);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startRecordingBtn = document.getElementById('lcStartRecordingBtn');
            const recordingWrap = document.getElementById('lcRecordingWrap');
            const recordingTimer = document.getElementById('lcRecordingTimer');
            const recordingCancel = document.getElementById('lcRecordingCancel');
            const recordingStop = document.getElementById('lcRecordingStop');
            const recordingSend = document.getElementById('lcRecordingSend');
            const recordingPreview = document.getElementById('lcRecordingPreview');
            const recordingAudio = document.getElementById('lcRecordingAudio');
            const voiceForm = document.getElementById('lcVoiceForm');
            const voiceFormInput = document.getElementById('lcVoiceFormInput');
            const voiceDurationInput = document.getElementById('lcVoiceDurationInput');
            const speedButtons = Array.from(document.querySelectorAll('.lc-speed-btn'));

            if (
                !startRecordingBtn ||
                !recordingWrap ||
                !recordingTimer ||
                !recordingCancel ||
                !recordingStop ||
                !recordingSend ||
                !recordingPreview ||
                !recordingAudio ||
                !voiceForm ||
                !voiceFormInput ||
                !voiceDurationInput
            ) {
                return;
            }

            let mediaRecorder = null;
            let mediaStream = null;
            let recordedChunks = [];
            let recordingTimerInterval = null;
            let recordingSeconds = 0;
            let recordedBlob = null;
            let recordedObjectUrl = null;
            let discardOnStop = false;

            const formatRecordingTime = (seconds) => {
                const mins = String(Math.floor(seconds / 60)).padStart(2, '0');
                const secs = String(seconds % 60).padStart(2, '0');
                return `${mins}:${secs}`;
            };

            const updateRecordingTimer = () => {
                recordingTimer.textContent = formatRecordingTime(recordingSeconds);
            };

            const stopRecordingTimer = () => {
                if (recordingTimerInterval) {
                    clearInterval(recordingTimerInterval);
                    recordingTimerInterval = null;
                }
            };

            const startRecordingTimer = () => {
                stopRecordingTimer();
                recordingSeconds = 0;
                updateRecordingTimer();

                recordingTimerInterval = setInterval(() => {
                    recordingSeconds += 1;
                    updateRecordingTimer();
                }, 1000);
            };

            const stopStreamTracks = () => {
                if (!mediaStream) {
                    return;
                }

                mediaStream.getTracks().forEach((track) => track.stop());
                mediaStream = null;
            };

            const clearRecordedPreview = (resetBlob = true) => {
                if (recordedObjectUrl) {
                    URL.revokeObjectURL(recordedObjectUrl);
                    recordedObjectUrl = null;
                }

                recordingAudio.removeAttribute('src');
                recordingAudio.load();
                voiceFormInput.value = '';
                voiceDurationInput.value = '';

                if (resetBlob) {
                    recordedBlob = null;
                }
            };

            const resetRecordingUi = () => {
                recordingWrap.classList.remove('is-visible');
                recordingPreview.classList.remove('is-visible');
                recordingSend.disabled = true;
                recordingStop.disabled = false;
                recordingSeconds = 0;
                updateRecordingTimer();
                recordingAudio.playbackRate = 1;
                speedButtons.forEach((button) => {
                    button.classList.toggle('is-active', button.dataset.speed === '1');
                });
            };

            const finalizeRecording = () => {
                if (discardOnStop) {
                    discardOnStop = false;
                    clearRecordedPreview();
                    resetRecordingUi();
                    return;
                }

                const mimeType = mediaRecorder && mediaRecorder.mimeType ? mediaRecorder.mimeType : 'audio/webm';
                recordedBlob = new Blob(recordedChunks, { type: mimeType });
                recordedChunks = [];

                if (!recordedBlob.size) {
                    clearRecordedPreview();
                    resetRecordingUi();
                    return;
                }

                clearRecordedPreview(false);
                recordedObjectUrl = URL.createObjectURL(recordedBlob);
                recordingAudio.src = recordedObjectUrl;
                recordingAudio.load();
                recordingPreview.classList.add('is-visible');
                recordingSend.disabled = false;
                recordingStop.disabled = true;
                voiceDurationInput.value = String(recordingSeconds);
            };

            const stopActiveRecording = () => {
                if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                    return;
                }

                stopRecordingTimer();
                mediaRecorder.stop();
            };

            const startRecording = async () => {
                if (!window.MediaRecorder || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    window.alert('Voice recording is not supported in this browser.');
                    return;
                }

                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    return;
                }

                discardOnStop = false;
                clearRecordedPreview();
                recordingPreview.classList.remove('is-visible');
                recordingWrap.classList.add('is-visible');
                recordingSend.disabled = true;
                recordingStop.disabled = false;

                try {
                    mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(mediaStream);
                    recordedChunks = [];

                    mediaRecorder.addEventListener('dataavailable', function (event) {
                        if (event.data && event.data.size > 0) {
                            recordedChunks.push(event.data);
                        }
                    });

                    mediaRecorder.addEventListener('stop', function () {
                        stopStreamTracks();
                        finalizeRecording();
                    });

                    mediaRecorder.start();
                    startRecordingTimer();
                } catch (error) {
                    stopStreamTracks();
                    resetRecordingUi();
                    window.alert('Microphone access was denied or is unavailable.');
                }
            };

            const getExtensionForMimeType = (mimeType) => {
                if (!mimeType) {
                    return 'webm';
                }

                if (mimeType.includes('ogg')) {
                    return 'ogg';
                }

                if (mimeType.includes('mpeg') || mimeType.includes('mp3')) {
                    return 'mp3';
                }

                if (mimeType.includes('wav')) {
                    return 'wav';
                }

                if (mimeType.includes('mp4') || mimeType.includes('m4a') || mimeType.includes('aac')) {
                    return 'm4a';
                }

                return 'webm';
            };

            startRecordingBtn.addEventListener('click', function () {
                startRecording();
            });

            recordingStop.addEventListener('click', function () {
                discardOnStop = false;
                stopActiveRecording();
            });

            recordingCancel.addEventListener('click', function () {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    discardOnStop = true;
                    stopActiveRecording();
                    return;
                }

                clearRecordedPreview();
                resetRecordingUi();
            });

            recordingSend.addEventListener('click', function () {
                if (!recordedBlob) {
                    return;
                }

                const mimeType = recordedBlob.type || 'audio/webm';
                const extension = getExtensionForMimeType(mimeType);
                const file = new File(
                    [recordedBlob],
                    `voice-message-${Date.now()}.${extension}`,
                    { type: mimeType }
                );
                const transfer = new DataTransfer();

                transfer.items.add(file);
                voiceFormInput.files = transfer.files;
                voiceDurationInput.value = String(recordingSeconds);
                recordingSend.disabled = true;
                if (typeof voiceForm.requestSubmit === 'function') {
                    voiceForm.requestSubmit();
                } else {
                    voiceForm.submit();
                }
            });

            document.addEventListener('lc:voice-sent', function () {
                clearRecordedPreview();
                resetRecordingUi();
            });

            speedButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const speed = Number(button.dataset.speed || '1');

                    if (Number.isNaN(speed)) {
                        return;
                    }

                    recordingAudio.playbackRate = speed;
                    speedButtons.forEach((item) => {
                        item.classList.toggle('is-active', item === button);
                    });
                });
            });

            window.addEventListener('beforeunload', function () {
                stopRecordingTimer();
                stopStreamTracks();
                clearRecordedPreview();
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emojiToggleBtn = document.getElementById('lcEmojiToggleBtn');
            const emojiPicker = document.getElementById('lcEmojiPicker');
            const emojiSearch = document.getElementById('lcEmojiSearch');
            const emojiTabs = document.getElementById('lcEmojiTabs');
            const emojiGrid = document.getElementById('lcEmojiGrid');
            const emojiFooter = document.getElementById('lcEmojiFooter');
            const messageTextarea = document.getElementById('lcMessageTextarea');

            if (
                !emojiToggleBtn ||
                !emojiPicker ||
                !emojiSearch ||
                !emojiTabs ||
                !emojiGrid ||
                !emojiFooter ||
                !messageTextarea
            ) {
                return;
            }

            const skinTones = [
                { id: 'default', label: 'Default', swatch: '✋' },
                { id: 'light', label: 'Light', swatch: '🏻' },
                { id: 'medium-light', label: 'Medium light', swatch: '🏼' },
                { id: 'medium', label: 'Medium', swatch: '🏽' },
                { id: 'medium-dark', label: 'Medium dark', swatch: '🏾' },
                { id: 'dark', label: 'Dark', swatch: '🏿' },
            ];

            const emojiCategories = [
                {
                    id: 'smileys',
                    label: 'Smileys',
                    icon: '😊',
                    items: [
                        { char: '😀', keywords: ['grin', 'smile', 'happy'] },
                        { char: '😂', keywords: ['laugh', 'joy', 'funny'] },
                        { char: '🙂', keywords: ['smile', 'nice', 'calm'] },
                        { char: '😍', keywords: ['love', 'heart eyes', 'like'] },
                        { char: '😎', keywords: ['cool', 'sunglasses'] },
                        { char: '🤔', keywords: ['think', 'hmm'] },
                        { char: '😭', keywords: ['cry', 'sad', 'tears'] },
                        { char: '😡', keywords: ['angry', 'mad'] },
                        { char: '🥳', keywords: ['party', 'celebration'] },
                        { char: '🤯', keywords: ['mind blown', 'wow'] },
                    ],
                },
                {
                    id: 'people',
                    label: 'People',
                    icon: '👍',
                    items: [
                        { char: '👍', keywords: ['thumbs up', 'ok', 'approve'], tones: ['👍', '👍🏻', '👍🏼', '👍🏽', '👍🏾', '👍🏿'] },
                        { char: '👎', keywords: ['thumbs down', 'dislike'], tones: ['👎', '👎🏻', '👎🏼', '👎🏽', '👎🏾', '👎🏿'] },
                        { char: '👏', keywords: ['clap', 'applause'], tones: ['👏', '👏🏻', '👏🏼', '👏🏽', '👏🏾', '👏🏿'] },
                        { char: '🙌', keywords: ['celebrate', 'hands up'], tones: ['🙌', '🙌🏻', '🙌🏼', '🙌🏽', '🙌🏾', '🙌🏿'] },
                        { char: '🙏', keywords: ['pray', 'thanks'], tones: ['🙏', '🙏🏻', '🙏🏼', '🙏🏽', '🙏🏾', '🙏🏿'] },
                        { char: '👋', keywords: ['wave', 'hello'], tones: ['👋', '👋🏻', '👋🏼', '👋🏽', '👋🏾', '👋🏿'] },
                        { char: '💪', keywords: ['strong', 'muscle'], tones: ['💪', '💪🏻', '💪🏼', '💪🏽', '💪🏾', '💪🏿'] },
                        { char: '🤝', keywords: ['deal', 'agree', 'handshake'] },
                        { char: '🙋', keywords: ['question', 'raise hand'], tones: ['🙋', '🙋🏻', '🙋🏼', '🙋🏽', '🙋🏾', '🙋🏿'] },
                        { char: '🤝', keywords: ['team', 'together', 'partnership'] },
                    ],
                },
                {
                    id: 'nature',
                    label: 'Nature',
                    icon: '🌿',
                    items: [
                        { char: '🌱', keywords: ['plant', 'grow'] },
                        { char: '🌿', keywords: ['leaf', 'nature'] },
                        { char: '🌸', keywords: ['flower', 'pink'] },
                        { char: '🔥', keywords: ['fire', 'hot'] },
                        { char: '✨', keywords: ['sparkle', 'shine'] },
                        { char: '⚡', keywords: ['energy', 'electric'] },
                        { char: '☀️', keywords: ['sun', 'bright'] },
                        { char: '🌙', keywords: ['moon', 'night'] },
                        { char: '🌧️', keywords: ['rain', 'weather'] },
                        { char: '🌈', keywords: ['rainbow', 'color'] },
                    ],
                },
                {
                    id: 'objects',
                    label: 'Objects',
                    icon: '📎',
                    items: [
                        { char: '📎', keywords: ['clip', 'attach'] },
                        { char: '📩', keywords: ['message', 'mail'] },
                        { char: '📞', keywords: ['call', 'phone'] },
                        { char: '💡', keywords: ['idea', 'tip'] },
                        { char: '💻', keywords: ['computer', 'laptop'] },
                        { char: '🎯', keywords: ['goal', 'target'] },
                        { char: '📝', keywords: ['note', 'write'] },
                        { char: '📌', keywords: ['pin', 'mark'] },
                        { char: '✅', keywords: ['done', 'check'] },
                        { char: '❌', keywords: ['wrong', 'cancel'] },
                    ],
                },
                {
                    id: 'symbols',
                    label: 'Symbols',
                    icon: '❤️',
                    items: [
                        { char: '❤️', keywords: ['heart', 'love'] },
                        { char: '💛', keywords: ['yellow heart', 'love'] },
                        { char: '💚', keywords: ['green heart', 'love'] },
                        { char: '💙', keywords: ['blue heart', 'love'] },
                        { char: '💜', keywords: ['purple heart', 'love'] },
                        { char: '💯', keywords: ['100', 'perfect'] },
                        { char: '🚀', keywords: ['rocket', 'launch'] },
                        { char: '⭐', keywords: ['star', 'favorite'] },
                        { char: '🎉', keywords: ['party', 'celebrate'] },
                        { char: '✔️', keywords: ['check', 'yes'] },
                    ],
                },
            ];

            let activeCategoryId = emojiCategories[0].id;
            let activeSkinToneId = 'default';

            const getSkinToneIndex = () => skinTones.findIndex((tone) => tone.id === activeSkinToneId);

            const getEmojiChar = (emoji) => {
                if (!emoji.tones) {
                    return emoji.char;
                }

                const toneIndex = getSkinToneIndex();
                return emoji.tones[toneIndex] || emoji.tones[0] || emoji.char;
            };

            const getFilteredItems = () => {
                const query = emojiSearch.value.trim().toLowerCase();
                const activeCategory = emojiCategories.find((category) => category.id === activeCategoryId);
                const items = activeCategory ? activeCategory.items : [];

                if (!query) {
                    return items;
                }

                return items.filter((emoji) => {
                    const haystack = [emoji.char, ...(emoji.keywords || [])].join(' ').toLowerCase();
                    return haystack.includes(query);
                });
            };

            const renderEmojiTabs = () => {
                emojiTabs.innerHTML = emojiCategories.map((category) => `
                    <button
                        type="button"
                        class="lc-emoji-tab ${category.id === activeCategoryId ? 'is-active' : ''}"
                        data-category-id="${category.id}"
                    >
                        ${category.icon} ${category.label}
                    </button>
                `).join('');
            };

            const renderEmojiGrid = () => {
                const items = getFilteredItems();

                if (!items.length) {
                    emojiGrid.innerHTML = '<div style="grid-column:1 / -1; padding:8px; font-size:12px; color:#94a3b8;">No emoji found.</div>';
                    return;
                }

                emojiGrid.innerHTML = items.map((emoji) => `
                    <button
                        type="button"
                        class="lc-emoji-btn"
                        data-emoji-value="${getEmojiChar(emoji)}"
                        title="${(emoji.keywords || []).join(', ')}"
                    >
                        ${getEmojiChar(emoji)}
                    </button>
                `).join('');
            };

            const renderEmojiFooter = () => {
                emojiFooter.innerHTML = skinTones.map((tone) => `
                    <button
                        type="button"
                        class="lc-skin-tone-btn ${tone.id === activeSkinToneId ? 'is-active' : ''}"
                        data-skin-tone-id="${tone.id}"
                        title="${tone.label}"
                    >
                        ${tone.swatch}
                    </button>
                `).join('');
            };

            const insertEmojiAtCursor = (emojiValue) => {
                const start = messageTextarea.selectionStart ?? messageTextarea.value.length;
                const end = messageTextarea.selectionEnd ?? messageTextarea.value.length;
                const currentValue = messageTextarea.value;

                messageTextarea.value =
                    currentValue.slice(0, start) +
                    emojiValue +
                    currentValue.slice(end);

                const nextPosition = start + emojiValue.length;
                messageTextarea.focus();
                messageTextarea.setSelectionRange(nextPosition, nextPosition);
                messageTextarea.dispatchEvent(new Event('input', { bubbles: true }));
            };

            const openEmojiPicker = () => {
                emojiPicker.classList.add('is-visible');
                renderEmojiTabs();
                renderEmojiGrid();
                renderEmojiFooter();
                emojiSearch.focus();
            };

            const closeEmojiPicker = () => {
                emojiPicker.classList.remove('is-visible');
            };

            const toggleEmojiPicker = () => {
                if (emojiPicker.classList.contains('is-visible')) {
                    closeEmojiPicker();
                } else {
                    openEmojiPicker();
                }
            };

            emojiToggleBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleEmojiPicker();
            });

            emojiSearch.addEventListener('input', function () {
                renderEmojiGrid();
            });

            emojiTabs.addEventListener('click', function (event) {
                const tabButton = event.target.closest('[data-category-id]');
                if (!tabButton) {
                    return;
                }

                activeCategoryId = tabButton.getAttribute('data-category-id') || activeCategoryId;
                renderEmojiTabs();
                renderEmojiGrid();
            });

            emojiGrid.addEventListener('click', function (event) {
                const emojiButton = event.target.closest('[data-emoji-value]');
                if (!emojiButton) {
                    return;
                }

                insertEmojiAtCursor(emojiButton.getAttribute('data-emoji-value') || '');
                closeEmojiPicker();
            });

            emojiFooter.addEventListener('click', function (event) {
                const toneButton = event.target.closest('[data-skin-tone-id]');
                if (!toneButton) {
                    return;
                }

                activeSkinToneId = toneButton.getAttribute('data-skin-tone-id') || 'default';
                renderEmojiFooter();
                renderEmojiGrid();
            });

            document.addEventListener('click', function (event) {
                if (
                    emojiPicker.classList.contains('is-visible') &&
                    !emojiPicker.contains(event.target) &&
                    !emojiToggleBtn.contains(event.target)
                ) {
                    closeEmojiPicker();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && emojiPicker.classList.contains('is-visible')) {
                    closeEmojiPicker();
                }
            });

            renderEmojiTabs();
            renderEmojiGrid();
            renderEmojiFooter();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectedTagsContainer = document.getElementById('lcConversationTagsSelected');
            const workspaceTagsList = document.getElementById('lcWorkspaceTagsList');
            const tagsSearchInput = document.getElementById('lcTagsSearchInput');
            const saveConversationTagsBtn = document.getElementById('lcSaveConversationTagsBtn');
            const tagsError = document.getElementById('lcTagsError');
            const tagsSaved = document.getElementById('lcTagsSaved');
            const conversationTagsSaveUrl = @json($selectedConversation ? route('inbox.tags.conversation.save', $selectedConversation) : null);
            const csrfToken = '{{ csrf_token() }}';

            if (!selectedTagsContainer || !workspaceTagsList || !saveConversationTagsBtn) {
                return;
            }

            const selectedTagIds = new Set(
                Array.from(selectedTagsContainer.querySelectorAll('[data-tag-id]'))
                    .map((el) => Number(el.getAttribute('data-tag-id')))
                    .filter((id) => !Number.isNaN(id))
            );

            const clearTagsState = () => {
                if (tagsError) {
                    tagsError.style.display = 'none';
                    tagsError.textContent = '';
                }
                if (tagsSaved) {
                    tagsSaved.style.display = 'none';
                }
            };

            const showTagsError = (message) => {
                if (!tagsError) return;
                tagsError.textContent = message;
                tagsError.style.display = 'block';
            };

            const flashTagsSaved = () => {
                if (!tagsSaved) return;
                tagsSaved.style.display = 'block';
                setTimeout(() => {
                    tagsSaved.style.display = 'none';
                }, 1800);
            };

            const renderSelectedConversationTags = () => {
                const selectedButtons = Array.from(
                    workspaceTagsList.querySelectorAll('.lc-workspace-tag-option.is-selected')
                );

                if (!selectedButtons.length) {
                    selectedTagsContainer.innerHTML = '<span id="lcConversationTagsEmpty" style="font-size:11px; color:#94a3b8;">No tags assigned</span>';
                    return;
                }

                selectedTagsContainer.innerHTML = selectedButtons.map((button) => {
                    const tagId = button.getAttribute('data-tag-id') || '';
                    const color = button.getAttribute('data-tag-color') || '#6366f1';
                    const label = button.textContent.trim();
                    return `<span data-tag-id="${tagId}" style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:${color}20; color:#334155; border:1px solid ${color}55; font-size:11px; font-weight:700;">${label}</span>`;
                }).join('');
            };

            const refreshWorkspaceTagButtons = () => {
                workspaceTagsList.querySelectorAll('.lc-workspace-tag-option').forEach((button) => {
                    const tagId = Number(button.getAttribute('data-tag-id'));
                    const color = button.getAttribute('data-tag-color') || '#6366f1';
                    const isSelected = selectedTagIds.has(tagId);

                    button.classList.toggle('is-selected', isSelected);
                    button.style.background = isSelected ? `${color}20` : `${color}10`;
                    button.style.border = `1px solid ${isSelected ? color + '66' : color + '33'}`;
                    button.style.color = '#334155';
                });

                renderSelectedConversationTags();
            };

            const filterWorkspaceTags = () => {
                if (!tagsSearchInput) return;
                const query = tagsSearchInput.value.trim().toLowerCase();
                workspaceTagsList.querySelectorAll('.lc-workspace-tag-option').forEach((button) => {
                    const name = (button.getAttribute('data-tag-name') || '').toLowerCase();
                    button.style.display = !query || name.includes(query) ? 'inline-flex' : 'none';
                });
            };

            workspaceTagsList.addEventListener('click', function (event) {
                const button = event.target.closest('.lc-workspace-tag-option');
                if (!button) return;

                event.preventDefault();
                clearTagsState();

                const tagId = Number(button.getAttribute('data-tag-id'));
                if (Number.isNaN(tagId)) return;

                if (selectedTagIds.has(tagId)) {
                    selectedTagIds.delete(tagId);
                } else {
                    selectedTagIds.add(tagId);
                }

                refreshWorkspaceTagButtons();
            });

            if (tagsSearchInput) {
                tagsSearchInput.addEventListener('input', filterWorkspaceTags);
            }

            saveConversationTagsBtn.addEventListener('click', async function (event) {
                event.preventDefault();
                clearTagsState();

                if (!conversationTagsSaveUrl) {
                    showTagsError('No conversation selected.');
                    return;
                }

                const originalText = saveConversationTagsBtn.textContent;
                saveConversationTagsBtn.disabled = true;
                saveConversationTagsBtn.textContent = 'Saving...';

                try {
                    const formData = new FormData();
                    formData.append('_token', csrfToken);

                    Array.from(selectedTagIds).forEach((id) => {
                        formData.append('tag_ids[]', String(id));
                    });

                    const response = await fetch(conversationTagsSaveUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showTagsError(
                            data?.errors?.tag_ids?.[0] ||
                            data?.message ||
                            'Failed to save tags.'
                        );
                        return;
                    }

                    if (Array.isArray(data.tags)) {
                        selectedTagIds.clear();
                        data.tags.forEach((tag) => {
                            const tagId = Number(tag.id);
                            if (!Number.isNaN(tagId)) {
                                selectedTagIds.add(tagId);
                            }
                        });
                    }

                    refreshWorkspaceTagButtons();
                    flashTagsSaved();
                } catch (error) {
                    showTagsError('Failed to save tags.');
                } finally {
                    saveConversationTagsBtn.disabled = false;
                    saveConversationTagsBtn.textContent = originalText;
                }
            });

            refreshWorkspaceTagButtons();
            filterWorkspaceTags();
        });
    </script>

    <script>
        (function () {
            const workspaceId = @json($workspace?->id);
            const selectedConversationId = @json($selectedConversation?->id);
            const snapshotUrl = @json(route('inbox.realtime.snapshot'));
            let lastSnapshotKey = null;
            let refreshTimer = null;
            let pollTimer = null;
            let isRefreshingPane = false;
            let refreshQueued = false;
            const messageArea = document.getElementById('lcMessageArea');
            const refreshConversationBtn = document.getElementById('lcRefreshConversationBtn');
            const cssEscape = function (value) {
                if (window.CSS && typeof window.CSS.escape === 'function') {
                    return window.CSS.escape(value);
                }

                return String(value).replace(/["\\]/g, '\\$&');
            };

            const isNearBottom = function () {
                if (!messageArea) {
                    return true;
                }

                return messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 120;
            };

            const scrollMessagesToBottom = function () {
                if (messageArea) {
                    messageArea.scrollTop = messageArea.scrollHeight;
                }
            };

            const buildSnapshotKey = function (snapshot) {
                if (selectedConversationId) {
                    return JSON.stringify({
                        conversation_last_message_at: snapshot.conversation_last_message_at || null,
                        conversation_message_count: snapshot.conversation_message_count || null,
                    });
                }

                return JSON.stringify({
                    latest_conversation_timestamp: snapshot.latest_conversation_timestamp || null,
                });
            };

            const schedulePaneRefresh = function (options = {}) {
                clearTimeout(refreshTimer);
                refreshTimer = setTimeout(function () {
                    refreshConversationPane(options);
                }, options.delay || 250);
            };

            const refreshConversationPane = async function (options = {}) {
                if (isRefreshingPane) {
                    refreshQueued = true;
                    return;
                }

                isRefreshingPane = true;
                const shouldStick = options.scrollToBottom || isNearBottom();

                try {
                    const response = await fetch(window.location.href, {
                        headers: {
                            Accept: 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    ['.lc-message-stack', '.lc-conversation-list'].forEach((selector) => {
                        const current = document.querySelector(selector);
                        const next = doc.querySelector(selector);

                        if (current && next) {
                            current.innerHTML = next.innerHTML;
                        }
                    });

                    if (shouldStick) {
                        requestAnimationFrame(scrollMessagesToBottom);
                    }

                    lastSnapshotKey = null;
                } catch (error) {
                    // Keep the current view stable; the next realtime tick can try again.
                } finally {
                    isRefreshingPane = false;

                    if (refreshQueued) {
                        refreshQueued = false;
                        schedulePaneRefresh({ scrollToBottom: true });
                    }
                }
            };

            const startSnapshotPolling = function () {
                if (!snapshotUrl || pollTimer) {
                    return;
                }

                pollTimer = setInterval(async function () {
                    try {
                        const url = new URL(snapshotUrl, window.location.origin);

                        if (selectedConversationId) {
                            url.searchParams.set('conversation_id', selectedConversationId);
                        }

                        const response = await fetch(url.toString(), {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const snapshot = await response.json();
                        const snapshotKey = buildSnapshotKey(snapshot);

                        if (lastSnapshotKey && snapshotKey !== lastSnapshotKey) {
                            schedulePaneRefresh({ scrollToBottom: true });
                            return;
                        }

                        lastSnapshotKey = snapshotKey;
                    } catch (error) {
                        // Websocket remains the primary realtime path; polling is only a quiet fallback.
                    }
                }, 3000);
            };

            startSnapshotPolling();

            if (refreshConversationBtn) {
                refreshConversationBtn.addEventListener('click', function () {
                    schedulePaneRefresh({ scrollToBottom: false, delay: 10 });
                });
            }

            const closeReactionPickers = function (except = null) {
                document.querySelectorAll('.lc-reaction-form.is-open').forEach((form) => {
                    if (form === except) {
                        return;
                    }

                    form.classList.remove('is-open');
                    form.querySelector('.lc-reaction-trigger')?.setAttribute('aria-expanded', 'false');
                });
            };

            const updateReactionTrigger = function (form, emoji) {
                if (!form) {
                    return;
                }

                const trigger = form.querySelector('.lc-reaction-trigger');
                const actionInput = form.querySelector('input[name="action"]');
                const hasReaction = Boolean(emoji);

                if (trigger) {
                    trigger.textContent = hasReaction ? emoji : '♡';
                    trigger.classList.toggle('is-active', hasReaction);
                    trigger.title = hasReaction ? 'Remove reaction' : 'React';
                    trigger.setAttribute('aria-label', hasReaction ? 'Remove reaction' : 'React');
                }

                if (actionInput) {
                    actionInput.value = hasReaction ? 'unreact' : 'react';
                }
            };

            const setReactionError = function (form, message = '') {
                if (!form) {
                    return;
                }

                const error = form.nextElementSibling && form.nextElementSibling.classList.contains('lc-reaction-error')
                    ? form.nextElementSibling
                    : null;

                form.classList.toggle('has-error', Boolean(message));

                if (error) {
                    error.textContent = message;
                }
            };

            const updateMessageReaction = function (payload) {
                const messageId = payload.message_id ? String(payload.message_id) : null;
                const providerMessageId = payload.provider_message_id ? String(payload.provider_message_id) : null;
                const actor = payload.actor || (payload.direction === 'inbound' ? 'customer' : 'agent');
                const action = payload.action || payload.reaction_action || 'react';
                const emoji = action === 'unreact' ? null : (payload.emoji || null);
                const row = messageId
                    ? document.querySelector(`[data-message-id="${cssEscape(messageId)}"]`)
                    : (
                        providerMessageId
                            ? document.querySelector(`[data-provider-message-id="${cssEscape(providerMessageId)}"]`)
                            : null
                    );

                if (!row) {
                    return false;
                }

                const pill = row.querySelector(`[data-reaction-actor="${cssEscape(actor)}"]`);

                if (pill) {
                    if (emoji) {
                        pill.textContent = emoji;
                        pill.style.display = '';
                    } else {
                        pill.textContent = '';
                        pill.style.display = 'none';
                    }
                }

                if (actor === 'agent') {
                    updateReactionTrigger(row.querySelector('.lc-reaction-form'), emoji);
                }

                return true;
            };

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('.lc-reaction-trigger');

                if (trigger) {
                    const form = trigger.closest('.lc-reaction-form');
                    const shouldOpen = !form.classList.contains('is-open');

                    closeReactionPickers(form);
                    form.classList.toggle('is-open', shouldOpen);
                    trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                    setReactionError(form, '');
                    return;
                }

                if (!event.target.closest('.lc-reaction-form')) {
                    closeReactionPickers();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeReactionPickers();
                }
            });

            document.addEventListener('submit', async function (event) {
                const actionForm = event.target.closest('.lc-conversation-action-form');

                if (actionForm) {
                    event.preventDefault();

                    const submitButton = event.submitter || actionForm.querySelector('button[type="submit"]');
                    const originalDisabled = submitButton ? submitButton.disabled : false;

                    if (submitButton) {
                        submitButton.disabled = true;
                    }

                    try {
                        const response = await fetch(actionForm.action, {
                            method: 'POST',
                            body: new FormData(actionForm),
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || data.ok === false) {
                            window.alert(data.error || data.message || 'Action failed.');
                            return;
                        }

                        window.location.assign(data.redirect_url || @json(route('inbox.index')));
                    } catch (error) {
                        window.alert('Action failed.');
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = originalDisabled;
                        }
                    }

                    return;
                }

                const reactionForm = event.target.closest('.lc-reaction-form');

                if (reactionForm) {
                    event.preventDefault();

                    const option = event.submitter?.closest('.lc-reaction-option');
                    const button = option || reactionForm.querySelector('.lc-reaction-option');

                    if (reactionForm.dataset.saving === '1') {
                        return;
                    }

                    if (option && option.value) {
                        const reactionInput = reactionForm.querySelector('input[name="reaction"]');

                        if (reactionInput) {
                            reactionInput.value = option.value;
                        }
                    }

                    reactionForm.dataset.saving = '1';
                    setReactionError(reactionForm, '');

                    if (button) {
                        button.disabled = true;
                    }

                    try {
                        const response = await fetch(reactionForm.action, {
                            method: 'POST',
                            body: new FormData(reactionForm),
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || data.ok === false) {
                            setReactionError(reactionForm, data.error || data.message || 'Reaction failed.');
                            return;
                        }

                        updateMessageReaction(data);
                        closeReactionPickers();
                    } catch (error) {
                        setReactionError(reactionForm, 'Reaction failed.');
                    } finally {
                        reactionForm.dataset.saving = '0';

                        if (button) {
                            button.disabled = false;
                        }
                    }

                    return;
                }

                const messageForm = event.target.closest('.lc-message-form');
                const voiceForm = event.target.closest('.lc-voice-form');
                const form = messageForm || voiceForm;

                if (!form) {
                    return;
                }

                event.preventDefault();

                const button = form.querySelector('button[type="submit"]')
                    || (voiceForm ? document.getElementById('lcRecordingSend') : null);
                const originalText = button ? button.textContent : null;

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Sending...';
                }

                let messageSent = false;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText || 'Send';
                        }

                        window.alert(data.error || data.message || 'Message failed.');
                        return;
                    }

                    messageSent = true;

                    if (messageForm) {
                        form.reset();
                        const replyInput = form.querySelector('input[name="reply_to_message_id"]');
                        const replyPreview = form.querySelector('[data-reply-preview]');

                        if (replyInput) {
                            replyInput.value = '';
                        }

                        if (replyPreview) {
                            replyPreview.remove();
                        }

                        if (window.history && window.history.replaceState) {
                            const cleanUrl = new URL(window.location.href);
                            cleanUrl.searchParams.delete('reply');
                            window.history.replaceState({}, '', cleanUrl.toString());
                        }

                        document.dispatchEvent(new CustomEvent('lc:composer-clear'));
                    }

                    if (voiceForm) {
                        document.dispatchEvent(new CustomEvent('lc:voice-sent'));
                    }

                    schedulePaneRefresh({ scrollToBottom: true, delay: 50 });
                } catch (error) {
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText || 'Send';
                    }

                    window.alert('Message failed.');
                } finally {
                    if (button && !(voiceForm && messageSent)) {
                        button.disabled = false;
                        button.textContent = originalText || 'Send';
                    }
                }
            });

            if (!workspaceId || !window.Echo) {
                return;
            }

            if (window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                window.Echo.connector.pusher.connection.bind('connected', function () {
                    document.documentElement.dataset.lcRealtime = 'connected';
                });

                window.Echo.connector.pusher.connection.bind('disconnected', function () {
                    document.documentElement.dataset.lcRealtime = 'disconnected';
                });

                window.Echo.connector.pusher.connection.bind('error', function () {
                    document.documentElement.dataset.lcRealtime = 'error';
                });
            }

            window.Echo.private(`workspace.${workspaceId}`)
                .listen('.workspace.updated', function (event) {
                    if (!event || event.domain !== 'inbox') {
                        return;
                    }

                    if (event.action === 'instagram_message_reaction_updated') {
                        if (updateMessageReaction(event.payload || {})) {
                            return;
                        }
                    }

                    if (
                        selectedConversationId &&
                        event.payload &&
                        Number(event.payload.conversation_id) !== Number(selectedConversationId)
                    ) {
                        schedulePaneRefresh({ scrollToBottom: false });
                        return;
                    }

                    schedulePaneRefresh({ scrollToBottom: true });
                });
        })();
    </script>
</x-app-layout>
