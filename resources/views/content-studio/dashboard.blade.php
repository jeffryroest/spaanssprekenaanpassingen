@extends('layouts.content-studio')

@section('title', 'Dashboard · Content Studio')

@section('content')
    <section aria-labelledby="dashboard-title">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">Fase 1C</p>
        <h1 id="dashboard-title" class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Content veilig beheren</h1>
        <p class="mt-4 max-w-3xl text-base leading-7 text-stone-300">Conceptcontent kan nu worden gezocht, aangemaakt, bekeken, versieerbaar bewerkt en veilig gearchiveerd. Publiceren blijft geblokkeerd tot de review- en releaseworkflow gereed is.</p>
    </section>

    <section class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3" aria-label="Content Studio-onderdelen">
        <a href="{{ route('content-studio.content.index') }}" class="rounded-2xl border border-orange-300/30 bg-orange-300/10 p-6 transition hover:border-orange-300/50 focus:outline-none focus:ring-2 focus:ring-orange-400">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-semibold text-white">Contentcatalogus</h2>
                <span class="rounded-full bg-orange-300/15 px-3 py-1 text-xs text-orange-100">Actief</span>
            </div>
            <p class="mt-3 text-sm leading-6 text-stone-300">Zoeken, filteren, concepten beheren en revisies bekijken.</p>
        </a>

        @foreach ([
            ['Reviewwachtrij', 'Concepten beoordelen en wijzigingen aanvragen.', 'Volgende fase'],
            ['Importcentrum', 'Externe bestanden veilig in staging verwerken.', 'Gepland'],
            ['Releases', 'Goedgekeurde versies gecontroleerd publiceren.', 'Gepland'],
            ['Auditlog', 'Wijzigingen en roltoewijzingen controleren.', 'Registratie actief'],
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
@endsection
