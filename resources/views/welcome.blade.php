<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Spaansspreken.nl wordt een interactieve Spaanse wereld waarin je leert spreken door te doen.">
    <title>Spaansspreken.nl · Interactieve Spaanse webgame</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <main class="relative isolate min-h-screen overflow-hidden">
        <div aria-hidden="true" class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(234,88,12,0.34),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(30,64,175,0.28),_transparent_38%)]"></div>

        <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-8 sm:px-10 lg:px-12">
            <header class="flex items-center justify-between gap-6 border-b border-white/10 pb-6">
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-white">
                    Spaansspreken<span class="text-orange-400">.nl</span>
                </a>
                <div class="flex items-center gap-3">
                    <span class="hidden rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-medium text-emerald-200 sm:inline-flex">
                        Fundament actief
                    </span>
                    @auth
                        <a href="{{ route('trial-week.show') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                            Mijn proefweek
                        </a>
                        <a href="{{ route('player.progress') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                            Mijn voortgang
                        </a>
                        @can('content-studio.view')
                            <a href="{{ route('content-studio.dashboard') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                                Content Studio
                            </a>
                        @endcan
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                            Inloggen
                        </a>
                    @endauth
                </div>
            </header>

            <section class="grid flex-1 items-center gap-12 py-16 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <p class="mb-5 text-sm font-semibold uppercase tracking-[0.2em] text-orange-300">Madrid · La panadería</p>
                    <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-white sm:text-6xl">
                        Leer Spaans door het echt te <span class="text-orange-400">spreken.</span>
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-stone-300">
                        Stap een levendige Madrileense buurt in, ontdek je eerste woorden en vind de bakkerij waar jouw eerste spreekmissie begint.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('game.madrid') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 font-semibold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2 focus:ring-offset-stone-950">
                            Start in Madrid
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="mt-9 flex flex-wrap gap-3" aria-label="Technische basis">
                        <span class="status-chip">Laravel 13</span>
                        <span class="status-chip">PHP 8.4</span>
                        <span class="status-chip">Tailwind CSS 4</span>
                        <span class="status-chip">WebM-audio</span>
                    </div>
                </div>

                <aside class="rounded-3xl border border-white/10 bg-white/[0.06] p-7 shadow-2xl shadow-black/30 backdrop-blur sm:p-9" aria-labelledby="status-title">
                    <p class="text-sm font-medium text-orange-300">Fase 3B1</p>
                    <h2 id="status-title" class="mt-2 text-2xl font-semibold text-white">Dag 2 brengt je met Diego door Madrid</h2>
                    <ul class="mt-6 space-y-4 text-sm leading-6 text-stone-300">
                        <li class="status-item">Vertel waar je heen wilt en reageer op de route</li>
                        <li class="status-item">Spreek of formuleer ieder antwoord zelf</li>
                        <li class="status-item">Gebruik dezelfde veilige transcriptie en feedback</li>
                        <li class="status-item">Verdien een taxistempel en Madrileense taxibon</li>
                    </ul>
                    <div class="mt-8 border-t border-white/10 pt-6">
                        <p class="text-sm text-stone-400">Volgende mijlpaal</p>
                        <p class="mt-1 font-medium text-white">Fase 3B2 · restaurantmissie</p>
                    </div>
                </aside>
            </section>

            <footer class="border-t border-white/10 pt-6 text-sm text-stone-500">
                Eerst één uitzonderlijk goede vertical slice. Daarna gecontroleerd uitbreiden.
            </footer>
        </div>
    </main>
</body>
</html>
