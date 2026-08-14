<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentRole;
use App\Models\ContentRoleAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AssignContentRole
{
    public function handle(User $user, ContentRole $role, ?User $actor = null): void
    {
        if ($user->content_role === $role) {
            return;
        }

        DB::transaction(function () use ($actor, $role, $user): void {
            $previousRole = $user->content_role;

            $user->forceFill(['content_role' => $role])->save();

            ContentRoleAudit::query()->create([
                'user_id' => $user->getKey(),
                'actor_id' => $actor?->getKey(),
                'from_role' => $previousRole,
                'to_role' => $role,
                'created_at' => now(),
            ]);
        });
    }
}
