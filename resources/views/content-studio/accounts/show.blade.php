@extends('layouts.content-studio')

@section('title', 'Accountdetail')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('content-studio.accounts.index') }}" class="text-sm font-bold text-brand-700 hover:text-brand-900">← Spelers en accounts</a>
            <p class="cs-eyebrow mt-4">Supportdossier</p>
            <h1 class="cs-page-title break-words">{{ $account->name }}</h1>
            <p class="cs-page-description break-all">{{ $account->email }}</p>
        </div>
        <span class="status-chip">{{ $account->content_role?->label() ?? 'Speler' }}</span>
    </div>

    @foreach (['role_status', 'support_status', 'deletion_status'] as $statusKey)
        @if (session($statusKey))
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-900" role="status">{{ session($statusKey) }}</div>
        @endif
    @endforeach

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Accountsignalen">
        @foreach ([
            [$account->mission_attempts_count, 'Pogingen', 'Structurele missiepogingen'],
            [$account->mission_progress_count, 'Missies', 'Unieke voortgangsregels'],
            [$account->rewards_count, 'Beloningen', 'Ontvangen spelbeloningen'],
            [$account->created_at->timezone('Europe/Madrid')->format('d-m-Y'), 'Sinds', 'Registratiedatum'],
        ] as [$value, $label, $detail])
            <article class="cs-panel p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $value }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $detail }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(20rem,0.85fr)]">
        <div class="space-y-6">
            <section class="cs-panel overflow-hidden" aria-labelledby="support-title">
                <div class="cs-panel-header">
                    <h2 id="support-title" class="font-bold text-slate-900">Supportnotities</h2>
                    <p class="mt-1 text-sm text-slate-500">Leg alleen het supportprobleem en de actie vast. Neem geen antwoorden, transcripties, audio of AI-feedback over.</p>
                </div>
                <form method="POST" action="{{ route('content-studio.accounts.notes.store', $account) }}" class="grid gap-4 border-b border-slate-200 p-5 sm:p-6">
                    @csrf
                    <div>
                        <label for="support-category" class="cs-label">Categorie</label>
                        <select id="support-category" name="category" class="cs-field @error('category', 'support') border-red-500 @enderror" required>
                            @foreach ($supportCategories as $category)
                                <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                            @endforeach
                        </select>
                        @error('category', 'support')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="support-summary" class="cs-label">Korte notitie</label>
                        <textarea id="support-summary" name="summary" rows="3" maxlength="500" class="cs-field @error('summary', 'support') border-red-500 @enderror" required>{{ old('summary') }}</textarea>
                        @error('summary', 'support')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:max-w-xs">
                        <label for="follow-up-at" class="cs-label">Opvolgen op <span class="font-normal text-slate-400">(optioneel)</span></label>
                        <input id="follow-up-at" name="follow_up_at" type="datetime-local" value="{{ old('follow_up_at') }}" class="cs-field @error('follow_up_at', 'support') border-red-500 @enderror">
                        @error('follow_up_at', 'support')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div><button type="submit" class="cs-button-primary">Notitie toevoegen</button></div>
                </form>

                @if ($account->supportNotes->isEmpty())
                    <p class="p-6 text-sm text-slate-500">Nog geen supportnotities.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($account->supportNotes as $note)
                            <li class="p-5 sm:p-6">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $note->category->label() }}</span>
                                    <span class="text-xs text-slate-500">{{ $note->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }} · {{ $note->actor?->name ?? 'Systeem' }}</span>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $note->summary }}</p>
                                @if ($note->follow_up_at)
                                    <p class="mt-2 text-xs font-semibold text-amber-800">Opvolgen: {{ $note->follow_up_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</p>
                                @endif
                                @if ($note->resolved_at)
                                    <p class="mt-3 text-xs font-semibold text-emerald-700">Opgelost op {{ $note->resolved_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }} door {{ $note->resolvedBy?->name ?? 'Systeem' }}</p>
                                @else
                                    <form method="POST" action="{{ route('content-studio.accounts.notes.resolve', [$account, $note]) }}" class="mt-4">
                                        @csrf
                                        <button type="submit" class="cs-button-secondary">Markeer als opgelost</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cs-panel overflow-hidden" aria-labelledby="billing-title">
                <div class="cs-panel-header">
                    <h2 id="billing-title" class="font-bold text-slate-900">Toegang en betalingen</h2>
                    <p class="mt-1 text-sm text-slate-500">Alleen operationele statussen; providergeheimen worden niet getoond.</p>
                </div>
                <div class="p-5 sm:p-6">
                    @if ($account->latestSubscription)
                        <p class="font-bold text-slate-900">{{ $account->latestSubscription->status->label() }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $account->latestSubscription->plan?->name ?? 'Onbekend plan' }}</p>
                    @else
                        <p class="text-sm text-slate-500">Geen abonnement.</p>
                    @endif
                </div>
                @if ($account->subscriptionOrders->isNotEmpty())
                    <ul class="divide-y divide-slate-100 border-t border-slate-200">
                        @foreach ($account->subscriptionOrders as $order)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm sm:px-6">
                                <span class="font-semibold text-slate-700">{{ $order->payment_status->label() }}</span>
                                <span class="text-slate-500">€ {{ number_format($order->amount_minor / 100, 2, ',', '.') }} · {{ $order->created_at->timezone('Europe/Madrid')->format('d-m-Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="cs-panel p-5 sm:p-6" aria-labelledby="role-title">
                <h2 id="role-title" class="font-bold text-slate-900">Content Studio-rol</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Iedere wijziging wordt geaudit. Je kunt je eigen rol en de laatste beheerder niet wijzigen.</p>
                @error('role', 'role')<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</div>@enderror
                <form method="POST" action="{{ route('content-studio.accounts.role.update', $account) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="account-role" class="cs-label">Rol</label>
                        <select id="account-role" name="role" class="cs-field" @disabled(auth()->id() === $account->getKey() || $account->privacy_erased_at)>
                            <option value="">Speler · geen beheerrol</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($account->content_role === $role)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="cs-button-primary" @disabled(auth()->id() === $account->getKey() || $account->privacy_erased_at)>Rol opslaan</button>
                </form>

                @if ($account->contentRoleAudits->isNotEmpty())
                    <ol class="mt-6 space-y-3 border-t border-slate-200 pt-5">
                        @foreach ($account->contentRoleAudits as $audit)
                            <li class="text-xs leading-5 text-slate-500">
                                {{ $audit->from_role?->label() ?? 'Speler' }} → {{ $audit->to_role?->label() ?? 'Speler' }}
                                · {{ $audit->created_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}
                                · {{ $audit->actor?->name ?? 'Systeem' }}
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            <section class="cs-panel overflow-hidden" aria-labelledby="deletion-title">
                <div class="cs-panel-header">
                    <h2 id="deletion-title" class="font-bold text-slate-900">Verwijderverzoeken</h2>
                    <p class="mt-1 text-sm text-slate-500">Speelgegevens worden gewist. Fiscale betaal- en factuurgegevens blijven waar nodig zeven jaar apart bewaard.</p>
                </div>
                @error('confirmation_email', 'deletion')<div class="m-5 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</div>@enderror
                @error('current_password', 'deletion')<div class="m-5 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</div>@enderror

                @if ($account->deletionRequests->isEmpty())
                    <p class="p-6 text-sm text-slate-500">Geen verwijderverzoek ingediend.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($account->deletionRequests as $deletionRequest)
                            <li class="p-5 sm:p-6">
                                <p class="font-bold text-slate-900">{{ $deletionRequest->status->label() }}</p>
                                <p class="mt-1 text-xs text-slate-500">Aangevraagd {{ $deletionRequest->requested_at->timezone('Europe/Madrid')->format('d-m-Y H:i') }}</p>
                                @if ($deletionRequest->billing_retained_until)
                                    <p class="mt-2 text-xs font-semibold text-amber-800">Fiscale gegevens bewaren tot en met {{ $deletionRequest->billing_retained_until->format('d-m-Y') }}</p>
                                @endif

                                @if (in_array($deletionRequest->status, [App\Enums\AccountDeletionStatus::Requested, App\Enums\AccountDeletionStatus::Blocked], true) && ! $account->privacy_erased_at)
                                    <form method="POST" action="{{ route('content-studio.accounts.deletions.process', [$account, $deletionRequest]) }}" class="mt-5 space-y-4 rounded-2xl border border-red-200 bg-red-50 p-4">
                                        @csrf
                                        <p class="text-xs font-bold leading-5 text-red-900">Dit wist onomkeerbaar het profiel, de sessies en alle spelvoortgang. Rond een actief Mollie-abonnement eerst af.</p>
                                        <div>
                                            <label for="confirmation-email-{{ $deletionRequest->getKey() }}" class="cs-label">Typ het e-mailadres van dit account</label>
                                            <input id="confirmation-email-{{ $deletionRequest->getKey() }}" name="confirmation_email" type="email" autocomplete="off" class="cs-field" required>
                                        </div>
                                        <div>
                                            <label for="deletion-password-{{ $deletionRequest->getKey() }}" class="cs-label">Jouw beheerderswachtwoord</label>
                                            <input id="deletion-password-{{ $deletionRequest->getKey() }}" name="current_password" type="password" autocomplete="current-password" class="cs-field" required>
                                        </div>
                                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-red-700 px-4 py-2 text-sm font-bold text-white hover:bg-red-800">Accountgegevens definitief wissen</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
