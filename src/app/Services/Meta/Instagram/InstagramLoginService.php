<?php

namespace App\Services\Meta\Instagram;

use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class InstagramLoginService
{
    public function __construct(
        protected InstagramTokenExchangeService $instagramTokenExchangeService
    ) {
    }

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
            'redirect_uri' => route('connections.instagram.callback'),
            'response_type' => 'code',
            'scope' => config('services.instagram.scopes'),
            'state' => $state,
        ]);

        Session::put('instagram_debug_authorization_url', $baseUrl . '?' . $query);

        return $baseUrl . '?' . $query;
    }

    public function handleCallback(Request $request): array
    {
        $incomingState = $request->string('state')->toString();
        $sessionState = Session::get('instagram_oauth_state');

        $decodedState = [];

        if ($incomingState !== '') {
            $decoded = json_decode(base64_decode($incomingState), true);
            $decodedState = is_array($decoded) ? $decoded : [];
        }

        $stateIsValid = $incomingState !== '' &&
            $sessionState !== null &&
            hash_equals($sessionState, $incomingState);

        $workspaceId = Arr::get($decodedState, 'workspace_id');
        $status = $request->has('code')
            ? ($stateIsValid ? 'callback_received' : 'invalid_state')
            : 'callback_missing_code';

        $savedConnectionId = null;
        $exchangeResult = null;

        if ($status === 'callback_received' && $workspaceId) {
            $connection = ProviderConnection::updateOrCreate(
                [
                    'workspace_id' => $workspaceId,
                    'provider' => 'instagram',
                    'provider_account_type' => 'instagram_account',
                    'provider_account_id' => 'pending-instagram-account',
                ],
                [
                    'provider_account_name' => 'Pending Instagram Connection',
                    'status' => 'pending_token_exchange',
                    'connected_at' => now(),
                    'last_synced_at' => null,
                    'meta' => [
                        'callback_code' => $request->input('code'),
                        'callback_state' => $decodedState,
                        'incoming_state' => $incomingState,
                        'authorization_url' => Session::get('instagram_debug_authorization_url'),
                        'mode' => 'local_debug',
                    ],
                ]
            );

            $savedConnectionId = $connection->id;

            $exchangeResult = $this->instagramTokenExchangeService
                ->exchangeAndStore($connection, (string) $request->input('code'));
        }

        return [
            'status' => $status,
            'code' => $request->input('code'),
            'error' => $request->input('error'),
            'error_reason' => $request->input('error_reason'),
            'error_description' => $request->input('error_description'),
            'workspace_id' => $workspaceId,
            'state' => $decodedState,
            'incoming_state' => $incomingState,
            'session_state' => $sessionState,
            'state_is_valid' => $stateIsValid,
            'redirect_uri' => route('connections.instagram.callback'),
            'authorization_url' => Session::get('instagram_debug_authorization_url'),
            'saved_connection_id' => $savedConnectionId,
            'exchange_result' => $exchangeResult,
        ];
    }
}
