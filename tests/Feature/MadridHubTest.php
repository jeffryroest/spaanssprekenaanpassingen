<?php

namespace Tests\Feature;

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
            ->assertSee('data-hub-list-view', false)
            ->assertSee('data-hub-sound', false);
    }

    public function test_homepage_links_to_the_madrid_hub(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('game.madrid'))
            ->assertSee('Start in Madrid');
    }
}
