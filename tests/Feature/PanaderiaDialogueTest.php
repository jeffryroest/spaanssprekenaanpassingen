<?php

namespace Tests\Feature;

use Tests\TestCase;

class PanaderiaDialogueTest extends TestCase
{
    public function test_guest_can_enter_the_panaderia_dialogue_shell(): void
    {
        $this->get('/spelen/madrid/la-panaderia')
            ->assertOk()
            ->assertSee('La Espiga · Spaansspreken.nl')
            ->assertSee('Ga naar het gesprek met Lucía')
            ->assertSee('data-panaderia-dialogue', false)
            ->assertSee('/api/v1/conversations/la-espiga-lucia?locale=nl-NL', false)
            ->assertSee('data-translation-toggle', false)
            ->assertSee('data-dialogue-history', false);
    }

    public function test_panaderia_links_back_to_the_madrid_hub(): void
    {
        $this->get('/spelen/madrid/la-panaderia')
            ->assertOk()
            ->assertSee(route('game.madrid'))
            ->assertSee('Terug naar Madrid');
    }
}
