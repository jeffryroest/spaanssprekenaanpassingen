<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_homepage_displays_the_project_foundation(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Spaansspreken.nl')
            ->assertSee('Madrid · La panadería')
            ->assertSee('Laravel 13');
    }
}
