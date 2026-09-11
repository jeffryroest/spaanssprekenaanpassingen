<?php

use App\Actions\ContentStudio\AssignContentRole;
use App\Billing\MollieMonthlyOffer;
use App\ContentStudio\DemoContentInstaller;
use App\ContentStudio\TrialWeekContentRelease;
use App\Enums\BillingInterval;
use App\Enums\ContentPermission;
use App\Enums\ContentRole;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:send-due-emails')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('operations:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

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

Artisan::command('game:install-demo-content
    {--actor= : E-mailadres van de bestaande Content Studio-beheerder}
    {--dry-run : Toon alleen wat zou veranderen}
    {--replace-existing : Vervang uitsluitend onvolledige, ongepubliceerde demoplaceholders}
    {--confirm= : Vereist OVERSCHRIJVEN bij een echte vervanging}', function () {
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

    $replaceExisting = (bool) $this->option('replace-existing');
    $dryRun = (bool) $this->option('dry-run');

    if ($replaceExisting && ! $dryRun && $this->option('confirm') !== 'OVERSCHRIJVEN') {
        $this->error('Bevestig een echte vervanging expliciet met --confirm=OVERSCHRIJVEN.');

        return Command::FAILURE;
    }

    $result = app(DemoContentInstaller::class)->install($actor, $dryRun, $replaceExisting);
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

Artisan::command('game:publish-trial-week-content
    {--actor= : E-mailadres van de bestaande Content Studio-uitgever}
    {--reviewer= : E-mailadres van een onafhankelijke Content Studio-reviewer}
    {--dry-run : Controleer de volledige workflow zonder iets te wijzigen}
    {--confirm= : Vereist PUBLICEREN voor een echte productierelease}', function () {
    $publisherEmail = Str::lower(trim((string) $this->option('actor')));
    $reviewerEmail = Str::lower(trim((string) $this->option('reviewer')));

    if ($publisherEmail === '' || $reviewerEmail === '') {
        $this->error('Geef zowel --actor=uitgever@example.com als --reviewer=reviewer@example.com mee.');

        return Command::FAILURE;
    }

    $publisher = User::query()->where('email', $publisherEmail)->first();
    $reviewer = User::query()->where('email', $reviewerEmail)->first();

    if ($publisher === null || $reviewer === null) {
        $this->error('De opgegeven uitgever of reviewer bestaat niet. Provision beide accounts eerst bewust.');

        return Command::FAILURE;
    }

    try {
        $service = app(TrialWeekContentRelease::class);
        $plan = $service->plan($publisher, $reviewer);
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Proefweekpakket '.$plan['package_version'].($this->option('dry-run') ? ' · controlemodus' : ''));
    $this->table(
        ['Sleutel', 'Slug', 'Huidige status', 'Vervolg', 'Blokkade'],
        array_map(static fn (array $item): array => [
            $item['key'],
            $item['slug'],
            $item['status'],
            $item['action'],
            $item['blocker'] ?? '—',
        ], $plan['items']),
    );

    if (! $plan['ready']) {
        $this->error('Er is niets gewijzigd. Los de getoonde blokkades eerst inhoudelijk op.');

        return Command::FAILURE;
    }

    if ($this->option('dry-run')) {
        $this->comment('Controle voltooid; content, reviews, releases en media zijn niet gewijzigd.');

        return Command::SUCCESS;
    }

    if ($this->option('confirm') !== 'PUBLICEREN') {
        $this->error('Bevestig de productierelease expliciet met --confirm=PUBLICEREN. Er is niets gewijzigd.');

        return Command::FAILURE;
    }

    try {
        $result = $service->publish($publisher, $reviewer);
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }

    if ($result['release'] === null) {
        $this->info("Alle {$result['already_published_count']} contentonderdelen stonden al als exacte pakketversie op productie.");

        return Command::SUCCESS;
    }

    $this->info("Productierelease #{$result['release']->getKey()} is uitgevoerd: {$result['published_count']} gepubliceerd, {$result['already_published_count']} al actueel.");

    return Command::SUCCESS;
})->purpose('Controleer, review en publiceer het complete zevendaagse proefweekpakket met vier-ogencontrole');

Artisan::command('subscriptions:install-mollie-monthly {--dry-run : Controleer zonder de database te wijzigen}', function () {
    $offer = app(MollieMonthlyOffer::class);
    $configuration = $offer->configuration();
    $existing = SubscriptionPlan::withTrashed()
        ->where('code', $configuration['code'])
        ->first();

    if ($existing !== null) {
        if ($existing->trashed() || ! $offer->matches($existing)) {
            $this->error('Het bestaande maandplan wijkt af. Er is niets overschreven; controleer het plan handmatig.');

            return Command::FAILURE;
        }

        $this->info('Het Mollie-maandplan is al exact en actief: € 9,95 per maand.');

        return Command::SUCCESS;
    }

    if ($this->option('dry-run')) {
        $this->comment('Controle voltooid: het plan kan als € 9,95 per maand worden geïnstalleerd.');

        return Command::SUCCESS;
    }

    SubscriptionPlan::query()->create([
        'code' => $configuration['code'],
        'name' => $configuration['name'],
        'billing_interval' => BillingInterval::from($configuration['billing_interval']),
        'currency' => $configuration['currency'],
        'amount_minor' => $configuration['amount_minor'],
        'trial_days' => $configuration['trial_days'],
        'provider_price_ref' => null,
        'entitlements' => $configuration['entitlements'],
        'active' => true,
    ]);

    $this->info('Het Mollie-maandplan is geïnstalleerd: € 9,95 per maand. Live betaling staat nog los hiervan.');

    return Command::SUCCESS;
})->purpose('Installeer het goedgekeurde Mollie-maandplan veilig en idempotent');
