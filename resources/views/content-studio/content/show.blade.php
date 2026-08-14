@extends('layouts.content-studio')

@section('title', $contentNode->defaultLocalization()?->title ?? 'Content bekijken')

@section('content')
    @php($localization = $contentNode->defaultLocalization())

    @if ($errors->any())
        <div class="mb-8 rounded-xl border border-red-300/20 bg-red-300/10 p-5 text-sm text-red-100" role="alert">
            <p class="font-semibold">De actie kon niet worden uitgevoerd.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-6">
        <div>
            <a href="{{ route('content-studio.content.index') }}" class="text-sm font-medium text-orange-300 hover:text-orange-200">← Terug naar de catalogus</a>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs text-stone-200">{{ $contentNode->status->label() }}</span>
                <span class="text-sm text-stone-400">{{ $contentNode->content_type->label() }} · versie {{ $contentNode->current_version }}</span>
            </div>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $localization?->title ?? 'Zonder titel' }}</h1>
            <p class="mt-2 text-sm text-stone-500">#{{ $contentNode->id }} · {{ $contentNode->slug }} · {{ $contentNode->default_locale }}</p>
        </div>

        @can('update', $contentNode)
            @if ($contentNode->isEditableDraft())
                <a href="{{ route('content-studio.content.edit', $contentNode) }}" class="rounded-xl bg-orange-400 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2 focus:ring-offset-stone-950">
                    Bewerken
                </a>
            @endif
        @endcan
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-6">
            <h2 class="text-lg font-semibold text-white">Inhoud</h2>
            @if ($localization?->summary)
                <p class="mt-4 text-base leading-7 text-stone-300">{{ $localization->summary }}</p>
            @endif
            @if ($localization?->body)
                <div class="mt-6 whitespace-pre-line text-sm leading-7 text-stone-300">{{ $localization->body }}</div>
            @else
                <p class="mt-4 text-sm text-stone-500">Er is nog geen uitgebreide inhoud ingevuld.</p>
            @endif
        </article>

        <aside class="rounded-2xl border border-white/10 bg-white/[0.04] p-6" aria-labelledby="metadata-title">
            <h2 id="metadata-title" class="text-lg font-semibold text-white">Metadata</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-stone-500">Aangemaakt door</dt>
                    <dd class="mt-1 text-stone-200">{{ $contentNode->creator?->name ?? 'Onbekend' }}</dd>
                </div>
                <div>
                    <dt class="text-stone-500">Laatst bijgewerkt door</dt>
                    <dd class="mt-1 text-stone-200">{{ $contentNode->updater?->name ?? 'Onbekend' }}</dd>
                </div>
                <div>
                    <dt class="text-stone-500">Laatst bijgewerkt</dt>
                    <dd class="mt-1 text-stone-200">{{ $contentNode->updated_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</dd>
                </div>
            </dl>
        </aside>
    </div>

    <section class="mt-8 rounded-2xl border border-white/10 bg-white/[0.04] p-6" aria-labelledby="revisions-title">
        <h2 id="revisions-title" class="text-lg font-semibold text-white">Revisiegeschiedenis</h2>
        <ol class="mt-5 space-y-4">
            @foreach ($contentNode->revisions->reverse() as $revision)
                <li class="rounded-xl border border-white/10 bg-stone-900/70 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-medium text-white">Versie {{ $revision->version }} · {{ $revision->status->value }}</p>
                        <time datetime="{{ $revision->created_at->toAtomString() }}" class="text-xs text-stone-500">{{ $revision->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</time>
                    </div>
                    <p class="mt-2 text-sm text-stone-300">{{ $revision->change_summary ?? 'Geen toelichting' }}</p>
                    <p class="mt-1 text-xs text-stone-500">Door {{ $revision->creator?->name ?? 'Onbekend' }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @can('delete', $contentNode)
        @if ($contentNode->isEditableDraft())
            <section class="mt-8 rounded-2xl border border-red-300/20 bg-red-300/5 p-6" aria-labelledby="archive-title">
                <h2 id="archive-title" class="font-semibold text-red-100">Concept archiveren</h2>
                <p class="mt-2 text-sm leading-6 text-red-100/70">Archiveren verwijdert niets. De statuswijziging, reden en revisiegeschiedenis blijven bewaard.</p>
                <form method="POST" action="{{ route('content-studio.content.destroy', $contentNode) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                    <div class="flex-1">
                        <label for="reason" class="block text-sm font-medium text-red-100">Reden</label>
                        <input id="reason" name="reason" type="text" required minlength="3" maxlength="480" value="{{ old('reason') }}" class="mt-2 w-full rounded-xl border border-red-300/20 bg-stone-900 px-4 py-3 text-white focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-300/30">
                        @error('reason')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="rounded-xl border border-red-300/30 px-5 py-3 text-sm font-semibold text-red-100 hover:bg-red-300/10 focus:outline-none focus:ring-2 focus:ring-red-300">Archiveren</button>
                </form>
            </section>
        @endif
    @endcan
@endsection
