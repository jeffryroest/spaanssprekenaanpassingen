@extends('layouts.content-studio')

@section('title', 'Releases')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="cs-eyebrow">Versiegebonden publicatie</p>
            <h1 class="cs-page-title">Releases</h1>
            <p class="cs-page-description">Bundel uitsluitend goedgekeurde revisies en voer ze na preflight uit naar preview, staging of productie.</p>
        </div>
        @can('content-studio.publish')
            <a href="{{ route('content-studio.releases.create') }}" class="cs-button-primary shrink-0">
                <x-content-studio.icon name="plus" class="size-4" />
                Nieuwe release
            </a>
        @endcan
    </div>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="release-list-title">
        <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="release-list-title" class="font-bold text-slate-900">Releasehistorie</h2>
                <p class="mt-1 text-sm text-slate-500">Nieuwste release eerst</p>
            </div>
            <span class="status-chip">{{ $releases->total() }} totaal</span>
        </div>

        @if ($releases->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-violet-50 text-violet-700"><x-content-studio.icon name="release" class="size-6" /></span>
                <h3 class="mt-5 text-base font-bold text-slate-900">Nog geen releases</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Maak een conceptrelease zodra minimaal één contentrevisie is goedgekeurd.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($releases as $release)
                    <a href="{{ route('content-studio.releases.show', $release) }}" class="group flex flex-col gap-4 p-5 transition hover:bg-violet-50/40 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-violet-500 sm:p-6 lg:flex-row lg:items-center">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700"><x-content-studio.icon name="release" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-slate-900">{{ $release->name }}</span>
                                <x-content-studio.status-badge :status="$release->status" />
                            </span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $release->target_channel->label() }} · {{ $release->items_count }} {{ $release->items_count === 1 ? 'versie' : 'versies' }} · eigenaar {{ $release->owner?->name ?? 'Onbekend' }}</span>
                            <span class="mt-1 block text-xs text-slate-500">
                                @if ($release->published_at)
                                    Uitgevoerd {{ $release->published_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}
                                @elseif ($release->desired_publish_at)
                                    Gewenst {{ $release->desired_publish_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}
                                @else
                                    Nog niet gepland
                                @endif
                            </span>
                        </span>
                        <x-content-studio.icon name="arrow-right" class="size-5 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-violet-600" />
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if ($releases->hasPages())
        <div class="mt-6">{{ $releases->links() }}</div>
    @endif
@endsection
