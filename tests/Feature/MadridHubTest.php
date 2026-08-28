<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MadridHubTest extends TestCase
{
    public function test_guest_can_open_the_madrid_hub_shell(): void
    {
        $this->get('/spelen/madrid')
            ->assertOk()
            ->assertSee('Madrid · Spaansspreken.nl')
            ->assertSee('Ga naar de Madrid-kaart')
            ->assertSee('data-madrid-hub', false)
            ->assertSee('/api/v1/worlds/madrid?locale=nl-NL', false)
            ->assertSee('data-authenticated="false"', false)
            ->assertSee(route('game.progress'), false)
            ->assertSee(route('game.trial-week.status'), false)
            ->assertSee(route('game.madrid.restaurant'), false)
            ->assertSee('data-account-xp', false)
            ->assertSee('data-hub-list-view', false)
            ->assertSee('data-hub-sound', false)
            ->assertSee('data-hub-arrival', false)
            ->assertSee('data-hub-preparation', false)
            ->assertSee('madrid-morning.webp', false);
    }

    public function test_authenticated_hub_exposes_account_progress_contract(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/spelen/madrid')
            ->assertOk()
            ->assertSee('data-authenticated="true"', false)
            ->assertSee(route('player.progress'), false);
    }

    public function test_homepage_links_to_the_madrid_hub(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('game.madrid'))
            ->assertSee('Start je eerste missie');
    }
}
