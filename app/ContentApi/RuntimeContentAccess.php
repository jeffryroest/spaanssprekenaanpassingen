<?php

namespace App\ContentApi;

use App\Models\ContentReleaseItem;

final class RuntimeContentAccess
{
    public function allowsEntitlement(ContentReleaseItem $releaseItem, string $entitlement): bool
    {
        $contract = $this->contract($releaseItem);

        return ($contract['visibility'] ?? null) === 'entitled'
            && ($contract['entitlement'] ?? null) === $entitlement;
    }

    /** @return array<string, mixed> */
    private function contract(ContentReleaseItem $releaseItem): array
    {
        $snapshot = $releaseItem->contentRevision?->snapshot;
        $domainData = is_array($snapshot) ? ($snapshot['domain_data'] ?? null) : null;
        $runtimeAccess = is_array($domainData) ? ($domainData['runtime_access'] ?? null) : null;

        return is_array($runtimeAccess) ? $runtimeAccess : [];
    }
}
