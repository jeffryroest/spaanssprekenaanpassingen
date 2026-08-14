<?php

namespace App\Models;

use App\Enums\ContentRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'actor_id', 'from_role', 'to_role', 'created_at'])]
class ContentRoleAudit extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'from_role' => ContentRole::class,
            'to_role' => ContentRole::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
