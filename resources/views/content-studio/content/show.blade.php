@extends('layouts.content-studio')

@section('title', $contentNode->defaultLocalization()?->title ?? 'Content bekijken')

@section('content')
    @php
        $localization = $contentNode->defaultLocalization();
        $currentRevision = $contentNode->revisions->firstWhere('version', $contentNode->current_version);
        $isOwnCurrentRevision = (int) $currentRevision?->created_by === (int) auth()->id();
        $domainData = data_get($currentRevision?->snapshot, 'domain_data', []);
    @endphp

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

        <div class="flex flex-wrap gap-3">
            @if ($previewUrl)
                <a href="{{ $previewUrl }}" class="cs-button-secondary shrink-0" target="_blank" rel="noopener">
                    <x-content-studio.icon name="preview" class="size-4" />
                    Spelpreview
                </a>
            @endif
            @can('update', $contentNode)
                @if ($contentNode->isEditableDraft())
                    <a href="{{ route('content-studio.content.edit', $contentNode) }}" class="cs-button-primary shrink-0">
                        <x-content-studio.icon name="edit" class="size-4" />
                        Bewerken
                    </a>
                @endif
            @endcan
        </div>
    </div>

    @if ($domainData !== [])
        <section class="cs-panel mt-6 overflow-hidden" aria-labelledby="play-data-title">
            <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="play-data-title" class="font-bold text-slate-900">Speeldata</h2>
                    <p class="mt-1 text-sm text-slate-500">Exact JSON-snapshot van revisie {{ $contentNode->current_version }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (data_get($domainData, 'scene'))
                        <span class="status-chip">{{ data_get($domainData, 'scene') }}</span>
                    @endif
                    @if (data_get($domainData, 'schema_version'))
                        <span class="status-chip">contract {{ data_get($domainData, 'schema_version') }}</span>
                    @endif
                </div>
            </div>
            <details class="group">
                <summary class="cursor-pointer list-none px-5 py-4 text-sm font-bold text-brand-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:px-6">
                    <span class="group-open:hidden">JSON bekijken</span>
                    <span class="hidden group-open:inline">JSON sluiten</span>
                </summary>
                <pre class="max-h-[42rem] overflow-auto border-t border-slate-200 bg-slate-950 p-5 text-xs leading-5 text-slate-100 sm:p-6"><code>{{ json_encode($domainData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </details>
        </section>
    @endif

    @if($currentRevision?->mediaAssets->isNotEmpty())
        <section class="cs-panel mt-6 overflow-hidden" aria-labelledby="linked-media-title">
            <div class="cs-panel-header">
                <h2 id="linked-media-title" class="font-bold text-slate-900">Media in deze revisie</h2>
                <p class="mt-1 text-sm text-slate-500">Versiegebonden scène- en personagemedia</p>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3 sm:p-6">
                @foreach($currentRevision->mediaAssets as $asset)
                    <article class="overflow-hidden rounded-xl border border-slate-200">
                        @if($asset->kind === App\Enums\MediaKind::Image)
                            <img src="{{ route('content-studio.media.stream', $asset) }}" alt="{{ $asset->alt_text }}" class="aspect-[16/9] w-full object-cover">
                        @else
                            <div class="bg-slate-100 p-4"><audio controls preload="none" class="w-full"><source src="{{ route('content-studio.media.stream', $asset) }}" type="{{ $asset->mime_type }}"></audio></div>
                        @endif
                        <div class="p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-violet-700">{{ str_replace('_', ' ', $asset->pivot->role) }}</p>
                            <p class="mt-1 font-bold text-slate-900">{{ $asset->title }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

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

    @if ($contentNode->status === App\Enums\ContentStatus::InReview && ($isOwnCurrentRevision || auth()->user()->can('content-studio.review')))
        <section class="mt-6 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50/60" aria-labelledby="review-decision-title">
            <div class="flex items-start gap-3 border-b border-blue-100 p-5 sm:p-6">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-700"><x-content-studio.icon name="review" /></span>
                <div>
                    <h2 id="review-decision-title" class="font-bold text-blue-950">Revisie {{ $contentNode->current_version }} beoordelen</h2>
                    <p class="mt-1 text-sm leading-6 text-blue-800">Controleer taal, didactiek, speelbaarheid en culturele juistheid. De beslissing geldt alleen voor deze onveranderlijke revisie.</p>
                </div>
            </div>

            @if ($isOwnCurrentRevision)
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="rounded-xl border {{ $allowsSelfApproval ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900' }} p-4 text-sm" role="note">
                        <p class="font-bold">{{ $allowsSelfApproval ? 'Gemotiveerde zelfgoedkeuring toegestaan' : 'Onafhankelijke reviewer vereist' }}</p>
                        <p class="mt-1 leading-6">
                            @if ($allowsSelfApproval)
                                Deze revisie valt onder het risicogestuurde beleid. Als beheerder of hoofdredacteur mag je haar na controle zelf goedkeuren; de motivatie en zelfgoedkeuring worden apart gelogd.
                            @elseif ($requiresIndependentReviewer)
                                Deze revisie bevat gevoelige inhoud en houdt daarom altijd de vier-ogencontrole.
                            @else
                                De huidige reviewinstelling vereist voor deze revisie een tweede beoordelaar.
                            @endif
                        </p>
                    </div>

                    @if ($allowsSelfApproval)
                        @can('content-studio.approve')
                            <form method="POST" action="{{ route('content-studio.reviews.decide', $contentNode) }}">
                                @csrf
                                <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                                <input type="hidden" name="action" value="{{ App\Enums\ContentReviewAction::Approved->value }}">
                                <label for="self-review-note" class="cs-label text-blue-950">Controle en motivatie <span class="text-red-600" aria-hidden="true">*</span></label>
                                <textarea id="self-review-note" name="note" rows="4" required minlength="3" maxlength="1000" class="cs-field border-blue-200 focus:border-blue-500 focus:ring-blue-500/10" aria-describedby="self-review-note-help">{{ old('note') }}</textarea>
                                <p id="self-review-note-help" class="cs-help text-blue-700">Noem concreet welke inhoudelijke en technische controles je hebt uitgevoerd.</p>
                                <div class="mt-5 flex justify-end">
                                    <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                        <x-content-studio.icon name="review" class="size-4" />
                                        Gemotiveerd goedkeuren
                                    </button>
                                </div>
                            </form>
                        @endcan
                    @endif

                    @can('update', $contentNode)
                        <details class="rounded-xl border border-slate-200 bg-white">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-slate-700">Review intrekken en verder bewerken</summary>
                            <form method="POST" action="{{ route('content-studio.content.withdraw-review', $contentNode) }}" class="border-t border-slate-200 p-4">
                                @csrf
                                <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                                <label for="withdraw-reason" class="cs-label">Reden <span class="text-red-600" aria-hidden="true">*</span></label>
                                <textarea id="withdraw-reason" name="reason" rows="3" required minlength="3" maxlength="1000" class="cs-field" aria-describedby="withdraw-reason-help">{{ old('reason') }}</textarea>
                                <p id="withdraw-reason-help" class="cs-help">De intrekking blijft zichtbaar in de audit- en reviewgeschiedenis.</p>
                                <div class="mt-4 flex justify-end">
                                    <button type="submit" class="cs-button-secondary">Review intrekken</button>
                                </div>
                            </form>
                        </details>
                    @endcan
                </div>
            @else
                @can('content-studio.review')
                    <form method="POST" action="{{ route('content-studio.reviews.decide', $contentNode) }}" class="p-5 sm:p-6">
                        @csrf
                        <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                        <div>
                            <label for="review-note" class="cs-label text-blue-950">Motivatie <span class="text-red-600" aria-hidden="true">*</span></label>
                            <textarea id="review-note" name="note" rows="4" required minlength="3" maxlength="1000" class="cs-field border-blue-200 focus:border-blue-500 focus:ring-blue-500/10" aria-describedby="review-note-help @error('note') review-note-error @enderror">{{ old('note') }}</textarea>
                            <p id="review-note-help" class="cs-help text-blue-700">Leg kort vast wat is gecontroleerd en waarom je deze beslissing neemt.</p>
                            @error('note')<p id="review-note-error" class="cs-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="submit" name="action" value="{{ App\Enums\ContentReviewAction::ChangesRequested->value }}" class="cs-button-secondary border-amber-300 text-amber-800 hover:bg-amber-50">
                                Wijzigingen aanvragen
                            </button>
                            @can('content-studio.approve')
                                <button type="submit" name="action" value="{{ App\Enums\ContentReviewAction::Approved->value }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                    <x-content-studio.icon name="review" class="size-4" />
                                    Versie goedkeuren
                                </button>
                            @endcan
                        </div>
                    </form>
                @endcan
            @endif
        </section>
    @endif

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
                @php($latestReviewForRevision = $contentNode->reviews->where('version', $revision->version)->last())
                <li class="flex gap-4 p-5 sm:p-6">
                    <span class="mt-1 grid size-9 shrink-0 place-items-center rounded-full {{ $revision->version === $contentNode->current_version ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500' }}">
                        <x-content-studio.icon name="clock" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-900">Versie {{ $revision->version }}</p>
                                <x-content-studio.status-badge :status="$latestReviewForRevision?->to_status ?? $revision->status" />
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

    @if ($contentNode->reviews->isNotEmpty())
        <section class="cs-panel mt-6 overflow-hidden" aria-labelledby="review-history-title">
            <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="review-history-title" class="font-bold text-slate-900">Reviewgeschiedenis</h2>
                    <p class="mt-1 text-sm text-slate-500">Append-only beslissingen per contentrevisie</p>
                </div>
                <span class="status-chip">{{ $contentNode->reviews->count() }} {{ $contentNode->reviews->count() === 1 ? 'gebeurtenis' : 'gebeurtenissen' }}</span>
            </div>
            <ol class="divide-y divide-slate-100">
                @foreach ($contentNode->reviews->reverse() as $review)
                    <li class="flex gap-4 p-5 sm:p-6">
                        <span class="mt-1 grid size-9 shrink-0 place-items-center rounded-full {{ match ($review->action) {
                            App\Enums\ContentReviewAction::Approved => 'bg-emerald-100 text-emerald-700',
                            App\Enums\ContentReviewAction::ChangesRequested => 'bg-amber-100 text-amber-800',
                            default => 'bg-blue-100 text-blue-700',
                        } }}">
                            <x-content-studio.icon name="review" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-slate-900">{{ $review->action->label() }}</p>
                                    <span class="text-xs font-semibold text-slate-500">versie {{ $review->version }}</span>
                                </div>
                                <time datetime="{{ $review->created_at->toAtomString() }}" class="text-xs font-medium text-slate-500">{{ $review->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</time>
                            </div>
                            @if ($review->note)
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $review->note }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-500">Door {{ $review->actor?->name ?? 'Onbekend' }} · {{ $review->actorRoleLabel() }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($contentNode->releaseItems->isNotEmpty())
        <section class="cs-panel mt-6 overflow-hidden" aria-labelledby="release-history-title">
            <div class="cs-panel-header">
                <h2 id="release-history-title" class="font-bold text-slate-900">Releasehistorie</h2>
                <p class="mt-1 text-sm text-slate-500">Releases waarin een exacte revisie van deze content is vastgelegd.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($contentNode->releaseItems->reverse() as $item)
                    <a href="{{ route('content-studio.releases.show', $item->release) }}" class="group flex items-center gap-4 p-5 transition hover:bg-violet-50/40 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-violet-500 sm:p-6">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700"><x-content-studio.icon name="release" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-slate-900">{{ $item->release->name }}</span>
                                <x-content-studio.status-badge :status="$item->release->status" />
                            </span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $item->release->target_channel->label() }} · revisie {{ $item->version }}</span>
                        </span>
                        <x-content-studio.icon name="arrow-right" class="size-5 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-violet-600" />
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @can('update', $contentNode)
        @if ($contentNode->status === App\Enums\ContentStatus::Draft)
            <section class="mt-6 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50/60" aria-labelledby="submit-review-title">
                <div class="flex items-start gap-3 border-b border-blue-100 p-5 sm:p-6">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-700"><x-content-studio.icon name="review" /></span>
                    <div>
                        <h2 id="submit-review-title" class="font-bold text-blue-950">Review aanvragen</h2>
                        <p class="mt-1 text-sm leading-6 text-blue-800">Versie {{ $contentNode->current_version }} wordt vergrendeld voor inhoudelijke beoordeling. Je kunt daarna pas weer bewerken als wijzigingen zijn gevraagd.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('content-studio.content.submit-review', $contentNode) }}" class="p-5 sm:p-6">
                    @csrf
                    <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                    <div>
                        <label for="submission-note" class="cs-label text-blue-950">Toelichting voor de reviewer <span class="font-normal text-blue-600">(optioneel)</span></label>
                        <textarea id="submission-note" name="note" rows="3" maxlength="1000" class="cs-field border-blue-200 focus:border-blue-500 focus:ring-blue-500/10" @error('note') aria-invalid="true" aria-describedby="submission-note-error" @enderror>{{ old('note') }}</textarea>
                        @error('note')<p id="submission-note-error" class="cs-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="cs-button-primary">
                            <x-content-studio.icon name="review" class="size-4" />
                            Versie indienen voor review
                        </button>
                    </div>
                </form>
            </section>
        @endif
    @endcan

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
