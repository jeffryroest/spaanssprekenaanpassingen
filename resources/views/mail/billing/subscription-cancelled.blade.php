<!doctype html>
<html lang="nl">
<body style="font-family: Arial, sans-serif; color: #302722; line-height: 1.6">
    <h1 style="font-size: 24px">Je abonnement is opgezegd</h1>
    <p>De automatische verlenging van Spaansspreken.nl is stopgezet.</p>
    @if ($subscription->current_period_ends_at)
        <p>Je toegang blijft actief tot <strong>{{ $subscription->current_period_ends_at->format('d-m-Y H:i') }}</strong>. Daarna schrijven we niet opnieuw af.</p>
    @endif
    <p>Vragen? Mail naar <a href="mailto:{{ config('subscriptions.invoicing.support_email') }}">{{ config('subscriptions.invoicing.support_email') }}</a>.</p>
</body>
</html>
