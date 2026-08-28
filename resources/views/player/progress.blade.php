<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Bekijk je blijvende voortgang, missies en beloningen op Spaansspreken.nl.">
    <title>Mijn voortgang · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f1e7] text-[#302722] antialiased">
    <a href="#progress-content" class="hub-skip-link">Ga naar je voortgang</a>

    <header class="border-b border-[#493429]/10 bg-[#fffaf0]/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl font-black tracking-tight text-[#302722] focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">
                <span class="grid size-10 place-items-center rounded-xl bg-[#a9472b] text-white" aria-hidden="true">S</span>
                <span>Spaansspreken<span class="text-[#bd5a34]">.nl</span></span>
            </a>

            <nav class="flex flex-wrap items-center gap-2" aria-label="Accountnavigatie">
                <a href="{{ route('trial-week.show') }}" class="inline-flex min-h-11 items-center rounded-xl border border-[#493429]/15 bg-white px-4 text-sm font-bold text-[#60483c] hover:border-[#bd5a34]/40 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Mijn proefweek</a>
                <a href="{{ route('game.madrid') }}" class="inline-flex min-h-11 items-center rounded-xl border border-[#493429]/15 bg-white px-4 text-sm font-bold text-[#60483c] hover:border-[#bd5a34]/40 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Naar Madrid</a>
                @can('content-studio.view')
                    <a href="{{ route('content-studio.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-[#493429]/15 bg-white px-4 text-sm font-bold text-[#60483c] hover:border-[#bd5a34]/40 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Content Studio</a>
                @endcan
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-bold text-[#78685e] hover:bg-[#493429]/5 focus:outline-none focus:ring-2 focus:ring-[#bd5a34]">Uitloggen</button>
                </form>
            </nav>
        </div>
    </header>

    <main id="progress-content" class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
        <section class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end" aria-labelledby="progress-title">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[#a9472b]">Mi progreso</p>
                <h1 id="progress-title" class="mt-3 font-serif text-4xl font-black tracking-tight text-[#302722] sm:text-5xl">Hola, {{ auth()->user()->name }}</h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-[#72645c]">Je voortgang en beloningen blijven bij je account, ook wanneer je later op een ander apparaat verdergaat.</p>
            </div>
            <a href="{{ route('game.madrid.panaderia') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#a9472b] px-5 font-black text-white shadow-lg shadow-[#71301f]/20 hover:bg-[#913b25] focus:outline-none focus:ring-2 focus:ring-[#bd5a34] focus:ring-offset-2">
                {{ $progress['mission']['status'] === 'completed' ? 'Oefen opnieuw met Lucía' : 'Start je eerste missie' }}
                <span aria-hidden="true">→</span>
            </a>
        </section>

        <dl class="mt-8 grid gap-4 sm:grid-cols-3" aria-label="Accountbalansen">
            @foreach ([['XP', $progress['balances']['xp'], 'Ervaringspunten'], ['Confianza', $progress['balances']['confianza'], 'Gesproken vertrouwen'], ['Valentía', $progress['balances']['valentia'], 'Moed om door te zetten']] as [$label, $value, $description])
                <div class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm">
                    <dt class="text-xs font-black uppercase tracking-[0.13em] text-[#8a776c]">{{ $label }}</dt>
                    <dd class="mt-2 text-4xl font-black text-[#302722]">{{ $value }}</dd>
                    <p class="mt-2 text-sm text-[#786a61]">{{ $description }}</p>
                </div>
            @endforeach
        </dl>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <section class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-8" aria-labelledby="mission-progress-title">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.15em] text-[#a9472b]">Madrid · La Espiga</p>
                        <h2 id="mission-progress-title" class="mt-2 text-2xl font-black">Het eerste ontbijt</h2>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $progress['mission']['status'] === 'completed' ? 'bg-[#dfeedd] text-[#315d47]' : 'bg-[#efe4d5] text-[#7b6558]' }}">
                        {{ $progress['mission']['status'] === 'completed' ? 'Voltooid' : 'Nog te spelen' }}
                    </span>
                </div>

                <div class="mt-7 rounded-2xl border border-[#315d47]/15 bg-[#edf4e9] p-5">
                    <div class="flex items-center justify-between gap-4">
                        <strong class="text-sm text-[#315d47]">Spreekdoel</strong>
                        <span class="text-sm font-black text-[#315d47]">{{ min($progress['mission']['best_spoken_turns'], $progress['mission']['spoken_goal_target']) }}/{{ $progress['mission']['spoken_goal_target'] }}</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#315d47]/10" aria-hidden="true">
                        <span class="block h-full rounded-full bg-[#4c8b65]" style="width: {{ min(100, ($progress['mission']['best_spoken_turns'] / max(1, $progress['mission']['spoken_goal_target'])) * 100) }}%"></span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-[#5c6e60]">
                        {{ $progress['mission']['spoken_goal_completed'] ? 'Spreekdoel behaald. Je hebt minstens drie beurten hardop gedaan.' : 'Tekst kan de dialoog afronden; alleen gesproken beurten bouwen Confianza op.' }}
                    </p>
                </div>

                <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Voltooiingen</dt><dd class="mt-1 text-xl font-black">{{ $progress['mission']['completion_count'] }}</dd></div>
                    <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Beste score</dt><dd class="mt-1 text-xl font-black">{{ $progress['mission']['best_xp'] }} XP</dd></div>
                    <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Ontgrendeld</dt><dd class="mt-1 text-xl font-black">{{ count($progress['mission']['states']) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-8" aria-labelledby="rewards-title">
                <p class="text-xs font-black uppercase tracking-[0.15em] text-[#a9472b]">Paspoort en verzameling</p>
                <h2 id="rewards-title" class="mt-2 text-2xl font-black">Jouw beloningen</h2>

                @if (count($progress['rewards']) === 0)
                    <div class="mt-6 rounded-2xl border border-dashed border-[#493429]/20 bg-white/60 p-6 text-center">
                        <span class="text-3xl" aria-hidden="true">◇</span>
                        <p class="mt-3 text-sm leading-6 text-[#75675e]">Rond je bestelling bij La Espiga af om je eerste stempel en verzamelitem te verdienen.</p>
                    </div>
                @else
                    <ul class="mt-6 grid gap-3">
                        @foreach ($progress['rewards'] as $reward)
                            <li class="flex items-center gap-4 rounded-2xl border border-[#493429]/10 bg-white p-4">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-[#f1e4d2] text-xl text-[#a9472b]" aria-hidden="true">{{ $reward['type'] === 'passport_stamp' ? '▣' : ($reward['type'] === 'badge' ? '★' : ($reward['type'] === 'unlock' ? '→' : '◇')) }}</span>
                                <div class="min-w-0">
                                    <strong lang="es" class="block truncate text-sm text-[#3b302a]">{{ $reward['title']['es'] }}</strong>
                                    <span class="mt-1 block text-xs text-[#7b6d64]">{{ $reward['title']['nl'] }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <section class="mt-6 rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-8" aria-labelledby="taxi-progress-title">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.15em] text-[#a9472b]">Dag 2 · Madrid · En taxi</p>
                    <h2 id="taxi-progress-title" class="mt-2 text-2xl font-black">Mijn eerste taxirit</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#76685f]">Vertel Diego je bestemming, reageer op een routewijziging, vraag naar de prijs en rond de rit af.</p>
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $taxiProgress['mission']['status'] === 'completed' ? 'bg-[#dfeedd] text-[#315d47]' : 'bg-[#efe4d5] text-[#7b6558]' }}">
                    {{ $taxiProgress['mission']['status'] === 'completed' ? 'Voltooid' : 'Nog te spelen' }}
                </span>
            </div>

            <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Voltooiingen</dt><dd class="mt-1 text-xl font-black">{{ $taxiProgress['mission']['completion_count'] }}</dd></div>
                <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Beste score</dt><dd class="mt-1 text-xl font-black">{{ $taxiProgress['mission']['best_xp'] }} XP</dd></div>
                <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Gesproken</dt><dd class="mt-1 text-xl font-black">{{ min($taxiProgress['mission']['best_spoken_turns'], $taxiProgress['mission']['spoken_goal_target']) }}/{{ $taxiProgress['mission']['spoken_goal_target'] }}</dd></div>
            </dl>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <a href="{{ route('game.madrid.taxi') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#a9472b] px-5 text-sm font-black text-white hover:bg-[#913b25] focus:outline-none focus:ring-2 focus:ring-[#bd5a34] focus:ring-offset-2">
                    {{ $taxiProgress['mission']['status'] === 'completed' ? 'Speel de taxirit opnieuw' : 'Open dag 2' }}
                </a>
                @foreach ($taxiProgress['rewards'] as $reward)
                    <span class="rounded-full border border-[#493429]/10 bg-white px-3 py-2 text-xs font-bold text-[#6f5f56]">{{ $reward['title']['nl'] }}</span>
                @endforeach
            </div>
        </section>

        <section class="mt-6 rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-8" aria-labelledby="restaurant-progress-title">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.15em] text-[#a9472b]">Dag 3 · Madrid · Café El Reloj</p>
                    <h2 id="restaurant-progress-title" class="mt-2 text-2xl font-black">Mijn eerste diner</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#76685f]">Vraag Carmen om een tafel, bestel drinken en eten, los een onverwachte vraag op en vraag om de rekening.</p>
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $restaurantProgress['mission']['status'] === 'completed' ? 'bg-[#dfeedd] text-[#315d47]' : 'bg-[#efe4d5] text-[#7b6558]' }}">
                    {{ $restaurantProgress['mission']['status'] === 'completed' ? 'Voltooid' : 'Nog te spelen' }}
                </span>
            </div>

            <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Voltooiingen</dt><dd class="mt-1 text-xl font-black">{{ $restaurantProgress['mission']['completion_count'] }}</dd></div>
                <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Beste score</dt><dd class="mt-1 text-xl font-black">{{ $restaurantProgress['mission']['best_xp'] }} XP</dd></div>
                <div class="rounded-2xl bg-[#f3e8d9] p-4"><dt class="text-xs font-bold text-[#806e63]">Gesproken</dt><dd class="mt-1 text-xl font-black">{{ min($restaurantProgress['mission']['best_spoken_turns'], $restaurantProgress['mission']['spoken_goal_target']) }}/{{ $restaurantProgress['mission']['spoken_goal_target'] }}</dd></div>
            </dl>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <a href="{{ route('game.madrid.restaurant') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#a9472b] px-5 text-sm font-black text-white hover:bg-[#913b25] focus:outline-none focus:ring-2 focus:ring-[#bd5a34] focus:ring-offset-2">
                    {{ $restaurantProgress['mission']['status'] === 'completed' ? 'Speel het diner opnieuw' : 'Open dag 3' }}
                </a>
                @foreach ($restaurantProgress['rewards'] as $reward)
                    <span class="rounded-full border border-[#493429]/10 bg-white px-3 py-2 text-xs font-bold text-[#6f5f56]">{{ $reward['title']['nl'] }}</span>
                @endforeach
            </div>
        </section>

        <p class="mt-8 text-center text-xs leading-5 text-[#8a7b72]">Voor voortgang bewaren we alleen missiestappen, invoerbron, hulpgebruik en beloningsmutaties — geen opname, transcript of AI-feedback.</p>
    </main>
</body>
</html>
