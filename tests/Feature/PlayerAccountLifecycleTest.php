<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PlayerAccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_create_an_account_and_enters_the_game_home(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '  Ana   de Vries  ',
            'email' => 'Ana@example.com',
            'password' => 'madridspel2026',
            'password_confirmation' => 'madridspel2026',
        ]);

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Ana de Vries',
            'email' => 'ana@example.com',
            'content_role' => null,
        ]);
    }

    public function test_registration_rejects_duplicate_email_and_weak_password(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'kort',
            'password_confirmation' => 'kort',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_known_account_receives_a_password_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_account_gets_the_same_generic_reset_response(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'onbekend@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een herstelmail.');

        Notification::assertNothingSent();
    }

    public function test_player_can_reset_the_password_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nieuwmadrid2026',
            'password_confirmation' => 'nieuwmadrid2026',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('nieuwmadrid2026', $user->fresh()->password));
    }

    public function test_account_page_is_private_and_shows_access_without_free_learning_data(): void
    {
        $user = User::factory()->create(['name' => 'Ana Speler']);

        $this->get(route('player.account'))->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('player.account'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Ana Speler')
            ->assertSee('Gratis account')
            ->assertDontSee('transcript');
    }

    public function test_player_can_update_name_but_email_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->put(route('player.account.profile'), [
                'name' => 'Ana de Vries',
                'email' => 'ana@example.com',
            ])->assertSessionHas('profile_status');

        $this->assertSame('Ana de Vries', $user->fresh()->name);

        $this->put(route('player.account.profile'), [
            'name' => 'Ana de Vries',
            'email' => 'nieuw@example.com',
        ])->assertSessionHasErrors(['current_password'], null, 'profile');

        $this->assertSame('ana@example.com', $user->fresh()->email);

        $this->put(route('player.account.profile'), [
            'name' => 'Ana de Vries',
            'email' => 'nieuw@example.com',
            'current_password' => 'password',
        ])->assertSessionHas('profile_status');

        $this->assertSame('nieuw@example.com', $user->fresh()->email);
    }

    public function test_player_can_change_password_only_with_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('player.account.password'), [
                'current_password' => 'verkeerd',
                'password' => 'nieuwmadrid2026',
                'password_confirmation' => 'nieuwmadrid2026',
            ])->assertSessionHasErrors(['current_password'], null, 'password');

        $this->put(route('player.account.password'), [
            'current_password' => 'password',
            'password' => 'nieuwmadrid2026',
            'password_confirmation' => 'nieuwmadrid2026',
        ])->assertSessionHas('password_status');

        $this->assertTrue(Hash::check('nieuwmadrid2026', $user->fresh()->password));
    }
}
