<?php

namespace App\ContentStudio;

use App\Enums\ContentRole;
use App\Models\ContentRevision;
use App\Models\User;

final class ContentReviewPolicy
{
    public function allowsSelfApproval(User $actor, ContentRevision $revision): bool
    {
        if (config('content-studio.review_mode') !== 'risk_based') {
            return false;
        }

        if (! in_array($actor->content_role, [ContentRole::Administrator, ContentRole::EditorInChief], true)) {
            return false;
        }

        return ! $this->requiresIndependentReviewer($revision);
    }

    public function requiresIndependentReviewer(ContentRevision $revision): bool
    {
        $domainData = data_get($revision->snapshot, 'domain_data', []);

        return data_get($domainData, 'scene') === 'health_text_dialogue'
            || data_get($domainData, 'review.risk_tier') === 'high'
            || data_get($domainData, 'review.requires_independent_reviewer') === true;
    }
}
