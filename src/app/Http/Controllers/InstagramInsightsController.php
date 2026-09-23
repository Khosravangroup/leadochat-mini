<?php

namespace App\Http\Controllers;

use App\Models\ProviderConnection;
use App\Services\Meta\Instagram\InstagramInsightsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstagramInsightsController extends Controller
{
    public function index(Request $request, InstagramInsightsService $insights): Response
    {
        $workspace = $request->user()?->currentWorkspace();
        abort_unless($workspace, 404);

        $connections = ProviderConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where('provider', 'instagram')
            ->where('status', 'connected')
            ->latest('id')
            ->get();

        $requestedId = $request->query('instagram_account');
        $connection = $requestedId === null
            ? $connections->first()
            : $connections->firstWhere('id', filter_var($requestedId, FILTER_VALIDATE_INT) ?: 0);

        if ($requestedId !== null) {
            abort_unless($connection, 404);
        }

        $summary = null;
        $error = null;
        if ($connection) {
            try {
                $summary = $insights->accountSummary($connection);
            } catch (Throwable $exception) {
                Log::warning('Instagram insights request failed.', [
                    'provider_connection_id' => $connection->id,
                    'exception' => $exception::class,
                ]);
                $error = 'Instagram insights could not be loaded. Check the connection and granted permission.';
            }
        }

        return response()->view('social.instagram.insights', compact('connections', 'connection', 'summary', 'error'))
            ->header('Cache-Control', 'no-store, private');
    }
}
