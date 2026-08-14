@extends('layouts.content-studio')

@section('title', 'Reviewwachtrij')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="cs-eyebrow">Vier-ogencontrole</p>
            <h1 class="cs-page-title">Reviewwachtrij</h1>
            <p class="cs-page-description">Beoordeel uitsluitend de ingediende revisie. Goedkeuren of terugsturen vereist altijd een gemotiveerde beslissing.</p>
        </div>
        <span class="status-chip shrink-0">{{ $contentNodes->total() }} {{ $contentNodes->total() === 1 ? 'open verzoek' : 'open verzoeken' }}</span>
    </div>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="review-list-title">
        <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="review-list-title" class="font-bold text-slate-900">Te beoordelen versies</h2>
                <p class="mt-1 text-sm text-slate-500">Oudste aanvraag eerst</p>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <span class="size-2 rounded-full bg-blue-500" aria-hidden="true"></span>
                In review
            </div>
        </div>

        @if ($contentNodes->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <x-content-studio.icon name="review" class="size-6" />
                </span>
                <h3 class="mt-5 text-base font-bold text-slate-900">De wachtrij is leeg</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Er zijn momenteel geen contentversies die op beoordeling wachten.</p>
                <a href="{{ route('content-studio.content.index') }}" class="cs-button-secondary mt-6">Naar de contentcatalogus</a>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($contentNodes as $contentNode)
                    @php
                        $localization = $contentNode->defaultLocalization();
                        $revision = $contentNode->revisions->last();
                        $submission = $contentNode->reviews->reverse()->first(
                            fn ($review) => $review->version === $contentNode->current_version
                                && $review->action === App\Enums\ContentReviewAction::Submitted,
                        );
                        $ownRevision = (int) $revision?->created_by === (int) auth()->id();
                    @endphp
                    <article class="flex flex-col gap-5 p-5 transition hover:bg-slate-50/80 sm:p-6 lg:flex-row lg:items-center">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                            <x-content-studio.icon name="document" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-slate-900">{{ $localization?->title ?? 'Zonder titel' }}</h3>
                                <x-content-studio.status-badge :status="$contentNode->status" />
                                @if ($ownRevision)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Eigen revisie</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $contentNode->content_type->label() }} · versie {{ $contentNode->current_version }} · #{{ $contentNode->id }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                Aangevraagd door {{ $submission?->actor?->name ?? 'Onbekend' }}
                                @if ($submission?->created_at)
                                    op {{ $submission->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}
                                @endif
                            </p>
                            @if ($submission?->note)
                                <p class="mt-2 line-clamp-2 text-sm italic text-slate-600">“{{ $submission->note }}”</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                            @if ($ownRevision)
                                <span class="text-xs font-medium text-amber-700">Een andere reviewer is vereist</span>
                            @endif
                            <a href="{{ route('content-studio.content.show', $contentNode) }}" class="cs-button-secondary">
                                Beoordelen
                                <x-content-studio.icon name="arrow-right" class="size-4" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($contentNodes->hasPages())
        <div class="mt-6">{{ $contentNodes->links() }}</div>
    @endif
@endsection
