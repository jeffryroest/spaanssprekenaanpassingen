@extends('layouts.content-studio')

@section('title', $contentRelease->name)

@section('content')
    <a href="{{ route('content-studio.releases.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-brand-700">
        <x-content-studio.icon name="arrow-left" class="size-4" />
        Terug naar releases
    </a>

    <div class="mt-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <p class="cs-eyebrow">Release #{{ $contentRelease->id }} · {{ $contentRelease->target_channel->label() }}</p>
                <x-content-studio.status-badge :status="$contentRelease->status" />
            </div>
            <h1 class="cs-page-title">{{ $contentRelease->name }}</h1>
            <p class="cs-page-description">{{ $contentRelease->description ?: 'Geen omschrijving toegevoegd.' }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <span class="status-chip">{{ $contentRelease->items->count() }} {{ $contentRelease->items->count() === 1 ? 'revisie' : 'revisies' }}</span>
            <span class="status-chip">Eigenaar {{ $contentRelease->owner?->name ?? 'Onbekend' }}</span>
        </div>
    </div>

    @if ($errors->has('release') || $errors->has('content') || $errors->has('preflight') || $errors->has('desired_publish_at'))
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800" role="alert">
            <p class="font-bold">De releaseactie is niet uitgevoerd</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach (['release', 'content', 'preflight', 'desired_publish_at'] as $field)
                    @foreach ($errors->get($field) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Releasegegevens">
        @foreach ([
            ['release', $contentRelease->target_channel->label(), 'Doelkanaal', 'Kanaalgebonden uitvoering', 'bg-violet-50 text-violet-700'],
            ['document', (string) $contentRelease->items->count(), 'Versies', 'Exacte revisies vastgelegd', 'bg-blue-50 text-blue-700'],
            ['clock', $contentRelease->desired_publish_at?->timezone('Europe/Madrid')->format('d-m H:i') ?? 'Handmatig', 'Gewenst moment', 'Niet vóór dit tijdstip', 'bg-amber-50 text-amber-800'],
            ['shield', $preflight['blockers'] === [] ? 'Gereed' : count($preflight['blockers']).' blokkade(s)', 'Preflight', 'Opnieuw gecontroleerd bij uitvoering', $preflight['blockers'] === [] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'],
        ] as [$icon, $value, $label, $detail, $color])
            <article class="cs-panel p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-lg font-bold tracking-tight text-slate-950">{{ $value }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $detail }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-xl {{ $color }}" aria-hidden="true"><x-content-studio.icon :name="$icon" /></span>
                </div>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(21rem,1fr)]">
        <div class="space-y-6">
            <section class="cs-panel overflow-hidden" aria-labelledby="release-items-title">
                <div class="cs-panel-header flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="release-items-title" class="font-bold text-slate-900">Vastgelegde contentversies</h2>
                        <p class="mt-1 text-sm text-slate-500">Iedere regel verwijst naar één onveranderlijke revisie.</p>
                    </div>
                    <span class="status-chip">{{ $contentRelease->items->count() }} ingepland</span>
                </div>

                @if ($contentRelease->items->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-500"><x-content-studio.icon name="document" /></span>
                        <h3 class="mt-4 font-bold text-slate-900">De release is nog leeg</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Voeg onderaan een goedgekeurde revisie toe. Een lege release kan nooit worden uitgevoerd.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($contentRelease->items as $item)
                            @php($localization = $item->contentNode?->defaultLocalization())
                            <article class="flex flex-col gap-4 p-5 sm:p-6 lg:flex-row lg:items-center">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700"><x-content-studio.icon name="document" /></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('content-studio.content.show', $item->contentNode) }}" class="font-bold text-slate-900 hover:text-brand-700">{{ $localization?->title ?? $item->contentNode?->slug ?? 'Ontbrekende content' }}</a>
                                        @if ($item->contentNode)<x-content-studio.status-badge :status="$item->contentNode->status" />@endif
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $item->contentNode?->content_type->label() ?? 'Onbekend type' }} · revisie {{ $item->version }} · toegevoegd door {{ $item->creator?->name ?? 'Onbekend' }}</p>
                                </div>
                                @can('content-studio.publish')
                                    @if ($contentRelease->isEditable())
                                        <form method="POST" action="{{ route('content-studio.releases.items.destroy', [$contentRelease, $item]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cs-button-secondary text-red-700 hover:bg-red-50">Uit release halen</button>
                                        </form>
                                    @endif
                                @endcan
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            @can('content-studio.publish')
                @if ($contentRelease->isEditable())
                    <section class="cs-panel overflow-hidden" aria-labelledby="approved-content-title">
                        <div class="cs-panel-header">
                            <h2 id="approved-content-title" class="font-bold text-slate-900">Goedgekeurde content toevoegen</h2>
                            <p class="mt-1 text-sm text-slate-500">Toevoegen zet de content atomair op Gepland en bewaart het exacte revisienummer.</p>
                        </div>

                        @error('content_node_id')
                            <p class="mx-5 mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 sm:mx-6" role="alert">{{ $message }}</p>
                        @enderror
                        @error('expected_version')
                            <p class="mx-5 mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 sm:mx-6" role="alert">{{ $message }}</p>
                        @enderror

                        @if ($approvedContent->isEmpty())
                            <div class="p-5 text-sm leading-6 text-slate-500 sm:p-6">Er is momenteel geen goedgekeurde content beschikbaar. Rond eerst een review af of haal een item uit een andere conceptrelease.</div>
                        @else
                            <div class="divide-y divide-slate-100">
                                @foreach ($approvedContent as $contentNode)
                                    <form method="POST" action="{{ route('content-studio.releases.items.store', $contentRelease) }}" class="flex flex-col gap-4 p-5 sm:p-6 lg:flex-row lg:items-center">
                                        @csrf
                                        <input type="hidden" name="content_node_id" value="{{ $contentNode->id }}">
                                        <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-slate-900">{{ $contentNode->defaultLocalization()?->title ?? $contentNode->slug }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $contentNode->content_type->label() }} · revisie {{ $contentNode->current_version }} · #{{ $contentNode->id }}</p>
                                        </div>
                                        <button type="submit" class="cs-button-secondary shrink-0"><x-content-studio.icon name="plus" class="size-4" />Toevoegen</button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif
            @endcan
        </div>

        <div class="space-y-6">
            @if ($contentRelease->isEditable())
                <aside class="cs-panel overflow-hidden" aria-labelledby="preflight-title">
                    <div class="cs-panel-header">
                        <h2 id="preflight-title" class="font-bold text-slate-900">Preflight</h2>
                        <p class="mt-1 text-sm text-slate-500">Resultaat van de actuele release-inhoud</p>
                    </div>
                    <div class="space-y-4 p-5 sm:p-6">
                        @if ($preflight['blockers'] === [])
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                <p class="font-bold">Geen blokkerende fouten</p>
                                <p class="mt-1 leading-6">Status, actuele revisie en reviewgoedkeuring zijn voor alle items geldig.</p>
                            </div>
                        @else
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                                <p class="font-bold">Uitvoering geblokkeerd</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($preflight['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                        @foreach ($preflight['warnings'] as $warning)
                            <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800">{{ $warning }}</p>
                        @endforeach
                    </div>
                </aside>

                @can('content-studio.publish')
                    <section class="overflow-hidden rounded-2xl border {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'border-red-200 bg-red-50/70' : 'border-violet-200 bg-violet-50/60' }}" aria-labelledby="execute-release-title">
                        <div class="p-5 sm:p-6">
                            <h2 id="execute-release-title" class="font-bold {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'text-red-950' : 'text-violet-950' }}">Release uitvoeren naar {{ $contentRelease->target_channel->label() }}</h2>
                            <p class="mt-2 text-sm leading-6 {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'text-red-800' : 'text-violet-800' }}">
                                @if ($contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production)
                                    Dit maakt alle vastgelegde revisies actief voor de toekomstige publieke game-API. De producteigenaar beslist definitief over deze handeling.
                                @else
                                    Dit legt een reproduceerbare kanaaluitvoering vast. De content wordt daarna weer Goedgekeurd en niet publiek.
                                @endif
                            </p>

                            <form method="POST" action="{{ route('content-studio.releases.publish', $contentRelease) }}" class="mt-5 space-y-4">
                                @csrf
                                <div>
                                    <label for="confirmation" class="cs-label {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'text-red-950' : 'text-violet-950' }}">Typ {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'PUBLICEREN' : 'UITVOEREN' }}</label>
                                    <input id="confirmation" name="confirmation" type="text" required autocomplete="off" class="cs-field" @error('confirmation') aria-invalid="true" aria-describedby="confirmation-error" @enderror>
                                    @error('confirmation')<p id="confirmation-error" class="cs-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="publish-reason" class="cs-label {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'text-red-950' : 'text-violet-950' }}">Motivatie</label>
                                    <textarea id="publish-reason" name="reason" required minlength="3" maxlength="1000" rows="3" class="cs-field" @error('reason') aria-invalid="true" aria-describedby="publish-reason-error" @enderror>{{ old('reason') }}</textarea>
                                    @error('reason')<p id="publish-reason-error" class="cs-error">{{ $message }}</p>@enderror
                                </div>
                                @if ($contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production)
                                    <label class="flex items-start gap-3 rounded-xl border border-red-200 bg-white/70 p-4 text-sm leading-6 text-red-900">
                                        <input name="acknowledge_warnings" type="checkbox" value="1" required class="mt-1 size-4 rounded border-red-300 text-red-600 focus:ring-red-500">
                                        <span>Ik heb de zichtbare waarschuwingen over relaties, media en rechten handmatig gecontroleerd en leg mijn motivatie hierboven vast.</span>
                                    </label>
                                    @error('acknowledge_warnings')<p class="cs-error">{{ $message }}</p>@enderror
                                @endif
                                <button type="submit" @disabled($preflight['blockers'] !== []) class="w-full {{ $contentRelease->target_channel === App\Enums\ContentReleaseChannel::Production ? 'cs-button-danger' : 'cs-button-primary' }} disabled:cursor-not-allowed disabled:opacity-50">
                                    <x-content-studio.icon name="release" class="size-4" />
                                    Uitvoeren naar {{ $contentRelease->target_channel->label() }}
                                </button>
                            </form>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="cancel-release-title">
                        <h2 id="cancel-release-title" class="font-bold text-slate-900">Conceptrelease annuleren</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Alle ingeplande content keert atomair terug naar Goedgekeurd. De releasehistorie en motivatie blijven bestaan.</p>
                        <form method="POST" action="{{ route('content-studio.releases.cancel', $contentRelease) }}" class="mt-4 space-y-3">
                            @csrf
                            <label for="cancel-reason" class="cs-label">Reden</label>
                            <input id="cancel-reason" name="cancel_reason" type="text" required minlength="3" maxlength="1000" class="cs-field" @error('cancel_reason') aria-invalid="true" aria-describedby="cancel-reason-error" @enderror>
                            @error('cancel_reason')<p id="cancel-reason-error" class="cs-error">{{ $message }}</p>@enderror
                            <button type="submit" class="cs-button-secondary text-red-700 hover:bg-red-50">Release annuleren</button>
                        </form>
                    </section>
                @endcan
            @elseif ($contentRelease->status === App\Enums\ContentReleaseStatus::Published)
                <aside class="cs-panel overflow-hidden">
                    <div class="bg-emerald-700 p-6 text-white">
                        <span class="grid size-11 place-items-center rounded-xl bg-white/15"><x-content-studio.icon name="release" /></span>
                        <h2 class="mt-5 text-lg font-bold">Release uitgevoerd</h2>
                        <p class="mt-2 text-sm leading-6 text-emerald-50">Door {{ $contentRelease->publisher?->name ?? 'Onbekend' }} op {{ $contentRelease->published_at?->timezone('Europe/Madrid')->format('d-m-Y H:i') }} naar {{ $contentRelease->target_channel->label() }}.</p>
                    </div>
                </aside>
            @else
                <aside class="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-900">
                    <h2 class="font-bold">Release geannuleerd</h2>
                    <p class="mt-2 text-sm leading-6">{{ $contentRelease->cancellation_reason }}</p>
                    <p class="mt-2 text-xs">Door {{ $contentRelease->canceller?->name ?? 'Onbekend' }} op {{ $contentRelease->cancelled_at?->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</p>
                </aside>
            @endif
        </div>
    </div>
@endsection
