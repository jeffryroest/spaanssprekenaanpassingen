@extends('layouts.content-studio')

@section('title', $contentNode->defaultLocalization()?->title ?? 'Content bekijken')

@section('content')
    @php($localization = $contentNode->defaultLocalization())

    @if ($errors->any())
        <div class="cs-alert-error mb-6" role="alert">
            <p class="font-bold">De actie kon niet worden uitgevoerd.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a href="{{ route('content-studio.content.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-brand-700">
        <x-content-studio.icon name="arrow-left" class="size-4" />
        Terug naar de catalogus
    </a>

    <div class="mt-6 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <x-content-studio.status-badge :status="$contentNode->status" />
                <span class="text-sm font-medium text-slate-500">{{ $contentNode->content_type->label() }} · versie {{ $contentNode->current_version }}</span>
            </div>
            <h1 class="mt-3 break-words text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl lg:text-4xl">{{ $localization?->title ?? 'Zonder titel' }}</h1>
            <p class="mt-2 break-all text-sm text-slate-500">#{{ $contentNode->id }} · {{ $contentNode->slug }} · {{ $contentNode->default_locale }}</p>
        </div>

        @can('update', $contentNode)
            @if ($contentNode->isEditableDraft())
                <a href="{{ route('content-studio.content.edit', $contentNode) }}" class="cs-button-primary shrink-0">
                    <x-content-studio.icon name="edit" class="size-4" />
                    Bewerken
                </a>
            @endif
        @endcan
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <article class="cs-panel overflow-hidden" aria-labelledby="content-title">
            <div class="cs-panel-header">
                <h2 id="content-title" class="font-bold text-slate-900">Inhoud</h2>
                <p class="mt-1 text-sm text-slate-500">Actuele inhoud van revisie {{ $contentNode->current_version }}</p>
            </div>
            <div class="p-5 sm:p-6">
                @if ($localization?->summary)
                    <div class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3.5 text-sm font-medium leading-6 text-brand-900">{{ $localization->summary }}</div>
                @endif
                @if ($localization?->body)
                    <div class="{{ $localization?->summary ? 'mt-6' : '' }} whitespace-pre-line text-sm leading-7 text-slate-700">{{ $localization->body }}</div>
                @else
                    <div class="py-8 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-400"><x-content-studio.icon name="document" /></span>
                        <p class="mt-4 text-sm text-slate-500">Er is nog geen uitgebreide inhoud ingevuld.</p>
                    </div>
                @endif
            </div>
        </article>

        <aside class="cs-panel self-start overflow-hidden" aria-labelledby="metadata-title">
            <div class="cs-panel-header">
                <h2 id="metadata-title" class="font-bold text-slate-900">Metadata</h2>
                <p class="mt-1 text-sm text-slate-500">Eigenaarschap en actualiteit</p>
            </div>
            <dl class="divide-y divide-slate-100 px-5 sm:px-6">
                <div class="py-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Aangemaakt door</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $contentNode->creator?->name ?? 'Onbekend' }}</dd>
                </div>
                <div class="py-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Laatst bijgewerkt door</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $contentNode->updater?->name ?? 'Onbekend' }}</dd>
                </div>
                <div class="py-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Laatst bijgewerkt</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $contentNode->updated_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</dd>
                </div>
            </dl>
        </aside>
    </div>

    <section class="cs-panel mt-6 overflow-hidden" aria-labelledby="revisions-title">
        <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="revisions-title" class="font-bold text-slate-900">Revisiegeschiedenis</h2>
                <p class="mt-1 text-sm text-slate-500">Alle snapshots blijven onveranderlijk bewaard.</p>
            </div>
            <span class="status-chip">{{ $contentNode->revisions->count() }} {{ $contentNode->revisions->count() === 1 ? 'revisie' : 'revisies' }}</span>
        </div>
        <ol class="divide-y divide-slate-100">
            @foreach ($contentNode->revisions->reverse() as $revision)
                <li class="flex gap-4 p-5 sm:p-6">
                    <span class="mt-1 grid size-9 shrink-0 place-items-center rounded-full {{ $revision->version === $contentNode->current_version ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500' }}">
                        <x-content-studio.icon name="clock" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-900">Versie {{ $revision->version }}</p>
                                <x-content-studio.status-badge :status="$revision->status" />
                            </div>
                            <time datetime="{{ $revision->created_at->toAtomString() }}" class="text-xs font-medium text-slate-500">{{ $revision->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</time>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $revision->change_summary ?? 'Geen toelichting' }}</p>
                        <p class="mt-1 text-xs text-slate-500">Door {{ $revision->creator?->name ?? 'Onbekend' }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    @can('delete', $contentNode)
        @if ($contentNode->isEditableDraft())
            <section class="mt-6 rounded-2xl border border-red-200 bg-red-50/70 p-5 sm:p-6" aria-labelledby="archive-title">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-red-100 text-red-700"><x-content-studio.icon name="archive" /></span>
                    <div>
                        <h2 id="archive-title" class="font-bold text-red-900">Concept archiveren</h2>
                        <p class="mt-1 text-sm leading-6 text-red-700">Archiveren verwijdert niets. De statuswijziging, reden en volledige revisiegeschiedenis blijven bewaard.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('content-studio.content.destroy', $contentNode) }}" class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-end">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                    <div class="flex-1">
                        <label for="reason" class="cs-label text-red-900">Reden voor archiveren</label>
                        <input id="reason" name="reason" type="text" required minlength="3" maxlength="480" value="{{ old('reason') }}" class="cs-field border-red-200 focus:border-red-500 focus:ring-red-500/10" @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>
                        @error('reason')<p id="reason-error" class="cs-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="cs-button-danger shrink-0">
                        <x-content-studio.icon name="archive" class="size-4" />
                        Archiveren
                    </button>
                </form>
            </section>
        @endif
    @endcan
@endsection
