@extends('layouts.content-studio')

@section('title', 'Bètastatus')

@section('content')
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="cs-eyebrow">Fase 4 · gesloten bèta</p>
            <h1 id="beta-title" class="cs-page-title">Bètastatus</h1>
            <p class="cs-page-description">Geaggregeerde voortgang en operationele controles, zonder trackingcookies, transcripties, audio of individuele spelersgegevens.</p>
        </div>
        <nav class="flex flex-wrap gap-2" aria-label="Meetperiode">
            @foreach ($periods as $period)
                <a href="{{ route('content-studio.beta.index', ['periode' => $period]) }}"
                   @if ($metrics['days'] === $period) aria-current="page" @endif
                   class="{{ $metrics['days'] === $period ? 'cs-button-primary' : 'cs-button-secondary' }}">
                    {{ $period }} dagen
                </a>
            @endforeach
        </nav>
    </div>

    <section class="mt-8" aria-labelledby="cohort-title">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="cohort-title" class="text-lg font-bold text-slate-950">Instroomcohort</h2>
                <p class="mt-1 text-sm text-slate-500">Spelers aangemaakt van {{ $metrics['from']->format('d-m-Y') }} tot en met {{ $metrics['until']->format('d-m-Y') }}. Content Studio-accounts tellen niet mee.</p>
            </div>
            <span class="status-chip">Geaggregeerd</span>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($metrics['stages'] as $stage)
                <article class="cs-panel p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stage['label'] }}</p>
                            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $stage['count'] }}</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">{{ number_format($stage['percentage'], 1, ',', '.') }}%</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, $stage['percentage']) }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Aandeel van alle nieuwe spelers in deze periode.</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="trial-content-title">
        <div class="cs-panel-header flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="cs-eyebrow">Fase 4B3</p>
                <h2 id="trial-content-title" class="mt-2 font-bold text-slate-900">Proefweekcontent en media</h2>
                <p class="mt-1 text-sm text-slate-500">Per dag wordt de exact gepubliceerde productierevisie gecontroleerd op scene-contract, toegangsgrens en verplichte beeldrollen.</p>
            </div>
            <span class="status-chip">{{ collect($operations['content_items'])->where('ready', true)->count() }}/{{ count($operations['content_items']) }} onderdelen gereed</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($operations['content_items'] as $item)
                <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $item['ready'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }} text-xs font-black" aria-label="{{ $item['day'] ? 'Dag '.$item['day'] : 'Wereld' }}">
                            {{ $item['day'] ?? 'M' }}
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-slate-900">{{ $item['label'] }}</h3>
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $item['ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $item['status'] }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $item['scope'] }}</p>
                            @if ($item['missing_media_roles'] !== [])
                                <p class="mt-1 text-xs font-semibold text-amber-800">Nog vereist: {{ implode(', ', $item['missing_media_roles']) }}</p>
                            @elseif (isset($item['detail']))
                                <p class="mt-1 text-xs text-slate-500">{{ $item['detail'] }}</p>
                            @endif
                        </div>
                    </div>
                    @if ($item['content_node'])
                        <a href="{{ route('content-studio.content.show', $item['content_node']) }}" class="cs-button-secondary shrink-0">Open content</a>
                    @elseif ($item['template'])
                        <a href="{{ route('content-studio.content.create', ['template' => $item['template']]) }}" class="cs-button-secondary shrink-0">Maak concept</a>
                    @else
                        <span class="text-xs font-semibold text-slate-500">Automatisch samengesteld</span>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(20rem,1fr)]">
        <section class="cs-panel overflow-hidden" aria-labelledby="readiness-title">
            <div class="cs-panel-header flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="readiness-title" class="font-bold text-slate-900">Gereedheidscontrole</h2>
                    <p class="mt-1 text-sm text-slate-500">Configuratiewaarden worden alleen op aanwezigheid gecontroleerd en nooit getoond.</p>
                </div>
                <span class="status-chip">{{ $operations['ready_count'] }}/{{ $operations['check_count'] }} gereed</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($operations['checks'] as $check)
                    <article class="flex gap-4 p-5 sm:p-6">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $check['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}" aria-hidden="true">
                            {{ $check['ready'] ? '✓' : '!' }}
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-slate-900">{{ $check['label'] }}</h3>
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $check['ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $check['ready'] ? 'Gereed' : 'Actie nodig' }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $check['detail'] }}</p>
                            @unless ($check['ready'])
                                <p class="mt-1 text-xs font-semibold text-amber-800">{{ $check['action'] }}</p>
                            @endunless
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="space-y-6">
            <section class="cs-panel p-6" aria-labelledby="incidents-title">
                <p class="cs-eyebrow">Support</p>
                <h2 id="incidents-title" class="mt-2 font-bold text-slate-900">Operationele signalen</h2>
                <dl class="mt-5 space-y-4">
                    @foreach ([
                        ['Betaling achterstallig', $operations['incidents']['past_due']],
                        ['Toegang gepauzeerd', $operations['incidents']['paused']],
                        ['E-mail te lang open', $operations['incidents']['overdue_emails']],
                        ['E-mailpogingen gestopt', $operations['incidents']['exhausted_emails']],
                    ] as [$label, $value])
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <dt class="text-sm text-slate-600">{{ $label }}</dt>
                            <dd class="text-lg font-black {{ $value > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <a href="{{ route('content-studio.billing.index') }}" class="cs-button-secondary mt-6 w-full">Open betaalbeheer</a>
            </section>

            <aside class="cs-panel p-6" aria-labelledby="privacy-title">
                <span class="grid size-11 place-items-center rounded-xl bg-emerald-100 text-emerald-700"><x-content-studio.icon name="shield" /></span>
                <h2 id="privacy-title" class="mt-4 font-bold text-slate-900">Privacybewuste meting</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Deze pagina telt alleen bestaande, structurele mijlpalen per cohort. Er worden geen nieuwe persoonsgegevens, cookies, audio, transcripties of vrije antwoorden opgeslagen.</p>
            </aside>
        </div>
    </div>
@endsection
