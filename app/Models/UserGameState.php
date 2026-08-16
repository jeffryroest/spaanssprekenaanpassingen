<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'total_xp',
    'confianza',
    'valentia',
    'state_version',
    'last_learning_date',
])]
class UserGameState extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'total_xp' => 'integer',
            'confianza' => 'integer',
            'valentia' => 'integer',
            'state_version' => 'integer',
            'last_learning_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
