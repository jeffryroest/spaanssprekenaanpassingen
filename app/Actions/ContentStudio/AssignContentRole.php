<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentRole;
use App\Models\ContentRoleAudit;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class AssignContentRole
{
    public function handle(User $user, ?ContentRole $role, ?User $actor = null): void
    {
        if ($user->content_role === $role) {
            return;
        }

        if ($actor?->is($user)) {
            throw new DomainException('Je kunt je eigen Content Studio-rol niet wijzigen.');
        }

        DB::transaction(function () use ($actor, $role, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $previousRole = $lockedUser->content_role;

            if ($previousRole === $role) {
                return;
            }

            if ($previousRole === ContentRole::Administrator && $role !== ContentRole::Administrator) {
                $administrators = User::query()
                    ->where('content_role', ContentRole::Administrator)
                    ->lockForUpdate()
                    ->get(['id']);

                if ($administrators->count() <= 1) {
                    throw new DomainException('De laatste beheerder kan niet worden verwijderd.');
                }
            }

            $lockedUser->forceFill(['content_role' => $role])->save();

            ContentRoleAudit::query()->create([
                'user_id' => $lockedUser->getKey(),
                'actor_id' => $actor?->getKey(),
                'from_role' => $previousRole,
                'to_role' => $role,
                'created_at' => now(),
            ]);

            $user->setRawAttributes($lockedUser->getAttributes(), true);
        });
    }
}
