<!doctype html>
<html lang="nl">
<body style="font-family: Arial, sans-serif; color: #302722; line-height: 1.6">
    <h1 style="font-size: 24px">Je maandbetaling is nog niet gelukt</h1>
    <p>Mollie probeert een mislukte abonnementsbetaling doorgaans automatisch opnieuw. Je toegang blijft tijdens de herstelperiode actief tot <strong>{{ $subscription->grace_ends_at?->format('d-m-Y H:i') }}</strong>.</p>
    @if ($reminderDay >= 13)
        <p><strong>Dit is de laatste herinnering.</strong> Zonder geslaagde betaling stopt je extra toegang na de genoemde datum.</p>
    @elseif ($reminderDay >= 7)
        <p>De betaling staat inmiddels een week open. Controleer je abonnementsstatus om te zien of de betaling intussen is hersteld.</p>
    @endif
    <p><a href="{{ route('trial-week.show') }}">Controleer en herstel mijn betaling</a></p>
    <p>Klikken op deze link schrijft nooit direct geld af. Na inloggen zie je eerst de actuele status.</p>
    <p>Vragen? Mail naar <a href="mailto:{{ config('subscriptions.invoicing.support_email') }}">{{ config('subscriptions.invoicing.support_email') }}</a>.</p>
</body>
</html>
