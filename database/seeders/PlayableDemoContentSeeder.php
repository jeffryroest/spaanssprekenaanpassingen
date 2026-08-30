<?php

namespace Database\Seeders;

use App\ContentStudio\DemoContentInstaller;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PlayableDemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('content-studio.demo_actor_email');

        if (! is_string($email) || trim($email) === '') {
            throw new RuntimeException('Stel CONTENT_STUDIO_DEMO_ACTOR_EMAIL in op een bestaande beheerder.');
        }

        $actor = User::query()->where('email', trim($email))->first();

        if ($actor === null) {
            throw new RuntimeException('De ingestelde demo-contentbeheerder bestaat niet.');
        }

        $result = app(DemoContentInstaller::class)->install($actor);

        if ($result['conflicts']) {
            throw new RuntimeException('Het demopakket heeft conflicten en heeft niets overschreven.');
        }
    }
}
