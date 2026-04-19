<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public string $domain,
        public string $action,
        public array $payload = []
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workspace.' . $this->workspaceId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'workspace.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'domain' => $this->domain,
            'action' => $this->action,
            'payload' => $this->payload,
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
