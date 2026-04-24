<?php

namespace App\Http\Controllers;

use App\Events\WorkspaceRealtimeUpdated;
use App\Models\CatalogProduct;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\WorkspaceTag;
use App\Models\ConversationParticipant;
use App\Models\ConversationProductShare;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\WorkspaceDepartment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\Meta\Instagram\InstagramService;

class InboxController extends Controller
{
    public function index(Request $request, ?Conversation $conversation = null): View|RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();
        $viewMode = $request->query('view', 'inbox');

        $showArchived = $viewMode === 'archived';
        $showTrashed = $viewMode === 'trash';

        if ($conversation && $workspace && $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        if ($conversation) {
            if ($conversation->status === 'trashed' && !$showTrashed) {
                return redirect()->route('inbox.index', ['view' => 'trash']);
            }

            if ($conversation->is_archived && !$showArchived && !$showTrashed) {
                return redirect()->route('inbox.index', ['view' => 'archived']);
            }

            if (!$conversation->is_archived && $showArchived) {
                return redirect()->route('inbox.index');
            }

            if ($conversation->status !== 'trashed' && $showTrashed) {
                return redirect()->route('inbox.index');
            }
        }

        if ($conversation && $conversation->status !== 'trashed') {
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('direction', 'inbound')
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                ]);

            $conversation->update([
                'unread_count' => 0,
            ]);

            $conversation->refresh();
        }

        $conversations = collect();
        $workspaceTags = collect();
        $workspaceDepartments = collect();
        $workspaceMembers = collect();
        $catalogProducts = collect();

        if ($workspace) {
            $query = Conversation::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'participants',
                    'messages.attachments',
                    'messages.productShare',
                    'messages.senderParticipant',
                    'messages.replyToMessage',
                    'workspaceTags',
                    'department',
                    'assignedAgent',
                ]);

            if ($showTrashed) {
                $query->where('status', 'trashed');
            } elseif ($showArchived) {
                $query->where('is_archived', true)
                    ->where('status', '!=', 'trashed');
            } else {
                $query->where('is_archived', false)
                    ->where('status', '!=', 'trashed');
            }

            $conversations = $query
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->get()
                ->map(function (Conversation $conversation) {
                    $conversation->unread_count = $conversation->messages
                        ->where('direction', 'inbound')
                        ->whereNull('read_at')
                        ->count();

                    return $conversation;
                });

            $workspaceTags = WorkspaceTag::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $workspaceDepartments = WorkspaceDepartment::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $workspaceMembers = $workspace->members()
                ->orderBy('users.name')
                ->orderBy('users.email')
                ->get();

            $catalogProducts = CatalogProduct::query()
                ->where('is_active', true)
                ->whereHas('catalog', function ($query) use ($workspace) {
                    $query
                        ->where('workspace_id', $workspace->id)
                        ->where('status', 'active');
                })
                ->with('catalog.providerConnection')
                ->orderBy('title')
                ->get();
        }

        $selectedConversation = null;

        if ($conversation) {
            $selectedConversation = $conversations->firstWhere('id', $conversation->id);
        }

        if (!$selectedConversation) {
            $selectedConversation = $conversations->first();
        }

        return view('inbox.index', [
            'workspace' => $workspace,
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'workspaceTags' => $workspaceTags,
            'workspaceDepartments' => $workspaceDepartments,
            'workspaceMembers' => $workspaceMembers,
            'catalogProducts' => $catalogProducts,
            'viewMode' => $viewMode,
            'showArchived' => $showArchived,
            'showTrashed' => $showTrashed,
        ]);
    }

    public function sendCatalogProduct(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        $this->guardWorkspaceConversationAccess($conversation, $workspace);

        $validated = $request->validate([
            'catalog_product_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = CatalogProduct::query()
            ->whereKey((int) $validated['catalog_product_id'])
            ->where('is_active', true)
            ->whereHas('catalog', function ($query) use ($workspace, $conversation) {
                $query
                    ->where('workspace_id', $workspace->id)
                    ->where('status', 'active')
                    ->where(function ($catalogQuery) use ($conversation) {
                        $catalogQuery
                            ->whereNull('provider_connection_id')
                            ->orWhere('provider_connection_id', $conversation->provider_connection_id);
                    });
            })
            ->with('catalog')
            ->firstOrFail();

        $selfParticipant = $this->resolveSelfParticipant($conversation);
        $snapshot = $this->buildProductSnapshot($product);
        $note = trim((string) ($validated['note'] ?? ''));
        $dmText = $this->buildProductMessageText($snapshot, $note);

        $sendResult = null;
        $providerMessageId = null;
        $status = 'sent';
        $failedAt = null;
        $lastError = null;

        if ($conversation->provider === 'instagram') {
            try {
                $sendResult = $this->sendInstagramCatalogProductTemplateMessage($conversation, $snapshot, $note);
                $providerMessageId = (string) (
                    $sendResult['message_id']
                    ?? $sendResult['response']['message_id']
                    ?? $sendResult['mock_response']['message_id']
                    ?? ''
                );
            } catch (\Throwable $exception) {
                report($exception);

                $errorMessage = $this->friendlyInstagramSendError($exception);

                if ($request->expectsJson()) {
                    return response()->json([
                        'ok' => false,
                        'status' => 'failed',
                        'error' => $errorMessage,
                    ], 422);
                }

                return redirect()
                    ->route('inbox.show', ['conversation' => $conversation->id])
                    ->withErrors(['catalog_product' => $errorMessage]);
            }
        }

        $message = null;

        DB::transaction(function () use ($conversation, $selfParticipant, $user, $product, $snapshot, $note, $dmText, $sendResult, $providerMessageId, $status, $failedAt, $lastError, &$message) {
            $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                'message_type' => 'product_card',
                'text_body' => $dmText,
                'provider_message_id' => $providerMessageId !== null && $providerMessageId !== ''
                    ? $providerMessageId
                    : ('product-card-' . now()->timestamp . '-' . random_int(1000, 9999)),
                'status' => $status,
                'failed_at' => $failedAt,
                'last_error' => $lastError,
                'meta' => $this->withAgentMeta($user, [
                    'delivery_mode' => $sendResult['delivery_mode'] ?? ($conversation->provider === 'instagram'
                        ? 'instagram_service_catalog_product_template'
                        : 'controller_catalog_product_mock'),
                    'product_card' => $snapshot,
                    'product_note' => $note !== '' ? $note : null,
                    'send_result' => $sendResult,
                ]),
            ]);

            ConversationProductShare::create([
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'catalog_product_id' => $product->id,
                'agent_id' => $user?->id,
                'product_snapshot' => $snapshot,
            ]);
        });

        $this->updateConversationSnapshot(
            $conversation,
            'Product: ' . $product->title,
            $message?->sent_at ?? now()
        );

        $this->broadcastInboxUpdate($workspace, $conversation, 'catalog_product_sent', $message);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $message?->status !== 'failed',
                'conversation_id' => $conversation->id,
                'message_id' => $message?->id,
                'message_ids' => $message?->id ? [$message->id] : [],
                'status' => $message?->status,
                'error' => $message?->last_error,
            ], $message?->status === 'failed' ? 422 : 200);
        }

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        $this->guardWorkspaceConversationAccess($conversation, $workspace);

        $validated = $request->validate([
            'message_text' => ['nullable', 'string', 'max:5000', 'required_without:attachment_files'],
            'attachment_files' => ['nullable', 'array', 'required_without:message_text'],
            'attachment_files.*' => ['file', 'max:10240'],
            'reply_to_message_id' => ['nullable', 'integer'],
        ]);

        $selfParticipant = $this->resolveSelfParticipant($conversation);
        $replyToMessageId = $this->resolveReplyToMessageId(
            $conversation,
            !empty($validated['reply_to_message_id']) ? (int) $validated['reply_to_message_id'] : null
        );

        $uploadedFiles = $request->file('attachment_files', []);
        $messageText = trim((string) ($validated['message_text'] ?? ''));
        
        if (empty($uploadedFiles)) {
            if ($conversation->provider === 'instagram') {
                try {
                    $sendResult = $this->sendInstagramTextMessage($conversation, $messageText);

                    $providerMessageId = (string) (
                        $sendResult['message_id']
                        ?? $sendResult['mock_response']['message_id']
                        ?? ''
                    );

                    $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                        'reply_to_message_id' => $replyToMessageId,
                        'message_type' => 'text',
                        'text_body' => $messageText,
                        'provider_message_id' => $providerMessageId !== ''
                            ? $providerMessageId
                            : ('instagram-outbound-' . now()->timestamp . '-' . random_int(1000, 9999)),
                        'status' => 'sent',
                        'meta' => $this->withAgentMeta($user, [
                            'provider' => 'instagram',
                            'delivery_mode' => 'instagram_service_text',
                            'send_result' => $sendResult,
                        ]),
                    ]);

                    $this->updateConversationSnapshot(
                        $conversation,
                        $messageText !== '' ? $messageText : 'New message',
                        $message->sent_at
                    );

                    $this->broadcastInboxUpdate($workspace, $conversation, 'instagram_message_sent', $message);
                } catch (\Throwable $exception) {
                    $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                        'reply_to_message_id' => $replyToMessageId,
                        'message_type' => 'text',
                        'text_body' => $messageText,
                        'status' => 'failed',
                        'failed_at' => now(),
                        'last_error' => $exception->getMessage(),
                        'meta' => $this->withAgentMeta($user, [
                            'provider' => 'instagram',
                            'delivery_mode' => 'instagram_service_text',
                            'send_failed' => true,
                        ]),
                    ]);

                    $this->updateConversationSnapshot(
                        $conversation,
                        'Failed to send Instagram message',
                        $message->sent_at
                    );

                    $this->broadcastInboxUpdate($workspace, $conversation, 'instagram_message_failed', $message);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'ok' => $message->status !== 'failed',
                        'conversation_id' => $conversation->id,
                        'message_id' => $message->id,
                        'message_ids' => [$message->id],
                        'status' => $message->status,
                        'error' => $message->last_error,
                    ], $message->status === 'failed' ? 422 : 200);
                }

                return redirect()->route('inbox.show', [
                    'conversation' => $conversation->id,
                    'reply' => null,
                ]);
            }

            $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                'reply_to_message_id' => $replyToMessageId,
                'message_type' => 'text',
                'text_body' => $messageText,
                'meta' => $this->withAgentMeta($user, [
                    'is_mock' => true,
                    'delivery_mode' => 'controller_mock_text',
                ]),
            ]);

            $this->updateConversationSnapshot(
                $conversation,
                $messageText !== '' ? $messageText : 'New message',
                $message->sent_at
            );

            $this->broadcastInboxUpdate($workspace, $conversation, 'message_sent', $message);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'message_ids' => [$message->id],
                    'status' => $message->status,
                    'error' => $message->last_error,
                ]);
            }

            return redirect()->route('inbox.show', [
                'conversation' => $conversation->id,
                'reply' => null,
            ]);
        }

        $lastPreview = 'Attachment';
        $createdMessageIds = [];

        if ($conversation->provider === 'instagram') {
            DB::transaction(function () use ($uploadedFiles, $messageText, $replyToMessageId, $conversation, $selfParticipant, $user, &$lastPreview, &$createdMessageIds) {
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    $upload = $this->storeInstagramOutboundUpload($uploadedFile);
                    $messageType = $upload['message_type'];
                    $attachmentType = $upload['instagram_attachment_type'];
                    $caption = $index === 0 && $messageText !== '' ? $messageText : null;

                    try {
                        $sendResult = $this->sendInstagramAttachmentMessage($conversation, $upload['public_url'], [
                            'attachment_type' => $attachmentType,
                            'messaging_type' => 'RESPONSE',
                        ]);

                        $providerMessageId = (string) (
                            $sendResult['message_id']
                            ?? $sendResult['mock_response']['message_id']
                            ?? ''
                        );

                        $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                            'reply_to_message_id' => $replyToMessageId,
                            'message_type' => $messageType,
                            'caption' => $caption,
                            'provider_message_id' => $providerMessageId !== ''
                                ? $providerMessageId
                                : ('instagram-outbound-attachment-' . now()->timestamp . '-' . random_int(1000, 9999)),
                            'status' => 'sent',
                            'meta' => $this->withAgentMeta($user, [
                                'provider' => 'instagram',
                                'multi_upload' => true,
                                'delivery_mode' => 'instagram_service_attachment',
                                'send_result' => $sendResult,
                            ]),
                        ]);

                        MessageAttachment::create([
                            'message_id' => $message->id,
                            'attachment_type' => $attachmentType,
                            'url' => $upload['public_url'],
                            'thumbnail_url' => null,
                            'mime_type' => $upload['mime_type'],
                            'file_name' => $upload['file_name'],
                            'file_size' => $upload['file_size'],
                            'width' => $upload['width'],
                            'height' => $upload['height'],
                            'duration_seconds' => null,
                            'meta' => [
                                'disk' => 'public',
                                'path' => $upload['stored_path'],
                                'provider' => 'instagram',
                                'source' => 'storeMessage',
                            ],
                        ]);

                        $createdMessageIds[] = $message->id;
                    } catch (\Throwable $exception) {
                        $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                            'reply_to_message_id' => $replyToMessageId,
                            'message_type' => $messageType,
                            'caption' => $caption,
                            'status' => 'failed',
                            'failed_at' => now(),
                            'last_error' => $exception->getMessage(),
                            'meta' => $this->withAgentMeta($user, [
                                'provider' => 'instagram',
                                'multi_upload' => true,
                                'delivery_mode' => 'instagram_service_attachment',
                                'send_failed' => true,
                            ]),
                        ]);

                        MessageAttachment::create([
                            'message_id' => $message->id,
                            'attachment_type' => $attachmentType,
                            'url' => $upload['public_url'],
                            'thumbnail_url' => null,
                            'mime_type' => $upload['mime_type'],
                            'file_name' => $upload['file_name'],
                            'file_size' => $upload['file_size'],
                            'width' => $upload['width'],
                            'height' => $upload['height'],
                            'duration_seconds' => null,
                            'meta' => [
                                'disk' => 'public',
                                'path' => $upload['stored_path'],
                                'provider' => 'instagram',
                                'source' => 'storeMessage',
                                'send_failed' => true,
                            ],
                        ]);

                        $createdMessageIds[] = $message->id;
                    }

                    $lastPreview = match ($messageType) {
                        'image' => '📷 Image',
                        'video' => '🎬 Video',
                        'voice' => '🎤 Voice message',
                        default => '📎 ' . $upload['file_name'],
                    };
                }
            });
        } else {
            DB::transaction(function () use ($uploadedFiles, $messageText, $replyToMessageId, $conversation, $selfParticipant, $user, &$lastPreview, &$createdMessageIds) {
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
                    $isImage = str_starts_with($mimeType, 'image/');
                    $isVideo = str_starts_with($mimeType, 'video/');
                    $isAudio = str_starts_with($mimeType, 'audio/');
                    $messageType = $isImage ? 'image' : ($isVideo ? 'video' : ($isAudio ? 'voice' : 'file'));
                    $caption = $index === 0 && $messageText !== '' ? $messageText : null;

                    $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                        'reply_to_message_id' => $replyToMessageId,
                        'message_type' => $messageType,
                        'caption' => $caption,
                        'meta' => $this->withAgentMeta($user, [
                            'is_mock' => true,
                            'multi_upload' => true,
                            'delivery_mode' => 'controller_mock_attachment',
                        ]),
                    ]);

                    $this->createAttachmentFromUpload($message, $uploadedFile, [
                        'attachment_type' => $isImage ? 'image' : ($isVideo ? 'video' : ($isAudio ? 'audio' : 'file')),
                        'meta' => [
                            'source' => 'storeMessage',
                        ],
                    ]);

                    $createdMessageIds[] = $message->id;

                    $lastPreview = match ($messageType) {
                        'image' => '📷 Image',
                        'video' => '🎬 Video',
                        'voice' => '🎤 Voice message',
                        default => '📎 ' . $uploadedFile->getClientOriginalName(),
                    };
                }
            });
        }

        $this->updateConversationSnapshot(
            $conversation,
            count($uploadedFiles) > 1
                ? ('📎 ' . count($uploadedFiles) . ' attachments')
                : $lastPreview,
            now()
        );

        $this->broadcastInboxUpdate($workspace, $conversation, $conversation->provider === 'instagram' ? 'instagram_message_sent' : 'message_sent', [
            'message_ids' => $createdMessageIds,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'conversation_id' => $conversation->id,
                'message_ids' => $createdMessageIds,
                'status' => 'sent',
            ]);
        }

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
            'reply' => null,
        ]);
    }

    public function storeVoice(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        $this->guardWorkspaceConversationAccess($conversation, $workspace);

        $validated = $request->validate([
            'voice_file' => ['required', 'file', 'mimetypes:audio/webm,audio/ogg,audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/mp4,audio/aac,audio/flac,audio/x-m4a', 'max:20480'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0', 'max:3600'],
        ]);

        $selfParticipant = $this->resolveSelfParticipant($conversation);

        $voiceFile = $request->file('voice_file');
        $durationSeconds = isset($validated['duration_seconds'])
            ? (int) round((float) $validated['duration_seconds'])
            : null;

        $message = null;

        if ($conversation->provider === 'instagram') {
            $upload = $this->storeInstagramOutboundUpload($voiceFile);

            try {
                $sendResult = $this->sendInstagramAttachmentMessage($conversation, $upload['public_url'], [
                    'attachment_type' => 'audio',
                    'messaging_type' => 'RESPONSE',
                ]);
                $status = 'sent';
                $failedAt = null;
                $lastError = null;
            } catch (\Throwable $exception) {
                $sendResult = [
                    'send_failed' => true,
                    'error' => $exception->getMessage(),
                ];
                $status = 'failed';
                $failedAt = now();
                $lastError = $exception->getMessage();
            }

            $providerMessageId = (string) (
                $sendResult['message_id']
                ?? $sendResult['response']['message_id']
                ?? $sendResult['mock_response']['message_id']
                ?? ''
            );

            DB::transaction(function () use ($conversation, $selfParticipant, $user, $durationSeconds, $upload, $sendResult, $status, $failedAt, $lastError, $providerMessageId, &$message) {
                $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                    'message_type' => 'voice',
                    'provider_message_id' => $providerMessageId !== ''
                        ? $providerMessageId
                        : ('instagram-outbound-voice-' . now()->timestamp . '-' . random_int(1000, 9999)),
                    'status' => $status,
                    'failed_at' => $failedAt,
                    'last_error' => $lastError,
                    'meta' => $this->withAgentMeta($user, [
                        'provider' => 'instagram',
                        'delivery_mode' => 'instagram_service_audio',
                        'send_result' => $sendResult,
                    ]),
                ]);

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'attachment_type' => 'audio',
                    'url' => $upload['public_url'],
                    'thumbnail_url' => null,
                    'mime_type' => $upload['mime_type'],
                    'file_name' => $upload['file_name'],
                    'file_size' => $upload['file_size'],
                    'width' => null,
                    'height' => null,
                    'duration_seconds' => $durationSeconds,
                    'sort_order' => 0,
                    'meta' => [
                        'disk' => 'public',
                        'path' => $upload['stored_path'],
                        'provider' => 'instagram',
                        'source' => 'storeVoice',
                    ],
                ]);
            });
        } else {
            DB::transaction(function () use ($conversation, $selfParticipant, $user, $voiceFile, $durationSeconds, &$message) {
                $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                    'message_type' => 'voice',
                    'meta' => $this->withAgentMeta($user, [
                        'is_mock' => true,
                        'delivery_mode' => 'controller_mock_voice',
                    ]),
                ]);

                $this->createAttachmentFromUpload($message, $voiceFile, [
                    'attachment_type' => 'audio',
                    'duration_seconds' => $durationSeconds,
                    'meta' => [
                        'source' => 'storeVoice',
                    ],
                ]);
            });
        }

        $this->updateConversationSnapshot($conversation, '🎤 Voice message', $message?->sent_at ?? now());
        $this->broadcastInboxUpdate($workspace, $conversation, $conversation->provider === 'instagram' ? 'instagram_message_sent' : 'message_sent', $message);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $message?->status !== 'failed',
                'conversation_id' => $conversation->id,
                'message_id' => $message?->id,
                'message_ids' => $message?->id ? [$message->id] : [],
                'status' => $message?->status,
                'error' => $message?->last_error,
            ], $message?->status === 'failed' ? 422 : 200);
        }

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
        ]);
    }

    public function storeReaction(Request $request, Conversation $conversation, Message $message): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        $this->guardWorkspaceConversationAccess($conversation, $workspace);

        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        if ($message->direction !== 'inbound') {
            abort(422, 'Only inbound Instagram messages can be reacted to from the inbox.');
        }

        $validated = $request->validate([
            'reaction' => ['nullable', 'string', 'in:love'],
            'action' => ['nullable', 'string', 'in:react,unreact'],
        ]);

        $reaction = $validated['reaction'] ?? 'love';
        $action = $validated['action'] ?? 'react';
        $emoji = $this->emojiForReaction($reaction);
        $sendResult = null;
        $status = 'local';
        $error = null;

        if ($conversation->provider === 'instagram') {
            try {
                $connection = app(InstagramService::class)->resolveConnectionFromConversation($conversation);
                $recipientId = $this->resolveInstagramRecipientId($conversation);

                if (! $connection) {
                    throw new \RuntimeException('Instagram provider connection was not found for this conversation.');
                }

                if (! $recipientId) {
                    throw new \RuntimeException('Instagram recipient id was not found for this conversation.');
                }

                if (blank($message->provider_message_id)) {
                    throw new \RuntimeException('Instagram provider message id was not found for this message.');
                }

                $sendResult = app(InstagramService::class)->sendReaction(
                    $connection,
                    $recipientId,
                    (string) $message->provider_message_id,
                    $reaction,
                    $action
                );
                $status = 'sent';
            } catch (\Throwable $exception) {
                $status = 'failed';
                $error = $exception->getMessage();
                report($exception);
            }
        }

        $this->applyMessageReaction($message, 'agent_reaction', [
            'action' => $action,
            'reaction' => $reaction,
            'emoji' => $emoji,
            'actor' => 'agent',
            'status' => $status,
            'error' => $error,
            'send_result' => $sendResult,
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->broadcastInboxUpdate($workspace, $conversation, 'instagram_message_reaction_updated', [
            'message_id' => $message->id,
            'provider_message_id' => $message->provider_message_id,
            'reaction' => $action === 'unreact' ? null : $reaction,
            'emoji' => $action === 'unreact' ? null : $emoji,
            'actor' => 'agent',
            'status' => $status,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $status !== 'failed',
                'status' => $status,
                'error' => $error,
                'reaction' => $action === 'unreact' ? null : $reaction,
                'emoji' => $action === 'unreact' ? null : $emoji,
                'action' => $action,
                'message_id' => $message->id,
                'provider_message_id' => $message->provider_message_id,
                'actor' => 'agent',
            ], $status === 'failed' ? 422 : 200);
        }

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
        ]);
    }

    public function realtimeSnapshot(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace();

        if (! $workspace) {
            abort(404);
        }

        $conversationId = $request->integer('conversation_id') ?: null;
        $conversation = null;

        if ($conversationId) {
            $conversation = Conversation::query()
                ->where('workspace_id', $workspace->id)
                ->whereKey($conversationId)
                ->first();
        }

        $latestConversationTimestamp = Conversation::query()
            ->where('workspace_id', $workspace->id)
            ->max('updated_at');

        $latestMessageTimestamp = Message::query()
            ->whereHas('conversation', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->max('updated_at');

        return response()->json([
            'ok' => true,
            'workspace_id' => $workspace->id,
            'conversation_id' => $conversation?->id,
            'conversation_updated_at' => optional($conversation?->updated_at)->toIso8601String(),
            'conversation_last_message_at' => optional($conversation?->last_message_at)->toIso8601String(),
            'conversation_message_count' => $conversation
                ? Message::query()->where('conversation_id', $conversation->id)->count()
                : null,
            'latest_conversation_timestamp' => $latestConversationTimestamp,
            'latest_message_timestamp' => $latestMessageTimestamp,
        ]);
    }

    public function saveNote(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $conversation->update([
            'internal_note' => trim((string) ($validated['internal_note'] ?? '')) ?: null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'internal_note' => $conversation->internal_note,
            ]);
        }

        $routeParams = ['conversation' => $conversation->id];

        if ($request->filled('view')) {
            $routeParams['view'] = $request->string('view')->toString();
        }

        return redirect()->route('inbox.show', $routeParams);
    }

    public function listWorkspaceTags(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $tags = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'is_active', 'sort_order']);

        return response()->json([
            'tags' => $tags,
        ]);
    }

    public function createWorkspaceTag(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $name = trim($validated['name']);

        $existing = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tag already exists.',
                'tag' => $existing,
            ], 422);
        }

        $nextSortOrder = (int) WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $tag = WorkspaceTag::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'color' => $validated['color'],
            'is_active' => true,
            'sort_order' => $nextSortOrder + 1,
        ]);

        return response()->json([
            'ok' => true,
            'tag' => $tag,
        ]);
    }

    public function saveConversationTags(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ]);

        $tagIds = collect($validated['tag_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedTagIds = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $conversation->workspaceTags()->sync($allowedTagIds);

        $assignedTags = $conversation->workspaceTags()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'ok' => true,
            'tags' => $assignedTags->map(fn (WorkspaceTag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values()->all(),
        ]);
    }

    public function saveConversationDepartment(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'department_id' => ['nullable', 'integer'],
        ]);

        $departmentId = isset($validated['department_id'])
            ? (int) $validated['department_id']
            : null;

        $department = null;

        if ($departmentId) {
            $department = WorkspaceDepartment::query()
                ->where('workspace_id', $workspace->id)
                ->where('id', $departmentId)
                ->first();
        }

        $conversation->update([
            'department_id' => $department?->id,
        ]);

        return response()->json([
            'ok' => true,
            'department' => $department ? [
                'id' => $department->id,
                'name' => $department->name,
                'color' => $department->color,
            ] : null,
        ]);
    }

    public function saveConversationAgent(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'assigned_user_id' => ['nullable', 'integer'],
        ]);

        $assignedUserId = isset($validated['assigned_user_id'])
            ? (int) $validated['assigned_user_id']
            : null;

        $assignedAgent = null;

        if ($assignedUserId) {
            $assignedAgent = $workspace->members()
                ->where('users.id', $assignedUserId)
                ->first();
        }

        $conversation->update([
            'assigned_user_id' => $assignedAgent?->id,
        ]);

        return response()->json([
            'ok' => true,
            'agent' => $assignedAgent ? [
                'id' => $assignedAgent->id,
                'name' => $assignedAgent->name,
                'email' => $assignedAgent->email,
                'role' => $assignedAgent->pivot->role ?? 'member',
            ] : null,
        ]);
    }

    public function archiveConversation(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id || $conversation->status === 'trashed') {
            abort(404);
        }

        $conversation->update([
            'is_archived' => true,
            'status' => 'archived',
        ]);

        return $this->respondWithConversationAction($request, route('inbox.index'), 'Conversation archived.');
    }

    public function unarchiveConversation(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id || $conversation->status === 'trashed') {
            abort(404);
        }

        $conversation->update([
            'is_archived' => false,
            'status' => 'active',
        ]);

        return $this->respondWithConversationAction($request, route('inbox.index'), 'Conversation unarchived.');
    }

    public function trashConversation(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $conversation->update([
            'status' => 'trashed',
            'is_archived' => false,
        ]);

        return $this->respondWithConversationAction($request, route('inbox.index'), 'Conversation moved to trash.');
    }

    public function restoreConversation(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $conversation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $conversation->update([
            'status' => 'active',
            'is_archived' => false,
        ]);

        return $this->respondWithConversationAction($request, route('inbox.index'), 'Conversation restored.');
    }

    protected function respondWithConversationAction(
        Request $request,
        string $redirectUrl,
        string $message
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('status', $message);
    }

    protected function guardWorkspaceConversationAccess(?Conversation $conversation, ?object $workspace, bool $allowArchived = false): void
    {
        if (
            !$workspace ||
            !$conversation ||
            $conversation->workspace_id !== $workspace->id ||
            (!$allowArchived && $conversation->is_archived) ||
            $conversation->status === 'trashed'
        ) {
            abort(404);
        }
    }

    protected function resolveSelfParticipant(Conversation $conversation): ?ConversationParticipant
    {
        return $conversation->participants()
            ->where('is_self', true)
            ->first();
    }

    protected function resolveCustomerParticipant(Conversation $conversation): ?ConversationParticipant
    {
        return $conversation->participants()
            ->where('is_self', false)
            ->first();
    }

    protected function resolveInstagramRecipientId(Conversation $conversation): ?string
    {
        $customerParticipant = $this->resolveCustomerParticipant($conversation);
        $providerUserId = (string) ($customerParticipant?->provider_user_id ?? '');

        return $providerUserId !== '' ? $providerUserId : null;
    }

    protected function withAgentMeta(?object $user, array $meta): array
    {
        if (! $user?->id) {
            return $meta;
        }

        $agentName = trim((string) ($user->name ?? ''));
        $agentEmail = trim((string) ($user->email ?? ''));

        if ($agentName === '' && $agentEmail !== '') {
            $agentName = strstr($agentEmail, '@', true) ?: $agentEmail;
        }

        $meta['agent_user'] = [
            'id' => $user->id,
            'name' => $agentName !== '' ? $agentName : 'Agent',
            'email' => $agentEmail !== '' ? $agentEmail : null,
            'avatar_url' => $user->avatar_url ?? null,
        ];

        return $meta;
    }

    protected function sendInstagramTextMessage(Conversation $conversation, string $text): array
    {
        return $this->sendInstagramTextMessageWithOptions($conversation, $text, [
            'messaging_type' => 'RESPONSE',
        ]);
    }

    protected function sendInstagramCatalogProductTextMessage(Conversation $conversation, string $text): array
    {
        try {
            $result = $this->sendInstagramTextMessageWithOptions($conversation, $text, [
                'messaging_type' => 'RESPONSE',
            ]);

            $result['delivery_mode'] = 'instagram_service_catalog_product_text';

            return $result;
        } catch (\Throwable $exception) {
            if (! $this->isInstagramAllowedWindowError($exception)) {
                throw $exception;
            }

            try {
                $result = $this->sendInstagramTextMessageWithOptions($conversation, $text, [
                    'messaging_type' => 'MESSAGE_TAG',
                    'tag' => 'HUMAN_AGENT',
                ]);

                $result['delivery_mode'] = 'instagram_service_catalog_product_text_human_agent';
                $result['fallback_reason'] = 'outside_standard_reply_window';
                $result['fallback_from'] = 'RESPONSE';
                $result['original_error'] = $exception->getMessage();

                return $result;
            } catch (\Throwable $fallbackException) {
                throw new \RuntimeException(
                    'Instagram rejected this product DM because the customer reply window is closed. I also tried the Human Agent fallback, but Meta rejected it too. Ask the customer to send a new DM, then try again.',
                    0,
                    $fallbackException
                );
            }
        }
    }

    protected function sendInstagramCatalogProductTemplateMessage(Conversation $conversation, array $snapshot, string $note = ''): array
    {
        $elements = [
            $this->buildInstagramProductTemplateElement($snapshot, $note),
        ];

        try {
            $result = $this->sendInstagramGenericTemplateWithOptions($conversation, $elements, [
                'messaging_type' => 'RESPONSE',
            ]);

            $result['delivery_mode'] = 'instagram_service_catalog_product_template';

            return $result;
        } catch (\Throwable $exception) {
            if (! $this->isInstagramAllowedWindowError($exception)) {
                throw $exception;
            }

            try {
                $result = $this->sendInstagramGenericTemplateWithOptions($conversation, $elements, [
                    'messaging_type' => 'MESSAGE_TAG',
                    'tag' => 'HUMAN_AGENT',
                ]);

                $result['delivery_mode'] = 'instagram_service_catalog_product_template_human_agent';
                $result['fallback_reason'] = 'outside_standard_reply_window';
                $result['fallback_from'] = 'RESPONSE';
                $result['original_error'] = $exception->getMessage();

                return $result;
            } catch (\Throwable $fallbackException) {
                throw new \RuntimeException(
                    'Instagram rejected this product card because the customer reply window is closed. I also tried the Human Agent fallback, but Meta rejected it too. Ask the customer to send a new DM, then try again.',
                    0,
                    $fallbackException
                );
            }
        }
    }

    protected function sendInstagramTextMessageWithOptions(Conversation $conversation, string $text, array $options): array
    {
        $connection = app(InstagramService::class)->resolveConnectionFromConversation($conversation);

        if (! $connection) {
            throw new \RuntimeException('Instagram provider connection was not found for this conversation.');
        }

        $recipientId = $this->resolveInstagramRecipientId($conversation);

        if (! $recipientId) {
            throw new \RuntimeException('Instagram recipient id was not found for this conversation.');
        }

        return app(InstagramService::class)->sendMessage($connection, $recipientId, $text, $options);
    }

    protected function sendInstagramGenericTemplateWithOptions(Conversation $conversation, array $elements, array $options): array
    {
        $connection = app(InstagramService::class)->resolveConnectionFromConversation($conversation);

        if (! $connection) {
            throw new \RuntimeException('Instagram provider connection was not found for this conversation.');
        }

        $recipientId = $this->resolveInstagramRecipientId($conversation);

        if (! $recipientId) {
            throw new \RuntimeException('Instagram recipient id was not found for this conversation.');
        }

        return app(InstagramService::class)->sendGenericTemplate($connection, $recipientId, $elements, $options);
    }

    protected function isInstagramAllowedWindowError(\Throwable $exception): bool
    {
        $message = $exception->getMessage();
        $lowerMessage = mb_strtolower($message);

        return str_contains($message, '2534022')
            || str_contains($lowerMessage, 'outside of allowed window')
            || str_contains($lowerMessage, 'outside the allowed window')
            || str_contains($lowerMessage, 'reply window is closed');
    }

    protected function friendlyInstagramSendError(\Throwable $exception): string
    {
        $message = $exception->getMessage();
        $lowerMessage = mb_strtolower($message);

        if ($this->isInstagramAllowedWindowError($exception) || str_contains($lowerMessage, 'human agent')) {
            return 'Instagram did not allow this DM because the customer reply window is closed. Ask the customer to send a new DM, then send the product again.';
        }

        return 'Instagram did not accept this product DM. Please try again, and if it repeats, check the Instagram connection permissions.';
    }

    protected function sendInstagramAttachmentMessage(
        Conversation $conversation,
        string $attachmentUrl,
        array $options = []
    ): array {
        $connection = app(InstagramService::class)->resolveConnectionFromConversation($conversation);

        if (! $connection) {
            throw new \RuntimeException('Instagram provider connection was not found for this conversation.');
        }

        $recipientId = $this->resolveInstagramRecipientId($conversation);

        if (! $recipientId) {
            throw new \RuntimeException('Instagram recipient id was not found for this conversation.');
        }

        return app(InstagramService::class)->sendAttachment($connection, $recipientId, $attachmentUrl, $options);
    }

    protected function storeInstagramOutboundUpload(UploadedFile $uploadedFile): array
    {
        $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
        $isImage = str_starts_with($mimeType, 'image/');
        $isVideo = str_starts_with($mimeType, 'video/');
        $isAudio = str_starts_with($mimeType, 'audio/');
        $storedPath = $uploadedFile->store('message-attachments', 'public');

        $width = null;
        $height = null;

        if ($isImage) {
            $imageInfo = @getimagesize($uploadedFile->getRealPath());
            if (is_array($imageInfo)) {
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
            }
        }

        return [
            'stored_path' => $storedPath,
            'public_url' => $this->publicStorageUrl($storedPath),
            'mime_type' => $mimeType,
            'message_type' => $isImage ? 'image' : ($isVideo ? 'video' : ($isAudio ? 'voice' : 'file')),
            'instagram_attachment_type' => $isImage ? 'image' : ($isVideo ? 'video' : ($isAudio ? 'audio' : 'file')),
            'is_image' => $isImage,
            'is_video' => $isVideo,
            'is_audio' => $isAudio,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }
    
    protected function resolveReplyToMessageId(Conversation $conversation, ?int $candidateReplyId): ?int
    {
        if (!$candidateReplyId) {
            return null;
        }

        $replyMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', $candidateReplyId)
            ->first();

        return $replyMessage?->id;
    }

    protected function buildProductSnapshot(CatalogProduct $product): array
    {
        return [
            'id' => $product->id,
            'catalog_id' => $product->catalog_id,
            'catalog_name' => $product->catalog?->name,
            'sku' => $product->sku,
            'title' => $product->title,
            'description' => $product->description,
            'price' => $product->price !== null ? (float) $product->price : null,
            'currency' => strtoupper((string) $product->currency),
            'image_url' => $product->image_url,
            'product_url' => $product->product_url,
            'availability' => $product->availability,
        ];
    }

    protected function buildProductMessageText(array $snapshot, string $note = ''): string
    {
        $lines = [
            'Product recommendation',
            (string) ($snapshot['title'] ?? 'Product'),
        ];

        if (($snapshot['price'] ?? null) !== null) {
            $lines[] = strtoupper((string) ($snapshot['currency'] ?? 'USD')) . ' ' . number_format((float) $snapshot['price'], 2);
        }

        if (! blank($snapshot['description'] ?? null)) {
            $lines[] = (string) $snapshot['description'];
        }

        if (! blank($snapshot['product_url'] ?? null)) {
            $lines[] = 'View product: ' . $snapshot['product_url'];
        }

        if ($note !== '') {
            $lines[] = 'Note: ' . $note;
        }

        return implode("\n", array_filter($lines, fn ($line) => trim((string) $line) !== ''));
    }

    protected function buildInstagramProductTemplateElement(array $snapshot, string $note = ''): array
    {
        $title = $this->compactTemplateText((string) ($snapshot['title'] ?? 'Product'), 80);
        $description = trim((string) ($snapshot['description'] ?? ''));
        $price = null;

        if (($snapshot['price'] ?? null) !== null) {
            $price = strtoupper((string) ($snapshot['currency'] ?? 'USD')) . ' ' . number_format((float) $snapshot['price'], 2);
        }

        $subtitleParts = array_filter([
            $price,
            $note !== '' ? 'Note: ' . $note : null,
            $description !== '' ? $description : null,
        ], fn ($value) => trim((string) $value) !== '');

        $element = [
            'title' => $title !== '' ? $title : 'Product',
            'subtitle' => $this->compactTemplateText(implode(' - ', $subtitleParts), 80),
        ];

        $imageUrl = trim((string) ($snapshot['image_url'] ?? ''));
        if ($imageUrl !== '') {
            $element['image_url'] = $imageUrl;
        }

        $productUrl = trim((string) ($snapshot['product_url'] ?? ''));
        if ($productUrl !== '') {
            $element['default_action'] = [
                'type' => 'web_url',
                'url' => $productUrl,
            ];
            $element['buttons'] = [
                [
                    'type' => 'web_url',
                    'url' => $productUrl,
                    'title' => 'View product',
                ],
            ];
        }

        return $element;
    }

    protected function compactTemplateText(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?: '');

        if ($value === '' || mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(1, $limit - 3))) . '...';
    }

    protected function createOutboundMessage(
        Conversation $conversation,
        ?ConversationParticipant $senderParticipant,
        array $attributes
    ): Message {
        $sentAt = $attributes['sent_at'] ?? now();
        $providerMessageId = $attributes['provider_message_id']
            ?? ('mock-msg-' . now()->timestamp . '-' . random_int(1000, 9999));

        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_participant_id' => $senderParticipant?->id,
            'reply_to_message_id' => $attributes['reply_to_message_id'] ?? null,
            'provider' => $conversation->provider,
            'provider_message_id' => $providerMessageId,
            'provider_reply_to_message_id' => $attributes['provider_reply_to_message_id'] ?? null,
            'direction' => 'outbound',
            'message_type' => $attributes['message_type'] ?? 'text',
            'text_body' => $attributes['text_body'] ?? null,
            'caption' => $attributes['caption'] ?? null,
            'status' => $attributes['status'] ?? 'sent',
            'sent_at' => $sentAt,
            'received_at' => $attributes['received_at'] ?? $sentAt,
            'read_at' => $attributes['read_at'] ?? null,
            'failed_at' => $attributes['failed_at'] ?? null,
            'last_error' => $attributes['last_error'] ?? null,
            'meta' => $attributes['meta'] ?? ['is_mock' => true],
        ]);
    }

    protected function createInboundMessage(
        Conversation $conversation,
        ?ConversationParticipant $senderParticipant,
        array $attributes
    ): Message {
        $sentAt = $attributes['sent_at'] ?? now();
        $providerMessageId = $attributes['provider_message_id']
            ?? ('mock-incoming-' . now()->timestamp . '-' . random_int(1000, 9999));

        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_participant_id' => $senderParticipant?->id,
            'reply_to_message_id' => $attributes['reply_to_message_id'] ?? null,
            'provider' => $conversation->provider,
            'provider_message_id' => $providerMessageId,
            'provider_reply_to_message_id' => $attributes['provider_reply_to_message_id'] ?? null,
            'direction' => 'inbound',
            'message_type' => $attributes['message_type'] ?? 'text',
            'text_body' => $attributes['text_body'] ?? null,
            'caption' => $attributes['caption'] ?? null,
            'status' => $attributes['status'] ?? 'received',
            'sent_at' => $sentAt,
            'received_at' => $attributes['received_at'] ?? $sentAt,
            'read_at' => $attributes['read_at'] ?? null,
            'failed_at' => $attributes['failed_at'] ?? null,
            'last_error' => $attributes['last_error'] ?? null,
            'meta' => $attributes['meta'] ?? ['is_mock' => true],
        ]);
    }

    protected function createAttachmentFromUpload(
        Message $message,
        UploadedFile $uploadedFile,
        array $meta = []
    ): MessageAttachment {
        $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
        $isImage = str_starts_with($mimeType, 'image/');
        $storedPath = $uploadedFile->store('message-attachments', 'public');

        $width = null;
        $height = null;

        if ($isImage) {
            $imageInfo = @getimagesize($uploadedFile->getRealPath());
            if (is_array($imageInfo)) {
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
            }
        }

        return MessageAttachment::create([
            'message_id' => $message->id,
            'attachment_type' => $meta['attachment_type'] ?? ($isImage ? 'image' : 'file'),
            'url' => $this->publicStorageUrl($storedPath),
            'thumbnail_url' => $meta['thumbnail_url'] ?? null,
            'mime_type' => $mimeType,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'width' => $meta['width'] ?? $width,
            'height' => $meta['height'] ?? $height,
            'duration_seconds' => $meta['duration_seconds'] ?? null,
            'sort_order' => $meta['sort_order'] ?? 0,
            'meta' => array_merge([
                'disk' => 'public',
                'path' => $storedPath,
                'is_mock' => true,
            ], $meta['meta'] ?? []),
        ]);
    }

    protected function updateConversationSnapshot(Conversation $conversation, string $preview, Carbon|string|null $messageTime = null, ?int $unreadCount = null): void
    {
        $resolvedMessageTime = $messageTime instanceof Carbon
            ? $messageTime
            : ($messageTime ? Carbon::parse($messageTime) : now());

        $payload = [
            'last_message_preview' => $preview,
            'last_message_at' => $resolvedMessageTime,
        ];

        if ($unreadCount !== null) {
            $payload['unread_count'] = $unreadCount;
        }

        $conversation->update($payload);
    }

    protected function publicStorageUrl(string $storedPath): string
    {
        return url(Storage::url($storedPath));
    }

    protected function applyMessageReaction(Message $message, string $metaKey, array $reaction): void
    {
        $meta = is_array($message->meta) ? $message->meta : [];
        $history = is_array($meta['reaction_history'] ?? null) ? $meta['reaction_history'] : [];
        $history[] = $reaction;

        if (($reaction['status'] ?? null) === 'failed') {
            unset($meta[$metaKey]);
            $meta[$metaKey . '_error'] = $reaction;
        } elseif (($reaction['action'] ?? 'react') === 'unreact') {
            unset($meta[$metaKey]);
            unset($meta[$metaKey . '_error']);
        } else {
            $meta[$metaKey] = $reaction;
            unset($meta[$metaKey . '_error']);
        }

        $meta['reaction_history'] = array_slice($history, -25);
        $message->meta = $meta;
        $message->save();
    }

    protected function emojiForReaction(string $reaction): string
    {
        return match ($reaction) {
            'love' => '❤️',
            default => '❤️',
        };
    }

    protected function broadcastInboxUpdate(?object $workspace, Conversation $conversation, string $action, mixed $messageOrPayload = null): void
    {
        if (! $workspace?->id) {
            return;
        }

        $payload = [
            'conversation_id' => $conversation->id,
            'provider' => $conversation->provider,
        ];

        if ($messageOrPayload instanceof Message) {
            $payload['message_id'] = $messageOrPayload->id;
            $payload['direction'] = $messageOrPayload->direction;
            $payload['message_type'] = $messageOrPayload->message_type;
            $payload['status'] = $messageOrPayload->status;
        } elseif (is_array($messageOrPayload)) {
            $payload = array_merge($payload, $messageOrPayload);
        }

        try {
            event(new WorkspaceRealtimeUpdated((int) $workspace->id, 'inbox', $action, $payload));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
