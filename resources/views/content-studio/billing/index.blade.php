@extends('layouts.content-studio')

@section('title', 'Betalingen')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="cs-eyebrow">Mollie en abonnementen</p>
            <h1 class="cs-page-title">Betalingen</h1>
            <p class="cs-page-description">Bekijk bestellingen, facturatie en financiële signalen. Mislukte maandincasso’s krijgen veertien dagen respijt; refunds en chargebacks blokkeren toegang voor controle.</p>
        </div>
        <span class="status-chip">Alleen beheerders</span>
    </div>

    @if (session('billing_notice'))
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900" role="status">
            {{ session('billing_notice') }}
        </div>
    @endif

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Betaalstatus">
        @foreach ([
            [$totalOrderCount, 'Bestellingen', 'Alle geregistreerde checkouts'],
            [$paidOrderCount, 'Betaald', 'Bevestigde eerste betalingen'],
            [$activeSubscriptionCount, 'Met toegang', 'Actief of betaald opgezegd'],
            [$attentionOrderCount, 'Aandacht', 'Mislukt, verlopen of teruggedraaid'],
        ] as [$value, $label, $detail])
            <article class="cs-panel p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $value }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $detail }}</p>
            </article>
        @endforeach
    </section>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="subscriptions-title">
        <div class="cs-panel-header">
            <h2 id="subscriptions-title" class="font-bold text-slate-900">Abonnementen beheren</h2>
            <p class="mt-1 text-sm text-slate-500">Een beheerder kan namens de klant opzeggen. De betaalde toegang blijft tot het periode-einde bestaan.</p>
        </div>

        @if ($managedSubscriptions->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-slate-500">Geen actieve abonnementen om te beheren.</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($managedSubscriptions as $subscription)
                    @php
                        $billingOrder = $subscription->orders->first();
                    @endphp
                    <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-900">{{ $billingOrder ? trim($billingOrder->first_name.' '.$billingOrder->last_name) : $subscription->user->name }}</p>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $subscription->status === App\Enums\SubscriptionStatus::PastDue ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">{{ $subscription->status->label() }}</span>
                            </div>
                            <p class="mt-1 break-all text-xs text-slate-500">{{ $billingOrder?->email ?? $subscription->user->email }}</p>
                            @if ($subscription->status === App\Enums\SubscriptionStatus::PastDue && $subscription->grace_ends_at)
                                <p class="mt-2 text-xs font-semibold text-amber-800">Respijt tot {{ $subscription->grace_ends_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</p>
                            @elseif ($subscription->current_period_ends_at)
                                <p class="mt-2 text-xs text-slate-500">Betaalde periode tot {{ $subscription->current_period_ends_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('content-studio.billing.subscriptions.cancel', $subscription) }}" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            @csrf
                            <label class="flex items-start gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="confirm_cancellation" value="1" required class="mt-0.5 size-4 accent-slate-900">
                                <span>Opzegging namens klant bevestigen</span>
                            </label>
                            <button type="submit" class="mt-3 inline-flex min-h-10 items-center justify-center rounded-lg border border-red-200 bg-white px-4 text-xs font-bold text-red-700 hover:bg-red-50">Opzeggen per periode-einde</button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="orders-title">
        <div class="cs-panel-header">
            <h2 id="orders-title" class="font-bold text-slate-900">Bestellingen</h2>
            <p class="mt-1 text-sm text-slate-500">Zoek op besteller, bedrijf, btw-id, e-mailadres of intern bestelnummer.</p>
        </div>

        <form method="POST" action="{{ route('content-studio.billing.search') }}" class="grid gap-4 border-b border-slate-200 p-5 md:grid-cols-[minmax(0,1fr)_16rem_auto] md:items-end sm:p-6">
            @csrf
            <div>
                <label for="billing-search" class="cs-label">Zoeken</label>
                <input id="billing-search" name="q" value="{{ $search }}" maxlength="100" class="cs-field" placeholder="Naam, e-mail of bestelnummer">
            </div>
            <div>
                <label for="billing-status" class="cs-label">Betaalstatus</label>
                <select id="billing-status" name="status" class="cs-field">
                    <option value="">Alle statussen</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="cs-button-primary">
                <x-content-studio.icon name="search" class="size-4" />
                Filteren
            </button>
        </form>

        @if ($orders->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-bold text-slate-900">Geen bestellingen gevonden</p>
                <p class="mt-2 text-sm text-slate-500">Pas de zoekterm of status aan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Besteller</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Status</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Bedrag</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Besteld</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Bestelnummer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($orders as $order)
                            @php
                                $needsAttention = in_array($order->payment_status, [
                                    App\Enums\CheckoutPaymentStatus::Failed,
                                    App\Enums\CheckoutPaymentStatus::Canceled,
                                    App\Enums\CheckoutPaymentStatus::Expired,
                                    App\Enums\CheckoutPaymentStatus::Refunded,
                                    App\Enums\CheckoutPaymentStatus::ChargedBack,
                                ], true);
                            @endphp
                            <tr>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-bold text-slate-900">{{ $order->first_name }} {{ $order->last_name }}</p>
                                    @if ($order->company_name)
                                        <p class="mt-1 text-xs font-semibold text-slate-700">{{ $order->company_name }}</p>
                                    @endif
                                    <p class="mt-1 break-all text-xs text-slate-500">{{ $order->email }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $needsAttention ? 'bg-amber-100 text-amber-900' : ($order->payment_status === App\Enums\CheckoutPaymentStatus::Paid ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $order->payment_status->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-700 sm:px-6">€ {{ number_format($order->amount_minor / 100, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600 sm:px-6">{{ $order->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-500 sm:px-6">{{ $order->public_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($ordersHavePages)
                <div class="border-t border-slate-200 p-5 sm:p-6">{{ $orders->links() }}</div>
            @endif
        @endif
    </section>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="incidents-title">
        <div class="cs-panel-header flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="incidents-title" class="font-bold text-slate-900">Recente aandachtspunten</h2>
                <p class="mt-1 text-sm text-slate-500">Gesaneerde betaalgebeurtenissen; geen kaart- of bankgegevens.</p>
            </div>
            <span class="status-chip">{{ $recentEvents->count() }} zichtbaar</span>
        </div>

        @if ($recentEvents->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-bold text-slate-900">Geen financiële signalen</p>
                <p class="mt-2 text-sm text-slate-500">Er zijn geen recente betalingen die handmatige aandacht vragen.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($recentEvents as $event)
                    @php
                        $paymentId = $event->event_payload['payment_id'] ?? null;
                        $relatedOrder = $paymentId ? $ordersByPayment->get($paymentId) : null;
                        $customerName = $relatedOrder
                            ? trim($relatedOrder->first_name.' '.$relatedOrder->last_name)
                            : $event->subscription?->user?->name;
                    @endphp
                    <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900">{{ $event->attentionLabel() }}</span>
                                @if ($customerName)
                                    <span class="font-bold text-slate-900">{{ $customerName }}</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $event->occurred_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }} · {{ $event->event_type }}</p>
                        </div>
                        @if ($relatedOrder)
                            <span class="font-mono text-xs text-slate-500">{{ $relatedOrder->public_id }}</span>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
