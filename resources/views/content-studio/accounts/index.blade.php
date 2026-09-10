@extends('layouts.content-studio')

@section('title', 'Spelers en accounts')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="cs-eyebrow">Support en toegang</p>
            <h1 class="cs-page-title">Spelers en accounts</h1>
            <p class="cs-page-description">Zoek accounts en zie hun rol, missieactiviteit en laatste abonnementsstatus. Antwoorden, audio, transcripties en feedback worden hier niet getoond.</p>
        </div>
        <span class="status-chip">Alleen beheerders</span>
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Accountstatus">
        @foreach ([
            [$playerCount, 'Spelers', 'Accounts zonder beheerrol'],
            [$staffCount, 'Contentteam', 'Accounts met Content Studio-rol'],
            [$activeAccessCount, 'Met toegang', 'Proef, actief of betaald opgezegd'],
            [$attentionCount, 'Aandacht', 'Achterstallig of gepauzeerd'],
        ] as [$value, $label, $detail])
            <article class="cs-panel p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $value }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $detail }}</p>
            </article>
        @endforeach
    </section>

    <section class="cs-panel mt-8 overflow-hidden" aria-labelledby="accounts-title">
        <div class="cs-panel-header">
            <h2 id="accounts-title" class="font-bold text-slate-900">Accountoverzicht</h2>
            <p class="mt-1 text-sm text-slate-500">Zoeken gebeurt via POST, zodat persoonsgegevens niet in de URL of browsergeschiedenis belanden.</p>
        </div>

        <form method="POST" action="{{ route('content-studio.accounts.search') }}" class="grid gap-4 border-b border-slate-200 p-5 lg:grid-cols-[minmax(0,1fr)_13rem_15rem_auto] lg:items-end sm:p-6">
            @csrf
            <div>
                <label for="account-search" class="cs-label">Zoeken</label>
                <input id="account-search" name="q" value="{{ $search }}" maxlength="100" class="cs-field" placeholder="Naam of e-mailadres">
            </div>
            <div>
                <label for="account-type" class="cs-label">Accounttype</label>
                <select id="account-type" name="account_type" class="cs-field">
                    <option value="">Alle accounts</option>
                    <option value="player" @selected($selectedAccountType === 'player')>Speler</option>
                    <option value="staff" @selected($selectedAccountType === 'staff')>Contentteam</option>
                </select>
            </div>
            <div>
                <label for="subscription-status" class="cs-label">Abonnementsstatus</label>
                <select id="subscription-status" name="subscription_status" class="cs-field">
                    <option value="">Alle statussen</option>
                    <option value="none" @selected($selectedSubscriptionStatus === 'none')>Geen abonnement</option>
                    @foreach ($subscriptionStatuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedSubscriptionStatus === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="cs-button-primary">
                <x-content-studio.icon name="search" class="size-4" />
                Filteren
            </button>
        </form>

        @if ($accounts->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-bold text-slate-900">Geen accounts gevonden</p>
                <p class="mt-2 text-sm text-slate-500">Pas de zoekterm of filters aan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Account</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Type</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Toegang</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Missies</th>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-6">Aangemaakt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($accounts as $account)
                            @php
                                $subscription = $account->latestSubscription;
                                $needsAttention = $subscription && in_array($subscription->status, [App\Enums\SubscriptionStatus::PastDue, App\Enums\SubscriptionStatus::Paused], true);
                            @endphp
                            <tr>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-bold text-slate-900">{{ $account->name }}</p>
                                    <p class="mt-1 break-all text-xs text-slate-500">{{ $account->email }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $account->content_role?->label() ?? 'Speler' }}</span>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    @if ($subscription)
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $needsAttention ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">{{ $subscription->status->label() }}</span>
                                        @if ($subscription->plan)
                                            <p class="mt-2 text-xs text-slate-500">{{ $subscription->plan->name }}</p>
                                        @endif
                                    @else
                                        <span class="text-xs font-semibold text-slate-500">Geen abonnement</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-700 sm:px-6">{{ $account->mission_attempts_count }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600 sm:px-6">{{ $account->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($accountsHavePages)
                <div class="border-t border-slate-200 p-5 sm:p-6">{{ $accounts->links() }}</div>
            @endif
        @endif
    </section>

    <aside class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
        Rolwijzigingen en accountverwijdering blijven bewust buiten deze eerste supportweergave. Rolmutaties moeten altijd worden geaudit; verwijdering wacht op het formele retentiebeleid voor bestellingen en facturen.
    </aside>
@endsection
