<?php

namespace Tests\Feature;

use App\Enums\ContentRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentStudioDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_the_branded_player_world_shell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('player-auth-shell', false)
            ->assertSee('Ga verder in Madrid')
            ->assertSee('Maak gratis een account');
    }

    public function test_dashboard_uses_accessible_shared_navigation(): void
    {
        $user = User::factory()->create(['content_role' => ContentRole::Editor]);

        $this->actingAs($user)
            ->get(route('content-studio.dashboard'))
            ->assertOk()
            ->assertSee('Direct naar de inhoud')
            ->assertSee('aria-label="Hoofdnavigatie"', false)
            ->assertSee('data-studio-sidebar', false)
            ->assertSee('Reviewwachtrij')
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_forbidden_page_keeps_a_clear_recovery_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('content-studio.dashboard'))
            ->assertForbidden()
            ->assertSee('Je hebt geen toegang tot de Content Studio')
            ->assertSee('Terug naar de startpagina');
    }
}
