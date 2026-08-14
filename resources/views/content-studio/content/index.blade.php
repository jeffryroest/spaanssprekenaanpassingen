@extends('layouts.content-studio')

@section('title', 'Contentcatalogus')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">Content Studio</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">Contentcatalogus</h1>
            <p class="mt-3 max-w-2xl text-stone-300">Zoek en beheer redactionele content. Geen enkel concept is via een publieke route beschikbaar.</p>
        </div>

        @can('create', App\Models\ContentNode::class)
            <a href="{{ route('content-studio.content.create') }}" class="rounded-xl bg-orange-400 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2 focus:ring-offset-stone-950">
                Nieuw concept
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('content-studio.content.index') }}" class="mt-8 grid gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-5 md:grid-cols-4" aria-label="Content filteren">
        <div class="md:col-span-2">
            <label for="search" class="block text-sm font-medium text-stone-200">Zoeken</label>
            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Titel, tekst, slug of ID" class="mt-2 w-full rounded-xl border border-white/10 bg-stone-900 px-4 py-3 text-white placeholder:text-stone-500 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/30">
        </div>
        <div>
            <label for="content_type" class="block text-sm font-medium text-stone-200">Contenttype</label>
            <select id="content_type" name="content_type" class="mt-2 w-full rounded-xl border border-white/10 bg-stone-900 px-4 py-3 text-white focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/30">
                <option value="">Alle typen</option>
                @foreach ($contentTypes as $contentType)
                    <option value="{{ $contentType->value }}" @selected(($filters['content_type'] ?? '') === $contentType->value)>{{ $contentType->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-stone-200">Status</label>
            <select id="status" name="status" class="mt-2 w-full rounded-xl border border-white/10 bg-stone-900 px-4 py-3 text-white focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/30">
                <option value="">Alle statussen</option>
                @foreach ($contentStatuses as $contentStatus)
                    <option value="{{ $contentStatus->value }}" @selected(($filters['status'] ?? '') === $contentStatus->value)>{{ $contentStatus->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap gap-3 md:col-span-4">
            <button type="submit" class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-stone-950 hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-orange-400">Filter toepassen</button>
            <a href="{{ route('content-studio.content.index') }}" class="rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-stone-300 hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">Wissen</a>
        </div>
    </form>

    <div class="mt-8 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
        @if ($contentNodes->isEmpty())
            <div class="px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-white">Geen content gevonden</h2>
                <p class="mt-2 text-sm text-stone-400">Pas de filters aan of maak een nieuw concept.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                    <thead class="bg-white/[0.04]">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-400">Content</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-400">Type</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-400">Status</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-400">Versie</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-400">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach ($contentNodes as $contentNode)
                            @php($localization = $contentNode->defaultLocalization())
                            <tr class="hover:bg-white/[0.03]">
                                <td class="px-5 py-4">
                                    <a href="{{ route('content-studio.content.show', $contentNode) }}" class="font-medium text-white hover:text-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-400">{{ $localization?->title ?? 'Zonder titel' }}</a>
                                    <p class="mt-1 text-xs text-stone-500">#{{ $contentNode->id }} · {{ $contentNode->slug }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-stone-300">{{ $contentNode->content_type->label() }}</td>
                                <td class="px-5 py-4"><span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs text-stone-200">{{ $contentNode->status->label() }}</span></td>
                                <td class="px-5 py-4 text-sm text-stone-300">{{ $contentNode->current_version }}</td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <a href="{{ route('content-studio.content.show', $contentNode) }}" class="font-medium text-orange-300 hover:text-orange-200">Bekijken</a>
                                    @can('update', $contentNode)
                                        @if ($contentNode->isEditableDraft())
                                            <a href="{{ route('content-studio.content.edit', $contentNode) }}" class="ml-4 font-medium text-stone-300 hover:text-white">Bewerken</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($contentNodes->hasPages())
        <div class="mt-6">{{ $contentNodes->links() }}</div>
    @endif
@endsection
