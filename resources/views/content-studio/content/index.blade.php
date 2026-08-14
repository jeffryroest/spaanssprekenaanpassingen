@extends('layouts.content-studio')

@section('title', 'Contentcatalogus')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="cs-eyebrow">Bibliotheek</p>
            <h1 class="cs-page-title">Contentcatalogus</h1>
            <p class="cs-page-description">Zoek en beheer redactionele content. Concepten zijn nooit via een publieke route beschikbaar.</p>
        </div>

        @can('create', App\Models\ContentNode::class)
            <a href="{{ route('content-studio.content.create') }}" class="cs-button-primary shrink-0">
                <x-content-studio.icon name="plus" class="size-4" />
                Nieuw concept
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('content-studio.content.index') }}" class="cs-panel mt-8 overflow-hidden" aria-label="Content filteren">
        <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-slate-900">Zoeken en filteren</h2>
                <p class="mt-1 text-sm text-slate-500">Verfijn de catalogus op inhoud, type of workflowstatus.</p>
            </div>
            @if (array_filter($filters))
                <a href="{{ route('content-studio.content.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Alle filters wissen</a>
            @endif
        </div>

        <div class="grid gap-5 p-5 md:grid-cols-2 xl:grid-cols-[minmax(18rem,2fr)_minmax(12rem,1fr)_minmax(12rem,1fr)_auto] xl:items-end sm:p-6">
            <div>
                <label for="search" class="cs-label">Zoeken</label>
                <div class="relative">
                    <x-content-studio.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Titel, tekst, slug of ID" class="cs-field pl-10">
                </div>
            </div>
            <div>
                <label for="content_type" class="cs-label">Contenttype</label>
                <select id="content_type" name="content_type" class="cs-field">
                    <option value="">Alle typen</option>
                    @foreach ($contentTypes as $contentType)
                        <option value="{{ $contentType->value }}" @selected(($filters['content_type'] ?? '') === $contentType->value)>{{ $contentType->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="cs-label">Status</label>
                <select id="status" name="status" class="cs-field">
                    <option value="">Alle statussen</option>
                    @foreach ($contentStatuses as $contentStatus)
                        <option value="{{ $contentStatus->value }}" @selected(($filters['status'] ?? '') === $contentStatus->value)>{{ $contentStatus->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="cs-button-secondary">
                <x-content-studio.icon name="search" class="size-4" />
                Filter toepassen
            </button>
        </div>
    </form>

    <section class="cs-panel mt-6 overflow-hidden" aria-labelledby="catalog-results-title">
        <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="catalog-results-title" class="font-bold text-slate-900">Content</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $contentNodes->total() }} {{ $contentNodes->total() === 1 ? 'resultaat' : 'resultaten' }}</p>
            </div>
            <span class="status-chip">Nieuwste wijziging eerst</span>
        </div>

        @if ($contentNodes->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                    <x-content-studio.icon name="search" class="size-6" />
                </span>
                <h3 class="mt-5 text-base font-bold text-slate-900">Geen content gevonden</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Pas de zoekopdracht of filters aan. Je kunt ook een nieuw concept aanmaken als je daarvoor bevoegd bent.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[52rem] w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500 sm:px-6">Content</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Type</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Versie</th>
                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500 sm:px-6">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($contentNodes as $contentNode)
                            @php($localization = $contentNode->defaultLocalization())
                            <tr class="transition hover:bg-brand-50/30">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500" aria-hidden="true">
                                            <x-content-studio.icon name="document" class="size-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('content-studio.content.show', $contentNode) }}" class="font-semibold text-slate-900 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ $localization?->title ?? 'Zonder titel' }}</a>
                                            <p class="mt-1 max-w-xs truncate text-xs text-slate-500">#{{ $contentNode->id }} · {{ $contentNode->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-600">{{ $contentNode->content_type->label() }}</td>
                                <td class="px-5 py-4"><x-content-studio.status-badge :status="$contentNode->status" /></td>
                                <td class="px-5 py-4 text-sm text-slate-600">v{{ $contentNode->current_version }}</td>
                                <td class="px-5 py-4 text-right text-sm sm:px-6">
                                    <a href="{{ route('content-studio.content.show', $contentNode) }}" class="font-semibold text-brand-700 hover:text-brand-800">Bekijken</a>
                                    @can('update', $contentNode)
                                        @if ($contentNode->isEditableDraft())
                                            <a href="{{ route('content-studio.content.edit', $contentNode) }}" class="ml-4 font-semibold text-slate-600 hover:text-slate-900">Bewerken</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($contentNodes->hasPages())
        <div class="mt-6">{{ $contentNodes->links() }}</div>
    @endif
@endsection
