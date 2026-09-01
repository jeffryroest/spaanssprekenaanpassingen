<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Controleer de status van je bestelling bij Spaansspreken.nl.">
    <title>Betaalstatus · Spaansspreken.nl</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#f7f1e7] text-[#302722] antialiased">
    <main class="mx-auto max-w-2xl px-5 py-12 sm:px-8 sm:py-20">
        <a href="{{ route('trial-week.show') }}" class="text-sm font-bold text-[#a9472b] underline underline-offset-4">← Terug naar mijn proefweek</a>

        <section class="mt-8 rounded-3xl border border-[#493429]/10 bg-[#fffaf0] p-6 shadow-sm sm:p-9" aria-labelledby="payment-status-title">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-[#a9472b]">Bestelling</p>
            <h1 id="payment-status-title" class="mt-3 font-serif text-4xl font-black">{{ $order->payment_status->label() }}</h1>

            @if ($notice)
                <p class="mt-5 rounded-2xl border border-[#bd5a34]/20 bg-[#fff4e8] p-4 text-sm leading-6 text-[#7a3e29]" role="status">{{ $notice }}</p>
            @endif

            <dl class="mt-7 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-[#8a776c]">Besteller</dt>
                    <dd class="mt-1 font-bold">{{ $order->first_name }} {{ $order->last_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-[#8a776c]">E-mailadres</dt>
                    <dd class="mt-1 break-all font-bold">{{ $order->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-[#8a776c]">Bedrag</dt>
                    <dd class="mt-1 font-bold">€ {{ number_format($order->amount_minor / 100, 2, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-[#8a776c]">Vervolg</dt>
                    <dd class="mt-1 text-sm leading-6">Daarna € {{ number_format($order->amount_minor / 100, 2, ',', '.') }} per maand totdat je opzegt.</dd>
                </div>
            </dl>

            @if ($order->payment_status->canRetry())
                <p class="mt-7 text-sm leading-6 text-[#6f5e54]">Er is niets nieuws afgeschreven. Je kunt vanuit je proefweek opnieuw afrekenen.</p>
            @elseif ($order->payment_status->value === 'paid')
                <p class="mt-7 rounded-2xl bg-[#e4f0df] p-4 text-sm font-bold leading-6 text-[#315d47]">Je betaling is bevestigd en je toegang is actief.</p>
            @else
                <p class="mt-7 text-sm leading-6 text-[#6f5e54]">Je hoeft deze pagina niet open te houden. Mollie meldt statuswijzigingen ook rechtstreeks aan de server.</p>
            @endif

            <a href="{{ route('trial-week.show') }}" class="mt-7 inline-flex min-h-11 items-center justify-center rounded-xl bg-[#a9472b] px-5 text-sm font-black text-white">Bekijk mijn toegang</a>
        </section>
    </main>
</body>
</html>
