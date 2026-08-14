<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Beveiligd dashboard van de Content Studio van Spaansspreken.nl.">
    <title>Dashboard · Content Studio · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <div class="min-h-screen">
        <header class="border-b border-white/10 bg-stone-950/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-5 lg:px-10">
                <div>
                    <a href="{{ route('content-studio.dashboard') }}" class="text-lg font-semibold text-white">
                        Spaansspreken<span class="text-orange-400">.nl</span>
                    </a>
                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-stone-400">Content Studio</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-stone-400">{{ auth()->user()->content_role->label() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                            Uitloggen
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-10 lg:px-10">
            <section aria-labelledby="dashboard-title">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">Fase 1A</p>
                <h1 id="dashboard-title" class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Content veilig beheren</h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-stone-300">De toegang, rollen en autorisatie staan klaar. Content blijft standaard concept en kan in deze eerste stap nog niet worden gepubliceerd.</p>
            </section>

            <section class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3" aria-label="Content Studio-onderdelen">
                @foreach ([
                    ['Contentcatalogus', 'Zoeken, filteren en relaties bekijken.', 'Volgende PR'],
                    ['Reviewwachtrij', 'Concepten beoordelen en wijzigingen aanvragen.', 'Volgende PR'],
                    ['Importcentrum', 'Externe bestanden veilig in staging verwerken.', 'Gepland'],
                    ['Releases', 'Goedgekeurde versies gecontroleerd publiceren.', 'Gepland'],
                    ['Auditlog', 'Wijzigingen en roltoewijzingen controleren.', 'Basis actief'],
                    ['Instellingen', 'Taxonomieën, rollen en checklists beheren.', 'Gepland'],
                ] as [$title, $description, $status])
                    <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-6">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-lg font-semibold text-white">{{ $title }}</h2>
                            <span class="rounded-full bg-white/[0.06] px-3 py-1 text-xs text-stone-300">{{ $status }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-stone-400">{{ $description }}</p>
                    </article>
                @endforeach
            </section>

            <aside class="mt-10 rounded-2xl border border-emerald-300/20 bg-emerald-300/10 p-6" aria-labelledby="access-title">
                <h2 id="access-title" class="font-semibold text-emerald-100">Toegang gecontroleerd</h2>
                <p class="mt-2 text-sm leading-6 text-emerald-100/80">Je bent ingelogd als {{ auth()->user()->content_role->label() }}. Bevoegdheden worden server-side afgedwongen.</p>
            </aside>
        </main>
    </div>
</body>
</html>
