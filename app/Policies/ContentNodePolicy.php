<?php

namespace App\Policies;

use App\Enums\ContentPermission;
use App\Enums\ContentRole;
use App\Models\ContentNode;
use App\Models\User;

class ContentNodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasContentPermission(ContentPermission::View);
    }

    public function view(User $user, ContentNode $contentNode): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasContentPermission(ContentPermission::Edit);
    }

    public function update(User $user, ContentNode $contentNode): bool
    {
        if (! $user->hasContentPermission(ContentPermission::Edit)) {
            return false;
        }

        return $user->content_role !== ContentRole::Editor
            || (int) $contentNode->created_by === (int) $user->getKey();
    }

    public function delete(User $user, ContentNode $contentNode): bool
    {
        return $this->update($user, $contentNode);
    }
}
