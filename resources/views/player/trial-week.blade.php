<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Bekijk de zeven missiedagen van je Spaanse proefweek en de toegang van je account.">
    <title>Mijn proefweek · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f1e7] text-[#302722] antialiased">
    <a href="#trial-week-content" class="hub-skip-link">Ga naar je proefweek</a>

    <header class="border-b border-[#493429]/10 bg-[#fffaf0]/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl font-black tracking-tight text-[#302722] focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">
                <span class="grid size-10 place-items-center rounded-xl bg-[#a9472b] text-white" aria-hidden="true">S</span>
                <span>Spaansspreken<span class="text-[#bd5a34]">.nl</span></span>
            </a>

            <nav class="flex flex-wrap items-center gap-2" aria-label="Accountnavigatie">
                <a href="{{ route('player.progress') }}" class="inline-flex min-h-11 items-center rounded-xl border border-[#493429]/15 bg-white px-4 text-sm font-bold text-[#60483c] hover:border-[#bd5a34]/40 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Mijn voortgang</a>
                <a href="{{ route('game.madrid') }}" class="inline-flex min-h-11 items-center rounded-xl border border-[#493429]/15 bg-white px-4 text-sm font-bold text-[#60483c] hover:border-[#bd5a34]/40 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Naar Madrid</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-bold text-[#78685e] hover:bg-[#493429]/5 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Uitloggen</button>
                </form>
            </nav>
        </div>
    </header>

    @php
        $status = match ($access['state']) {
            'trialing' => ['label' => 'Proefperiode actief', 'title' => 'Dag '.($access['trial_day'] ?? 1).' van '.($access['trial_days'] ?? 7), 'tone' => 'bg-[#e4f0df] text-[#315d47]', 'description' => 'Nieuwe missiedagen worden stap voor stap bereikbaar. Alleen gepubliceerde content kan worden gestart.'],
            'active' => ['label' => 'Toegang actief', 'title' => 'Je volledige proefweekroute is beschikbaar', 'tone' => 'bg-[#e4f0df] text-[#315d47]', 'description' => 'Je account heeft de benodigde rechten. Missies verschijnen zodra ze via de Content Studio zijn gepubliceerd.'],
            'past_due' => ['label' => 'Betaling openstaand', 'title' => 'Controleer je toegang', 'tone' => 'bg-[#fff0cc] text-[#7b5615]', 'description' => 'De toegangsservice past de ingestelde grace-policy server-side toe. Er worden hier geen betaalgegevens getoond.'],
            'paused' => ['label' => 'Gepauzeerd', 'title' => 'Je extra missiedagen zijn gepauzeerd', 'tone' => 'bg-[#efe4d5] text-[#705c4f]', 'description' => 'De openbare voorbeeldmissie blijft speelbaar.'],
            'cancelled' => ['label' => 'Opgezegd', 'title' => $access['access_active'] ? 'Toegang tot het periode-einde' : 'Je extra toegang is beëindigd', 'tone' => 'bg-[#efe4d5] text-[#705c4f]', 'description' => 'De server gebruikt het effectieve periode-einde en niet alleen het statuslabel.'],
            'expired' => ['label' => 'Verlopen', 'title' => 'Je extra toegang is verlopen', 'tone' => 'bg-[#f3dddd] text-[#8a3838]', 'description' => 'De openbare eerste missie blijft beschikbaar. Een betaalroute volgt pas na het productbesluit over prijs en voorwaarden.'],
            default => ['label' => 'Nog niet actief', 'title' => 'Begin met de openbare voorbeeldmissie', 'tone' => 'bg-[#efe4d5] text-[#705c4f]', 'description' => 'Automatische proefactivatie en betaling staan bewust nog uit. Je kunt La Espiga wel direct spelen.'],
        };
    @endphp

    <main id="trial-week-content" class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14" data-trial-week data-access-state="{{ $access['state'] }}">
        @if (session('access_notice'))
            <div class="mb-6 rounded-2xl border border-[#bd5a34]/25 bg-[#fff4e8] px-5 py-4 text-sm font-bold text-[#7a3e29]" role="status">
                {{ session('access_notice') }}
            </div>
        @endif

        <section class="grid gap-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end" aria-labelledby="trial-week-title">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[#a9472b]">Fase 3A · Mi semana</p>
                <h1 id="trial-week-title" class="mt-3 max-w-3xl font-serif text-4xl font-black tracking-tight text-[#302722] sm:text-5xl">Zeven dagen spreken in Madrid</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-[#72645c]">Van een ontbijt bestellen tot een treinreis regelen. Elke dag heeft één duidelijke communicatieve missie; actieve reproductie blijft belangrijker dan meerkeuzeherkenning.</p>
            </div>

            <aside class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm" aria-labelledby="access-title" data-access-summary>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $status['tone'] }}">{{ $status['label'] }}</span>
                <h2 id="access-title" class="mt-4 text-xl font-black">{{ $status['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-[#76685f]">{{ $status['description'] }}</p>
                @if ($access['valid_until'])
                    <p class="mt-4 text-xs font-bold text-[#8a776c]">Geldig tot <time datetime="{{ $access['valid_until'] }}">{{ CarbonCarbonImmutable::parse($access['valid_until'])->format('d-m-Y H:i') }}</time></p>
                @endif
            </aside>
        </section>

        <section class="mt-10" aria-labelledby="days-title">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.15em] text-[#a9472b]">Missieroute</p>
                    <h2 id="days-title" class="mt-2 text-2xl font-black">Jouw zeven missiedagen</h2>
                </div>
                <p class="max-w-md text-sm leading-6 text-[#7a6b62]">‘In voorbereiding’ betekent dat je toegangsdag is bereikt, maar de leercontent nog niet veilig is gepubliceerd.</p>
            </div>

            <ol class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-trial-days>
                @foreach ($days as $day)
                    @php
                        $dayStatus = match ($day['access_state']) {
                            'completed' => ['label' => 'Voltooid', 'class' => 'bg-[#dfeedd] text-[#315d47]', 'symbol' => '✓'],
                            'available' => ['label' => 'Beschikbaar', 'class' => 'bg-[#f4dfc4] text-[#874626]', 'symbol' => '→'],
                            'planned' => ['label' => 'In voorbereiding', 'class' => 'bg-[#e7e4ef] text-[#5d5576]', 'symbol' => '…'],
                            'scheduled' => ['label' => 'Later deze week', 'class' => 'bg-[#e8e5df] text-[#70665e]', 'symbol' => (string) $day['day']],
                            default => ['label' => 'Extra toegang nodig', 'class' => 'bg-[#eee7de] text-[#76695f]', 'symbol' => '·'],
                        };
                    @endphp
                    <li class="flex min-h-64 flex-col rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm" data-trial-day="{{ $day['day'] }}" data-access-state="{{ $day['access_state'] }}">
                        <div class="flex items-start justify-between gap-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-[#a9472b] text-sm font-black text-white" aria-label="Dag {{ $day['day'] }}">{{ $dayStatus['symbol'] }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $dayStatus['class'] }}">{{ $dayStatus['label'] }}</span>
                        </div>
                        <p class="mt-5 text-xs font-black uppercase tracking-[0.13em] text-[#9a806f]">Dag {{ $day['day'] }} · {{ $day['setting'] }}</p>
                        <h3 lang="es" class="mt-2 text-xl font-black text-[#302722]">{{ $day['title_es'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#76685f]">{{ $day['title_nl'] }}</p>

                        <div class="mt-auto pt-5">
                            @if ($day['action_url'])
                                <a href="{{ $day['action_url'] }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#a9472b] px-4 text-sm font-black text-white hover:bg-[#913b25] focus:outline-none focus:ring-2 focus:ring-[#bd5a34] focus:ring-offset-2">
                                    {{ $day['access_state'] === 'completed' ? 'Speel opnieuw' : 'Start dag 1' }}
                                </a>
                            @else
                                <span class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-[#493429]/10 bg-white/60 px-4 text-center text-sm font-bold text-[#887970]" aria-disabled="true">Nog niet te starten</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <aside class="mt-8 rounded-3xl border border-[#315d47]/15 bg-[#edf4e9] p-6 text-sm leading-6 text-[#4f6656] sm:p-8" aria-labelledby="safe-access-title">
            <h2 id="safe-access-title" class="font-black text-[#315d47]">Veilige toegangsgrens</h2>
            <p class="mt-2">Rechten worden altijd server-side berekend uit de nieuwste geldige abonnementsprojectie. Deze pagina bevat geen kaartgegevens, providerreferenties, transcripties, audio of AI-feedback. Prijs, proefvoorwaarden en live betaling worden pas toegevoegd na jouw expliciete productbesluit.</p>
        </aside>
    </main>
</body>
</html>
