<!doctype html>
<html lang="nl">
<body style="font-family: Arial, sans-serif; color: #302722; line-height: 1.6">
    <h1 style="font-size: 24px">Je betaling is bevestigd</h1>
    <p>Bedankt. We hebben je betaling van € {{ number_format($invoice->amount_minor / 100, 2, ',', '.') }} voor Spaansspreken.nl ontvangen.</p>
    <p>Je factuur <strong>{{ $invoice->invoice_number }}</strong> is als pdf toegevoegd. Je kunt de factuur ook veilig downloaden wanneer je bent ingelogd:</p>
    <p><a href="{{ route('billing.invoices.download', $invoice) }}">Download mijn factuur</a></p>
    <p>Vragen? Mail naar <a href="mailto:{{ config('subscriptions.invoicing.support_email') }}">{{ config('subscriptions.invoicing.support_email') }}</a>.</p>
</body>
</html>
