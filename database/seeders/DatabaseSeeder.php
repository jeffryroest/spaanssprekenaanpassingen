<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || blank(config('content-studio.demo_actor_email'))) {
            return;
        }

        $this->call(PlayableDemoContentSeeder::class);
    }
}
