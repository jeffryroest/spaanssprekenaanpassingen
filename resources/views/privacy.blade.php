<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Technische privacy-uitleg voor spraakoefeningen op Spaansspreken.nl.">
    <title>Privacybeleid · Spaansspreken.nl</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <main class="mx-auto max-w-3xl px-5 py-12 sm:py-20">
        <a href="{{ route('game.madrid') }}" class="text-sm font-semibold text-orange-300 underline underline-offset-4">← Terug naar Madrid</a>

        <p class="mt-10 text-sm font-bold uppercase tracking-[0.18em] text-orange-300">Spaansspreken.nl</p>
        <h1 class="mt-3 text-4xl font-black tracking-tight text-white sm:text-5xl">Privacy bij spraakoefeningen</h1>
        <p class="mt-5 text-lg leading-8 text-stone-300">Deze pagina beschrijft wat de huidige speelbare versie technisch met je opname en transcript doet.</p>

        <section id="spraakopnamen" class="mt-10 rounded-3xl border border-white/10 bg-white/[0.06] p-6 sm:p-8">
            <h2 class="text-2xl font-bold text-white">Spraakopnamen</h2>
            <ul class="mt-5 list-disc space-y-3 pl-5 leading-7 text-stone-300">
                <li>De microfoon start uitsluitend nadat je zelf op <strong class="text-white">Opnemen</strong> drukt.</li>
                <li>De browser maakt lokaal een WebM/Opus-opname van maximaal 12 seconden en laat je die eerst terugluisteren.</li>
                <li>Pas na <strong class="text-white">Transcript maken</strong> wordt de opname beveiligd naar de server verzonden en doorgestuurd naar de ingestelde transcriptiedienst. In deze fase is dat OpenAI.</li>
                <li>De applicatie schrijft de ruwe opname niet naar de database, bestandsopslag of productanalytics.</li>
                <li>Na een geslaagde beurt wordt alleen je gecontroleerde transcript, met de gepubliceerde oefencontext en technische bronmetadata, naar OpenAI gestuurd om persoonlijke feedback te maken. De opname wordt niet opnieuw meegestuurd.</li>
                <li>De feedbackservice bewaart het antwoord of de feedback niet in de applicatiedatabase en gebruikt die gegevens niet voor voortgang, beloningen of productanalytics.</li>
                <li>Bij gewone missies bewaart je browser het antwoord tijdelijk in de sessie zodat je in hetzelfde tabblad kunt hervatten. In het fictieve gezondheidsrollenspel worden antwoord- en NPC-tekst vóór iedere schrijfactie uit <code>sessionStorage</code> verwijderd; alleen stap-id, invoerbron en hulpgebruik blijven lokaal hervatbaar.</li>
            </ul>
        </section>

        <section class="mt-6 rounded-3xl border border-white/10 bg-white/[0.06] p-6 sm:p-8">
            <h2 class="text-2xl font-bold text-white">Fictief gezondheidsrollenspel</h2>
            <p class="mt-4 leading-7 text-stone-300">Consulta La Luz gebruikt een vooraf ingevulde fictieve rolkaart. Deel daar geen echte medische gegevens. De feedback beoordeelt uitsluitend je Spaanse taal en communicatieve taakuitvoering en geeft geen medische beoordeling, diagnose of advies.</p>
        </section>

        <section class="mt-6 rounded-3xl border border-white/10 bg-white/[0.06] p-6 sm:p-8">
            <h2 class="text-2xl font-bold text-white">Jouw keuze</h2>
            <p class="mt-4 leading-7 text-stone-300">Je kunt een opname opnieuw maken of helemaal niet verzenden. Weiger je microfoontoestemming, dan blijft de volledige dialoog speelbaar met tekst en voorbeeldzinnen. Het intrekken van browsertoestemming regel je via de site-instellingen van je browser.</p>
        </section>

        <section id="accountvoortgang" class="mt-6 rounded-3xl border border-white/10 bg-white/[0.06] p-6 sm:p-8">
            <h2 class="text-2xl font-bold text-white">Accountvoortgang</h2>
            <p class="mt-4 leading-7 text-stone-300">Als je bent ingelogd, bewaren we na een voltooide missie de gebruikte gepubliceerde contentversie, stap-id's, invoerbron, hulpgebruik, afgeleide missiestates, XP/Confianza/Valentía en unieke beloningssleutels. Zo blijven je resultaat en ontgrendelingen na opnieuw inloggen beschikbaar.</p>
            <p class="mt-4 leading-7 text-stone-300">De voortgangsopslag bevat geen ruwe opname, antwoordtekst, transcript, transcript-confidence, spelercorrectie, AI-feedback of gezondheidsinformatie. Die inhoud wordt ook niet in het accountdashboard teruggegeven.</p>
            <p class="mt-4 leading-7 text-stone-300">Voor persoonlijke herhaling bewaren we daarnaast een gehashte kaart-id, bronmissie en -versie, stap-id, invoerbron, hulpgebruik, jouw keuze ‘nog eens/moeilijk/goed/makkelijk’ en de volgende oefendatum. Je Spaanse oefenantwoord wordt vóór het wisselen van kaart uit de pagina verwijderd en niet meegestuurd in het opslagverzoek.</p>
        </section>

        <p class="mt-8 text-sm leading-6 text-stone-400">Een bredere juridische privacy- en retentiereview staat gepland vóór de gesloten bèta. Deze technische uitleg wordt bijgewerkt wanneer de gegevensverwerking verandert.</p>
    </main>
</body>
</html>
