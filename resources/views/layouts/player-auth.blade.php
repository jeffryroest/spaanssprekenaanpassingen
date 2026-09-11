<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description')">
    <meta name="theme-color" content="#172c36">
    <title>@yield('title') · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="player-auth-body">
    <a href="#auth-content" class="hub-skip-link">Direct naar het formulier</a>

    <main class="player-auth-shell">
        <section class="player-auth-scene" aria-labelledby="auth-world-title">
            <img src="{{ asset('images/game/madrid-morning.webp') }}" alt="" width="1672" height="941">
            <div aria-hidden="true"></div>
            <x-player.brand class="player-brand-on-dark" />
            <div class="player-auth-scene-copy">
                <p>Jouw Spaanse wereld</p>
                <h1 id="auth-world-title">Madrid onthoudt waar je bent.</h1>
                <p>Bewaar je missies, XP, Confianza, Valentía en paspoortstempels. Spreek vrij, krijg gerichte feedback en kom later terug waar je gebleven was.</p>
            </div>
            <ul aria-label="Wat bij je account hoort">
                <li><span aria-hidden="true">✓</span> Zeven interactieve missiedagen</li>
                <li><span aria-hidden="true">✓</span> Blijvende voortgang en beloningen</li>
                <li><span aria-hidden="true">✓</span> Spreken én een tekstalternatief</li>
            </ul>
        </section>

        <section id="auth-content" class="player-auth-panel" aria-labelledby="auth-title">
            <div class="w-full max-w-md">
                <x-player.brand class="mb-10 lg:hidden" />

                @yield('content')

                <footer class="mt-10 flex flex-wrap gap-x-5 gap-y-2 border-t border-[#493429]/10 pt-5 text-xs font-semibold text-[#75675e]">
                    <a href="{{ config('app.marketing_url') }}" class="underline decoration-[#d57137]/40 underline-offset-4 hover:text-[#a8432b]">Naar Spaansspreken.nl</a>
                    <a href="{{ route('privacy') }}" class="underline decoration-[#d57137]/40 underline-offset-4 hover:text-[#a8432b]">Privacy</a>
                </footer>
            </div>
        </section>
    </main>
</body>
</html>
