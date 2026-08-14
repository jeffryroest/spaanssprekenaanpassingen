<?php

use App\Actions\ContentStudio\AssignContentRole;
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
