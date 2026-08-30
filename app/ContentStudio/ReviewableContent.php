<?php

namespace App\ContentStudio;

use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\ContentRevision;
use App\Rules\PlayableDomainData;
use Illuminate\Support\Facades\Validator;

final class ReviewableContent
{
    /** @return list<string> */
    public function errors(ContentNode $contentNode, ContentRevision $revision): array
    {
        if (! in_array($contentNode->content_type, [ContentType::Region, ContentType::ConversationScenario], true)) {
            return [];
        }

        $domainData = data_get($revision->snapshot, 'domain_data', []);
        $validator = Validator::make([
            'domain_data' => $domainData,
            'scene' => is_array($domainData) ? ($domainData['scene'] ?? null) : null,
        ], [
            'domain_data' => ['required', 'array', 'min:1', new PlayableDomainData($contentNode->content_type)],
            'scene' => ['required', 'string'],
        ], [
            'domain_data.required' => 'Speelbare content vereist ingevulde speeldata.',
            'domain_data.min' => 'Speelbare content vereist ingevulde speeldata.',
            'scene.required' => 'Speelbare content vereist een ondersteund scene-contract.',
        ]);

        return $validator->fails()
            ? array_values(array_unique($validator->errors()->all()))
            : [];
    }
}
