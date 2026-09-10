<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Beheer je Spaansspreken.nl-account, e-mailadres en wachtwoord.">
    <meta name="theme-color" content="#172c36">
    <title>Mijn account · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f1e7] text-[#302722] antialiased">
    <a href="#account-content" class="hub-skip-link">Ga naar accountbeheer</a>

    <header class="border-b border-[#493429]/10 bg-[#fffaf0]/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl font-black tracking-tight text-[#302722] focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">
                <span class="grid size-10 place-items-center rounded-xl bg-[#172c36] text-[#f5b94f]" aria-hidden="true">S</span>
                <span>Spaansspreken<span class="text-[#bd5a34]">.nl</span></span>
            </a>
            <nav class="flex flex-wrap items-center gap-2" aria-label="Spelersnavigatie">
                <a href="{{ route('player.progress') }}" class="player-account-nav">Mijn voortgang</a>
                <a href="{{ route('trial-week.show') }}" class="player-account-nav">Proefweek en abonnement</a>
                @can('content-studio.view')
                    <a href="{{ route('content-studio.dashboard') }}" class="player-account-nav">Content Studio</a>
                @endcan
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="player-account-nav">Uitloggen</button>
                </form>
            </nav>
        </div>
    </header>

    <main id="account-content" class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
        <section class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)] lg:items-end" aria-labelledby="account-title">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[#a9472b]">Mi cuenta</p>
                <h1 id="account-title" class="mt-3 font-serif text-4xl font-black tracking-tight sm:text-5xl">Jouw account</h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-[#72645c]">Beheer hoe je inlogt. Je missievoortgang, beloningen en abonnement blijven aan dit account gekoppeld.</p>
            </div>

            @php
                $accessPresentation = match ($access['state']) {
                    'trialing' => ['Proefweek actief', 'bg-[#e4f0df] text-[#315d47]'],
                    'active' => ['Abonnement actief', 'bg-[#e4f0df] text-[#315d47]'],
                    'past_due' => ['Betaling openstaand', 'bg-[#fff0cc] text-[#7b5615]'],
                    'cancelled' => ['Opgezegd', 'bg-[#efe4d5] text-[#7b6558]'],
                    'paused' => ['Toegang gepauzeerd', 'bg-[#f8dfdf] text-[#8a3838]'],
                    'expired' => ['Toegang verlopen', 'bg-[#efe4d5] text-[#7b6558]'],
                    default => ['Gratis account', 'bg-[#e8e5df] text-[#70665e]'],
                };
            @endphp
            <aside class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-5 shadow-sm" aria-label="Toegangsstatus">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $accessPresentation[1] }}">{{ $accessPresentation[0] }}</span>
                    @if ($access['access_active'])
                        <span class="text-xs font-black text-[#315d47]">Toegang actief</span>
                    @endif
                </div>
                <p class="mt-4 font-black">{{ $access['plan']['name'] ?? 'Madrid · gratis kennismaken' }}</p>
                @if ($access['valid_until'])
                    <p class="mt-1 text-xs text-[#75675e]">Geldig tot {{ \Carbon\CarbonImmutable::parse($access['valid_until'])->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</p>
                @endif
                @if ($latestOrder)
                    <p class="mt-3 text-xs text-[#75675e]">Laatste betaalstatus: <strong>{{ $latestOrder->payment_status->label() }}</strong></p>
                @endif
                <a href="{{ route('trial-week.show') }}" class="mt-4 inline-flex min-h-11 items-center font-black text-[#a9472b] underline decoration-[#a9472b]/30 underline-offset-4">Bekijk proefweek en facturen</a>
            </aside>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-8" aria-labelledby="profile-title">
                <p class="text-xs font-black uppercase tracking-[0.15em] text-[#a9472b]">Profiel</p>
                <h2 id="profile-title" class="mt-2 text-2xl font-black">Naam en e-mailadres</h2>
                <p class="mt-2 text-sm leading-6 text-[#75675e]">Bij een wijziging van je e-mailadres vragen we ter controle je huidige wachtwoord.</p>

                @if (session('profile_status'))
                    <div class="player-auth-success" role="status">{{ session('profile_status') }}</div>
                @endif

                @if ($errors->getBag('profile')->any())
                    <div class="player-auth-error" role="alert">Je profiel kon nog niet worden bijgewerkt. Controleer de gemarkeerde velden.</div>
                @endif

                <form method="POST" action="{{ route('player.account.profile') }}" class="mt-6 space-y-5" novalidate>
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="name" class="player-auth-label">Naam</label>
                        <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" autocomplete="name" maxlength="120" required class="player-auth-field @error('name', 'profile') player-auth-field-error @enderror">
                        @error('name', 'profile')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="player-auth-label">E-mailadres</label>
                        <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email" maxlength="255" required class="player-auth-field @error('email', 'profile') player-auth-field-error @enderror">
                        @error('email', 'profile')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile-current-password" class="player-auth-label">Huidig wachtwoord <span class="font-normal text-[#867970]">(alleen nodig bij nieuw e-mailadres)</span></label>
                        <input id="profile-current-password" name="current_password" type="password" autocomplete="current-password" class="player-auth-field @error('current_password', 'profile') player-auth-field-error @enderror">
                        @error('current_password', 'profile')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="player-auth-primary">Profiel opslaan</button>
                </form>
            </section>

            <section class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-8" aria-labelledby="password-title">
                <p class="text-xs font-black uppercase tracking-[0.15em] text-[#315d47]">Beveiliging</p>
                <h2 id="password-title" class="mt-2 text-2xl font-black">Wachtwoord wijzigen</h2>
                <p class="mt-2 text-sm leading-6 text-[#75675e]">Gebruik minimaal 12 tekens met letters en cijfers. Deel je wachtwoord nooit met anderen.</p>

                @if (session('password_status'))
                    <div class="player-auth-success" role="status">{{ session('password_status') }}</div>
                @endif

                @if ($errors->getBag('password')->any())
                    <div class="player-auth-error" role="alert">Je wachtwoord kon nog niet worden gewijzigd.</div>
                @endif

                <form method="POST" action="{{ route('player.account.password') }}" class="mt-6 space-y-5" novalidate>
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="current-password" class="player-auth-label">Huidig wachtwoord</label>
                        <input id="current-password" name="current_password" type="password" autocomplete="current-password" required class="player-auth-field @error('current_password', 'password') player-auth-field-error @enderror">
                        @error('current_password', 'password')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new-password" class="player-auth-label">Nieuw wachtwoord</label>
                        <input id="new-password" name="password" type="password" autocomplete="new-password" required class="player-auth-field @error('password', 'password') player-auth-field-error @enderror">
                        @error('password', 'password')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new-password-confirmation" class="player-auth-label">Herhaal nieuw wachtwoord</label>
                        <input id="new-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="player-auth-field">
                    </div>
                    <button type="submit" class="player-auth-primary">Wachtwoord wijzigen</button>
                </form>
            </section>
        </div>

        <aside class="mt-6 rounded-3xl border border-[#493429]/10 bg-white/65 p-6 text-sm leading-6 text-[#70625a]">
            Accountverwijdering is nog niet geautomatiseerd, omdat de bewaartermijn voor betaal- en factuurgegevens eerst formeel moet worden vastgesteld. Een verzoek kan alvast naar <a href="mailto:{{ config('subscriptions.invoicing.support_email') }}" class="font-black text-[#a9472b] underline underline-offset-4">{{ config('subscriptions.invoicing.support_email') }}</a>.
        </aside>
    </main>
</body>
</html>
