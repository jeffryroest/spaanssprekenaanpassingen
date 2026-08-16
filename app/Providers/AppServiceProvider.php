<?php

namespace App\Providers;

use App\Enums\ContentPermission;
use App\Feedback\Contracts\TurnAssessor;
use App\Feedback\Contracts\TurnContextResolver;
use App\Feedback\OpenAiTurnAssessor;
use App\Feedback\PublishedConversationTurnResolver;
use App\Models\User;
use App\Speech\Contracts\Transcriber;
use App\Speech\OpenAiTranscriber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Transcriber::class, OpenAiTranscriber::class);
        $this->app->bind(TurnContextResolver::class, PublishedConversationTurnResolver::class);
        $this->app->bind(TurnAssessor::class, OpenAiTurnAssessor::class);
    }

    public function boot(): void
    {
        RateLimiter::for('speech-transcriptions', fn (Request $request): Limit => Limit::perMinute(10)
            ->by(implode('|', [$request->ip(), $request->session()->getId()]))
            ->response(fn () => response()->json([
                'schema_version' => '1.0.0',
                'error' => [
                    'code' => 'rate_limit_exceeded',
                    'message' => 'Te veel transcriptiepogingen. Wacht een minuut of gebruik tekst.',
                ],
            ], 429)));

        RateLimiter::for('turn-feedback', fn (Request $request): Limit => Limit::perMinute(20)
            ->by(implode('|', [$request->ip(), $request->session()->getId()]))
            ->response(fn () => response()->json([
                'schema_version' => '1.0.0',
                'error' => [
                    'code' => 'rate_limit_exceeded',
                    'message' => 'Te veel feedbackpogingen. Je kunt veilig doorgaan of het later opnieuw proberen.',
                ],
            ], 429)));

        foreach (ContentPermission::cases() as $permission) {
            Gate::define(
                "content-studio.{$permission->value}",
                fn (User $user): bool => $user->hasContentPermission($permission),
            );
        }
    }
}
