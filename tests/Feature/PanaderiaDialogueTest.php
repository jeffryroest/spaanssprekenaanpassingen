<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertSee('data-speech-recorder', false)
            ->assertSee(route('game.madrid.panaderia.transcription'), false)
            ->assertSee(route('game.madrid.panaderia.feedback'), false)
            ->assertSee(route('game.madrid.panaderia.complete'), false)
            ->assertSee('data-authenticated="false"', false)
            ->assertSee('data-account-sync', false)
            ->assertSee('data-feedback-details', false)
            ->assertSee('data-feedback-retry', false)
            ->assertSee('data-dialogue-history', false)
            ->assertSee('data-preparation-summary', false)
            ->assertSee('Ik wil een voorbeeldzin');
    }

    public function test_authenticated_dialogue_can_link_account_storage_and_progress(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/spelen/madrid/la-panaderia')
            ->assertOk()
            ->assertSee('data-authenticated="true"', false)
            ->assertSee(route('game.madrid.panaderia.complete'), false)
            ->assertSee(route('player.progress'), false)
            ->assertSee('Bekijk mijn voortgang');
    }

    public function test_panaderia_links_back_to_the_madrid_hub(): void
    {
        $this->get('/spelen/madrid/la-panaderia')
            ->assertOk()
            ->assertSee(route('game.madrid'))
            ->assertSee('Terug naar Madrid');
    }

    public function test_panaderia_explains_speech_privacy_and_keeps_text_fallback(): void
    {
        $this->get('/spelen/madrid/la-panaderia')
            ->assertOk()
            ->assertSee('WebM/Opus · maximaal 12 seconden')
            ->assertSee('De microfoon start pas wanneer jij op opnemen drukt.')
            ->assertSee(route('privacy'))
            ->assertSee('of typ je antwoord');
    }
}
