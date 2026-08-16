<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_homepage_introduces_the_madrid_hub(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Spaansspreken.nl')
            ->assertSee('Madrid · La panadería')
            ->assertSee('Start in Madrid')
            ->assertSee('Fase 3B1');
    }
}
