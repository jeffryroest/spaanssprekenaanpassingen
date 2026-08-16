<?php

namespace Tests\Feature;

use App\Enums\ContentRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_player_can_log_in_and_is_redirected_to_progress(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('player.progress', absolute: false));
    }

    public function test_content_editor_can_log_in_and_is_redirected_to_content_studio(): void
    {
        $user = User::factory()->create(['content_role' => ContentRole::Editor]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('content-studio.dashboard', absolute: false));
    }

    public function test_safe_game_redirect_is_restored_after_login(): void
    {
        $user = User::factory()->create();

        $this->get(route('login', [
            'redirect' => route('game.madrid.panaderia', absolute: false),
        ]))->assertOk();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('game.madrid.panaderia', absolute: false));
    }

    public function test_external_login_redirect_is_ignored(): void
    {
        $user = User::factory()->create();

        $this->get(route('login', ['redirect' => 'https://example.net/phishing']))->assertOk();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('player.progress', absolute: false));
    }

    public function test_invalid_credentials_are_rejected_without_revealing_account_state(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'De opgegeven inloggegevens zijn niet correct.',
        ]);
    }

    public function test_failed_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);
        }

        $this->assertTrue(
            RateLimiter::tooManyAttempts(strtolower($user->email).'|127.0.0.1', 5),
        );
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }
}
