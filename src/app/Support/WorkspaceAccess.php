<?php

namespace App\Support;

use App\Models\User;

final class WorkspaceAccess
{
    public const ACCESS = 'workspace.access';

    public const MANAGE = 'workspace.manage';

    /**
     * @var array<string, list<string>>
     */
    private const ROLE_CAPABILITIES = [
        'owner' => [self::ACCESS, self::MANAGE],
        'agent' => [self::ACCESS],
        'member' => [self::ACCESS],
    ];

    public static function allows(User $user, string $capability): bool
    {
        $workspace = $user->currentWorkspace();

        if (! $workspace) {
            return false;
        }

        $role = (int) $workspace->owner_id === (int) $user->id
            ? 'owner'
            : (string) ($workspace->pivot?->role ?? '');

        return in_array($capability, self::ROLE_CAPABILITIES[$role] ?? [], true);
    }
}
