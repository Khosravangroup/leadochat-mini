<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\WorkspaceTag;
use App\Models\ConversationParticipant;
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

        if ($workspace) {
            $query = Conversation::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'participants',
                    'messages.attachments',
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
            'viewMode' => $viewMode,
            'showArchived' => $showArchived,
            'showTrashed' => $showTrashed,
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse
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
                        'meta' => [
                            'provider' => 'instagram',
                            'delivery_mode' => 'instagram_service_text',
                            'send_result' => $sendResult,
                        ],
                    ]);

                    $this->updateConversationSnapshot(
                        $conversation,
                        $messageText !== '' ? $messageText : 'New message',
                        $message->sent_at
                    );
                } catch (\Throwable $exception) {
                    $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                        'reply_to_message_id' => $replyToMessageId,
                        'message_type' => 'text',
                        'text_body' => $messageText,
                        'status' => 'failed',
                        'failed_at' => now(),
                        'last_error' => $exception->getMessage(),
                        'meta' => [
                            'provider' => 'instagram',
                            'delivery_mode' => 'instagram_service_text',
                            'send_failed' => true,
                        ],
                    ]);

                    $this->updateConversationSnapshot(
                        $conversation,
                        'Failed to send Instagram message',
                        $message->sent_at
                    );
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
                'meta' => [
                    'is_mock' => true,
                    'delivery_mode' => 'controller_mock_text',
                ],
            ]);

            $this->updateConversationSnapshot(
                $conversation,
                $messageText !== '' ? $messageText : 'New message',
                $message->sent_at
            );

            return redirect()->route('inbox.show', [
                'conversation' => $conversation->id,
                'reply' => null,
            ]);
        }

        $lastPreview = 'Attachment';

        if ($conversation->provider === 'instagram') {
            DB::transaction(function () use ($uploadedFiles, $messageText, $replyToMessageId, $conversation, $selfParticipant, &$lastPreview) {
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    $upload = $this->storeInstagramOutboundUpload($uploadedFile);
                    $messageType = $upload['is_image'] ? 'image' : ($upload['is_video'] ? 'video' : 'file');
                    $attachmentType = $upload['is_image'] ? 'image' : ($upload['is_video'] ? 'video' : 'file');
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
                            'meta' => [
                                'provider' => 'instagram',
                                'multi_upload' => true,
                                'delivery_mode' => 'instagram_service_attachment',
                                'send_result' => $sendResult,
                            ],
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
                    } catch (\Throwable $exception) {
                        $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                            'reply_to_message_id' => $replyToMessageId,
                            'message_type' => $messageType,
                            'caption' => $caption,
                            'status' => 'failed',
                            'failed_at' => now(),
                            'last_error' => $exception->getMessage(),
                            'meta' => [
                                'provider' => 'instagram',
                                'multi_upload' => true,
                                'delivery_mode' => 'instagram_service_attachment',
                                'send_failed' => true,
                            ],
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
                    }

                    $lastPreview = $upload['is_image']
                        ? '📷 Image'
                        : ($upload['is_video'] ? '🎬 Video' : '📎 ' . $upload['file_name']);
                }
            });
        } else {
            DB::transaction(function () use ($uploadedFiles, $messageText, $replyToMessageId, $conversation, $selfParticipant, &$lastPreview) {
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
                    $isImage = str_starts_with($mimeType, 'image/');
                    $isVideo = str_starts_with($mimeType, 'video/');
                    $messageType = $isImage ? 'image' : ($isVideo ? 'video' : 'file');
                    $caption = $index === 0 && $messageText !== '' ? $messageText : null;

                    $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                        'reply_to_message_id' => $replyToMessageId,
                        'message_type' => $messageType,
                        'caption' => $caption,
                        'meta' => [
                            'is_mock' => true,
                            'multi_upload' => true,
                            'delivery_mode' => 'controller_mock_attachment',
                        ],
                    ]);

                    $this->createAttachmentFromUpload($message, $uploadedFile, [
                        'attachment_type' => $isImage ? 'image' : ($isVideo ? 'video' : 'file'),
                        'meta' => [
                            'source' => 'storeMessage',
                        ],
                    ]);

                    $lastPreview = $isImage
                        ? '📷 Image'
                        : ($isVideo ? '🎬 Video' : '📎 ' . $uploadedFile->getClientOriginalName());
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

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
            'reply' => null,
        ]);
    }

    public function storeVoice(Request $request, Conversation $conversation): RedirectResponse
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

        DB::transaction(function () use ($conversation, $selfParticipant, $voiceFile, $durationSeconds, &$message) {
            $message = $this->createOutboundMessage($conversation, $selfParticipant, [
                'message_type' => 'voice',
                'meta' => [
                    'is_mock' => true,
                    'delivery_mode' => 'controller_mock_voice',
                ],
            ]);

            $this->createAttachmentFromUpload($message, $voiceFile, [
                'attachment_type' => 'audio',
                'duration_seconds' => $durationSeconds,
                'meta' => [
                    'source' => 'storeVoice',
                ],
            ]);
        });

        $this->updateConversationSnapshot($conversation, '🎤 Voice message', $message?->sent_at ?? now());

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
        ]);
    }

    public function mockIncoming(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        $this->guardWorkspaceConversationAccess($conversation, $workspace);

        $validated = $request->validate([
            'incoming_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $customerParticipant = $this->resolveCustomerParticipant($conversation);

        $text = trim($validated['incoming_text'] ?: 'Mock incoming message from customer.');

        $message = $this->createInboundMessage($conversation, $customerParticipant, [
            'message_type' => 'text',
            'text_body' => $text,
            'meta' => [
                'is_mock' => true,
                'source' => 'mock_incoming',
            ],
        ]);

        $unreadCount = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->count();

        $this->updateConversationSnapshot($conversation, $text, $message->sent_at, $unreadCount);

        return redirect()->route('inbox.show', [
            'conversation' => $conversation->id,
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

    public function archiveConversation(Request $request, Conversation $conversation): RedirectResponse
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

        return redirect()->route('inbox.index');
    }

    public function unarchiveConversation(Request $request, Conversation $conversation): RedirectResponse
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

        return redirect()->route('inbox.index');
    }

    public function trashConversation(Request $request, Conversation $conversation): RedirectResponse
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

        return redirect()->route('inbox.index');
    }

    public function restoreConversation(Request $request, Conversation $conversation): RedirectResponse
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

        return redirect()->route('inbox.index');
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

    protected function sendInstagramTextMessage(Conversation $conversation, string $text): array
    {
        $connection = app(InstagramService::class)->resolveConnectionFromConversation($conversation);

        if (! $connection) {
            throw new \RuntimeException('Instagram provider connection was not found for this conversation.');
        }

        $recipientId = $this->resolveInstagramRecipientId($conversation);

        if (! $recipientId) {
            throw new \RuntimeException('Instagram recipient id was not found for this conversation.');
        }

        return app(InstagramService::class)->sendMessage($connection, $recipientId, $text, [
            'messaging_type' => 'RESPONSE',
        ]);
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
            'public_url' => Storage::url($storedPath),
            'mime_type' => $mimeType,
            'is_image' => $isImage,
            'is_video' => str_starts_with($mimeType, 'video/'),
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
            'url' => Storage::url($storedPath),
            'thumbnail_url' => $meta['thumbnail_url'] ?? null,
            'mime_type' => $mimeType,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'width' => $meta['width'] ?? $width,
            'height' => $meta['height'] ?? $height,
            'duration_seconds' => $meta['duration_seconds'] ?? null,
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
}