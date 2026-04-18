<?php

namespace App\Http\Controllers;

use App\Services\Meta\Instagram\InstagramLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class InstagramConnectController extends Controller
{
    public function redirect(Request $request, InstagramLoginService $instagramLoginService): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        abort_if(! $workspace, 404, 'No active workspace found.');

        return redirect()->away(
            $instagramLoginService->buildAuthorizationUrl($workspace->id)
        );
    }

    public function callback(Request $request, InstagramLoginService $instagramLoginService): RedirectResponse
    {
        $result = $instagramLoginService->handleCallback($request);

        $status = (string) Arr::get($result, 'status', 'unknown');
        $isSuccess = in_array($status, ['connected', 'success', 'callback_received'], true)
            && !in_array($status, ['invalid_state', 'failed', 'error'], true);

        $message = match ($status) {
            'connected', 'success', 'callback_received' => 'Instagram connection completed successfully.',
            'invalid_state' => 'Instagram connection failed because the login state was invalid or expired. Please try again.',
            'failed', 'error' => 'Instagram connection failed. Please try again.',
            default => 'Instagram connection flow finished. Please review the saved connection status below.',
        };

        $redirect = redirect()->route('settings.index', [
            'section' => 'channels',
        ]);

        if ($isSuccess) {
            return $redirect->with('status', $message);
        }

        return $redirect->with('error', $message);
    }
}
