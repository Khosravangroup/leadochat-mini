<?php

use App\Models\ProviderConnection;
use App\Services\Meta\Instagram\InstagramWebhookSubscriptionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('instagram:webhooks:subscribe {connection? : Provider connection id or Instagram account id}', function (?string $connection = null) {
    $query = ProviderConnection::query()
        ->where('provider', 'instagram')
        ->where('status', 'connected');

    if ($connection) {
        $query->where(function ($builder) use ($connection) {
            $builder->whereKey($connection)
                ->orWhere('provider_account_id', $connection);
        });
    }

    $connections = $query->orderBy('id')->get();

    if ($connections->isEmpty()) {
        $this->warn('No connected Instagram provider connections found.');

        return 1;
    }

    $failed = 0;

    foreach ($connections as $providerConnection) {
        try {
            $result = app(InstagramWebhookSubscriptionService::class)
                ->ensureSubscribed($providerConnection);

            $this->info(json_encode([
                'connection_id' => $providerConnection->id,
                'provider_account_id' => $providerConnection->provider_account_id,
                'success' => (bool) ($result['success'] ?? false),
                'requested_fields' => $result['requested_fields'] ?? [],
                'verified_fields' => $result['verified_fields'] ?? [],
                'missing_fields' => $result['missing_fields'] ?? [],
            ], JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $exception) {
            $failed++;

            $this->error(json_encode([
                'connection_id' => $providerConnection->id,
                'provider_account_id' => $providerConnection->provider_account_id,
                'success' => false,
                'error' => $exception->getMessage(),
            ], JSON_UNESCAPED_SLASHES));
        }
    }

    return $failed > 0 ? 1 : 0;
})->purpose('Ensure connected Instagram accounts are subscribed to configured webhook fields');
