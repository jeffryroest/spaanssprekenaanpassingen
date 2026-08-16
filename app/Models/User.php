<?php

namespace App\Models;

use App\Enums\ContentPermission;
use App\Enums\ContentRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'content_role' => ContentRole::class,
        ];
    }

    public function hasContentPermission(ContentPermission $permission): bool
    {
        return $this->content_role?->allows($permission) ?? false;
    }

    public function gameState(): HasOne
    {
        return $this->hasOne(UserGameState::class);
    }

    public function missionProgress(): HasMany
    {
        return $this->hasMany(UserMissionProgress::class);
    }

    public function missionAttempts(): HasMany
    {
        return $this->hasMany(MissionAttempt::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }
}
