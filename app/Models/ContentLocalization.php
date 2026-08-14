<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'content_node_id',
    'locale',
    'title',
    'summary',
    'body',
    'metadata',
])]
class ContentLocalization extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function contentNode(): BelongsTo
    {
        return $this->belongsTo(ContentNode::class);
    }
}
