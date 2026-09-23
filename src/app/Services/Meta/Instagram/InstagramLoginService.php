<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Throwable;

class InstagramLoginService
{
    public function __construct(
        protected InstagramTokenExchangeService $instagramTokenExchangeService
    ) {}

    public function buildAuthorizationUrl(int $workspaceId): string
    {
        $baseUrl = 'https://www.instagram.com/oauth/authorize';

        $payload = [
            'workspace_id' => $workspaceId,
            'nonce' => Str::uuid()->toString(),
        ];

        $state = base64_encode(json_encode($payload));

        Session::put('instagram_oauth_state', $state);
        Session::put('instagram_oauth_workspace_id', $workspaceId);

        $query = http_build_query([
            'client_id' => config('services.instagram.client_id'),
            'redirect_uri' => $this->resolveRedirectUri(),
            'response_type' => 'code',
            'scope' => config('services.instagram.scopes'),
            'state' => $state,
        ]);

        return $baseUrl.'?'.$query;
    }

    public function handleCallback(Request $request): array
    {
        $incomingState = $request->string('state')->toString();
        $sessionState = Session::get('instagram_oauth_state');
        $workspaceId = Session::get('instagram_oauth_workspace_id');

        $stateIsValid = $incomingState !== '' &&
            $sessionState !== null &&
            hash_equals($sessionState, $incomingState) &&
            (int) $workspaceId > 0 &&
            (int) $workspaceId === (int) $request->user()?->currentWorkspace()?->id;

        $status = ! $stateIsValid
            ? 'invalid_state'
            : ($request->filled('code') ? 'callback_received' : 'callback_missing_code');

        if ($stateIsValid) {
            Session::forget([
                'instagram_oauth_state',
                'instagram_oauth_workspace_id',
                'instagram_debug_authorization_url',
            ]);
        }

        $savedConnectionId = null;
        $exchangeResult = null;

        if ($status === 'callback_received' && $workspaceId) {
            $pendingAccountId = 'pending-instagram-account-'.$workspaceId;

            $connection = ProviderConnection::updateOrCreate(
                [
                    'workspace_id' => $workspaceId,
                    'provider' => 'instagram',
                    'provider_account_type' => 'instagram_account',
                    'provider_account_id' => $pendingAccountId,
                ],
                [
                    'provider_account_name' => 'Pending Instagram Connection',
                    'status' => 'pending_token_exchange',
                    'connected_at' => null,
                    'last_synced_at' => null,
                    'meta' => [
                        'callback_code_received' => true,
                        'mode' => app()->environment('local') ? 'local_debug' : 'instagram_login',
                    ],
                ]
            );

            $savedConnectionId = $connection->id;

            try {
                $exchangeResult = $this->instagramTokenExchangeService
                    ->exchangeAndStore($connection, (string) $request->input('code'));

                $status = ($exchangeResult['status'] ?? null) === 'connected' ? 'connected' : 'failed';
            } catch (Throwable $exception) {
                Log::warning('Instagram OAuth token exchange failed.', [
                    'connection_id' => $connection->id,
                    'exception_type' => $exception::class,
                ]);

                $status = 'failed';
            }

            if ($status === 'failed') {
                ProviderConnection::query()
                    ->whereKey($connection->id)
                    ->where('status', 'pending_token_exchange')
                    ->update(['status' => 'failed_token_exchange']);
            }
        }

        return [
            'status' => $status,
            'saved_connection_id' => $savedConnectionId,
            'exchange_result' => $exchangeResult,
        ];
    }

    protected function resolveRedirectUri(): string
    {
        return (string) (config('services.instagram.redirect_uri') ?: route('connections.instagram.callback'));
    }
}
