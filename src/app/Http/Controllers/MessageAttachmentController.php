<?php

namespace App\Http\Controllers;

use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MessageAttachmentController extends Controller
{
    public function show(Request $request, MessageAttachment $attachment): BinaryFileResponse
    {
        $attachment->loadMissing('message.conversation');

        $workspace = $request->user()?->currentWorkspace();
        $attachmentWorkspaceId = $attachment->message?->conversation?->workspace_id;

        abort_unless(
            $workspace && $attachmentWorkspaceId === $workspace->id,
            404
        );

        $meta = is_array($attachment->meta) ? $attachment->meta : [];
        $disk = (string) ($meta['disk'] ?? '');
        $path = (string) ($meta['path'] ?? '');

        abort_unless(in_array($disk, ['local', 'public'], true), 404);

        return $this->fileResponse(
            $disk,
            $path,
            $attachment->file_name,
            $attachment->mime_type
        );
    }

    public function provider(Request $request): BinaryFileResponse
    {
        $path = (string) $request->query('path', '');

        return $this->fileResponse('local', $path);
    }

    private function fileResponse(
        string $disk,
        string $path,
        ?string $fileName = null,
        ?string $mimeType = null
    ): BinaryFileResponse {
        abort_unless($this->isMessageAttachmentPath($path), 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $resolvedMimeType = $mimeType ?: Storage::disk($disk)->mimeType($path);
        $headers = [
            'Cache-Control' => 'no-store, private',
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (is_string($resolvedMimeType) && $resolvedMimeType !== '') {
            $headers['Content-Type'] = $resolvedMimeType;
        }

        $response = response()->file(Storage::disk($disk)->path($path), $headers);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName ?: basename($path)
        );
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    private function isMessageAttachmentPath(string $path): bool
    {
        return str_starts_with($path, 'message-attachments/')
            && ! str_contains($path, '..')
            && preg_match('/\Amessage-attachments\/[A-Za-z0-9._\/-]+\z/', $path) === 1;
    }
}
