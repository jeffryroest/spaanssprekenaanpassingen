@extends('layouts.content-studio')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="cs-eyebrow">Fase 1C.1 · Werkruimte</p>
            <h1 id="dashboard-title" class="cs-page-title">Welkom, {{ auth()->user()->name }}</h1>
            <p class="cs-page-description">Beheer conceptcontent vanuit één rustige, veilige werkruimte. Publiceren blijft geblokkeerd totdat de review- en releaseworkflow gereed is.</p>
        </div>

        @can('create', App\Models\ContentNode::class)
            <a href="{{ route('content-studio.content.create') }}" class="cs-button-primary shrink-0">
                <x-content-studio.icon name="plus" class="size-4" />
                Nieuw concept
            </a>
        @endcan
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Status van de Content Studio">
        @foreach ([
            ['document', '11', 'Contenttypen', 'Klaar voor concepten', 'bg-brand-50 text-brand-700'],
            ['catalog', '1', 'Actieve module', 'Contentcatalogus', 'bg-blue-50 text-blue-700'],
            ['shield', 'Server-side', 'Autorisatie', auth()->user()->content_role->label(), 'bg-emerald-50 text-emerald-700'],
            ['release', 'Geblokkeerd', 'Publicatie', 'Tot releaseworkflow', 'bg-violet-50 text-violet-700'],
        ] as [$icon, $value, $label, $detail, $color])
            <article class="cs-panel p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-xl font-bold tracking-tight text-slate-950">{{ $value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $detail }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-xl {{ $color }}" aria-hidden="true">
                        <x-content-studio.icon :name="$icon" />
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(20rem,1fr)]">
        <section class="cs-panel overflow-hidden" aria-labelledby="workspace-title">
            <div class="cs-panel-header flex items-center justify-between gap-4">
                <div>
                    <h2 id="workspace-title" class="font-bold text-slate-900">Contentworkflow</h2>
                    <p class="mt-1 text-sm text-slate-500">Beschikbare en geplande onderdelen</p>
                </div>
                <span class="status-chip">1 actief</span>
            </div>

            <div class="divide-y divide-slate-100">
                <a href="{{ route('content-studio.content.index') }}" class="group flex items-center gap-4 p-5 transition hover:bg-brand-50/50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:p-6">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700">
                        <x-content-studio.icon name="catalog" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-slate-900">Contentcatalogus</span>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-emerald-700">Actief</span>
                        </span>
                        <span class="mt-1 block text-sm text-slate-500">Zoeken, filteren, concepten beheren en revisies bekijken.</span>
                    </span>
                    <x-content-studio.icon name="arrow-right" class="size-5 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                </a>

                @foreach ([
                    ['review', 'Reviewwachtrij', 'Concepten beoordelen en wijzigingen aanvragen.', 'Volgende fase'],
                    ['import', 'Importcentrum', 'Externe bestanden gecontroleerd in staging verwerken.', 'Gepland'],
                    ['release', 'Releases', 'Goedgekeurde versies gecontroleerd publiceren.', 'Gepland'],
                ] as [$icon, $title, $description, $status])
                    <div class="flex items-center gap-4 p-5 sm:p-6">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500">
                            <x-content-studio.icon :name="$icon" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="font-semibold text-slate-700">{{ $title }}</span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $description }}</span>
                        </span>
                        <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 sm:inline-flex">{{ $status }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="space-y-6">
            <aside class="cs-panel overflow-hidden" aria-labelledby="security-title">
                <div class="bg-studio-900 p-6 text-white">
                    <span class="grid size-11 place-items-center rounded-xl bg-emerald-400/15 text-emerald-300">
                        <x-content-studio.icon name="shield" />
                    </span>
                    <h2 id="security-title" class="mt-5 text-lg font-bold">Toegang gecontroleerd</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Je werkt als <strong class="text-white">{{ auth()->user()->content_role->label() }}</strong>. Alle bevoegdheden worden ook op de server afgedwongen.</p>
                </div>
                <div class="flex items-center gap-3 border-t border-slate-100 bg-white px-6 py-4 text-sm text-slate-600">
                    <span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    Beveiligde sessie actief
                </div>
            </aside>

            <aside class="cs-panel p-6" aria-labelledby="next-step-title">
                <p class="cs-eyebrow">Blauwdruk</p>
                <h2 id="next-step-title" class="mt-2 font-bold text-slate-900">Volgende bouwstap</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Na deze visuele basis volgt de reviewworkflow, zonder de bestaande concept- en revisieveiligheid te doorbreken.</p>
            </aside>
        </div>
    </div>
@endsection
