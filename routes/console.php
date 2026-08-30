<?php

use App\Actions\ContentStudio\AssignContentRole;
use App\ContentStudio\DemoContentInstaller;
use App\Enums\ContentPermission;
use App\Enums\ContentRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('content-studio:provision-administrator {email} {--name=}', function (string $email) {
    $email = Str::lower(trim($email));
    $user = User::query()->where('email', $email)->first();

    if ($user === null) {
        $name = $this->option('name') ?: $this->ask('Naam');
        $password = $this->secret('Wachtwoord (minimaal 12 tekens)');
        $passwordConfirmation = $this->secret('Herhaal het wachtwoord');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers(),
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return Command::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }

    app(AssignContentRole::class)->handle($user, ContentRole::Administrator);

    $this->info("{$user->email} heeft nu de rol Beheerder.");

    return Command::SUCCESS;
})->purpose('Maak de eerste beheerder aan of geef een bestaande gebruiker de beheerdersrol');

Artisan::command('game:install-demo-content {--actor= : E-mailadres van de bestaande Content Studio-beheerder} {--dry-run : Toon alleen wat zou veranderen}', function () {
    $email = Str::lower(trim((string) ($this->option('actor') ?: config('content-studio.demo_actor_email'))));

    if ($email === '') {
        $this->error('Geef --actor=beheerder@example.com mee of stel CONTENT_STUDIO_DEMO_ACTOR_EMAIL in.');

        return Command::FAILURE;
    }

    $actor = User::query()->where('email', $email)->first();

    if ($actor === null) {
        $this->error("Er bestaat geen gebruiker met e-mailadres {$email}.");

        return Command::FAILURE;
    }

    if (! $actor->hasContentPermission(ContentPermission::Edit)) {
        $this->error('De gekozen gebruiker mag geen Content Studio-concepten aanmaken.');

        return Command::FAILURE;
    }

    $result = app(DemoContentInstaller::class)->install($actor, (bool) $this->option('dry-run'));
    $this->info('Demopakket '.$result['package_version'].($this->option('dry-run') ? ' · controlemodus' : ''));
    $this->table(
        ['Sleutel', 'Slug', 'Uitkomst', 'Toelichting'],
        array_map(static fn (array $item): array => [
            $item['key'],
            $item['slug'],
            $item['result'],
            $item['message'],
        ], $result['items']),
    );

    if ($result['conflicts']) {
        $this->error('Er is niets aangemaakt. Los de conflicten op; bestaande inhoud wordt nooit overschreven.');

        return Command::FAILURE;
    }

    if ($this->option('dry-run')) {
        $this->comment('Controle voltooid; de database is niet gewijzigd.');

        return Command::SUCCESS;
    }

    $this->info('Alle ontbrekende voorbeelden staan als concept klaar. Review en publiceer ze bewust in de Content Studio.');

    return Command::SUCCESS;
})->purpose('Installeer het versiegebonden speelbare demopakket als veilige conceptcontent');
