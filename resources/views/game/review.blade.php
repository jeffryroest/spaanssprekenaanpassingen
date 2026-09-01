<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Herhaal persoonlijke Spaanse spreekkaarten uit missies die je al hebt voltooid.">
    <title>Mi repaso · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="review-body">
    <a href="#review-content" class="hub-skip-link">Ga naar je herhaling</a>

    <main
        id="review-content"
        class="review-shell"
        data-personal-review
        data-completion-url="{{ route('game.madrid.review.complete') }}"
    >
        <header class="review-topbar">
            <a href="{{ route('trial-week.show') }}" class="bakery-back-link"><span aria-hidden="true">←</span>Terug naar de proefweek</a>
            <div class="review-day-mark"><span>4</span><strong>Mi repaso</strong></div>
            <a href="{{ route('player.progress') }}" class="bakery-back-link">Mijn voortgang</a>
        </header>

        <section class="review-hero" aria-labelledby="review-title">
            <div>
                <p class="bakery-eyebrow">Dag 4 · persoonlijke herhaling</p>
                <h1 id="review-title">Zinnen die voor jou terugkomen</h1>
                <p>Je oefent alleen situaties die je eerder werkelijk hebt gespeeld. Zeg je antwoord hardop of typ het, kijk daarna pas naar een voorbeeld en kies eerlijk hoe het ging.</p>
            </div>
            <aside class="review-privacy-note">
                <span aria-hidden="true">◎</span>
                <div><strong>Jouw antwoord blijft vluchtig</strong><p>We bewaren alleen kaart-id, bronsoort, hulpgebruik en jouw beoordeling—geen opname, antwoord of transcript.</p></div>
            </aside>
        </section>

        <script type="application/json" data-review-deck>@json($deck, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>

        <section class="review-empty" data-review-empty {{ count($deck['cards']) > 0 ? 'hidden' : '' }} aria-labelledby="review-empty-title">
            <span aria-hidden="true">◇</span>
            @if($deck['meta']['has_practice_history'])
                <p class="bakery-eyebrow">Alles is bijgewerkt</p>
                <h2 id="review-empty-title">Voor nu ben je klaar</h2>
                <p>Je geoefende kaarten komen automatisch terug wanneer hun interval voorbij is. Je kunt intussen een andere missie spelen.</p>
                <a href="{{ route('trial-week.show') }}">Kies een andere missiedag</a>
            @else
                <p class="bakery-eyebrow">Je kaartenbak is nog leeg</p>
                <h2 id="review-empty-title">Speel eerst een gesprek</h2>
                <p>Na een voltooide missie kiezen we maximaal vijf zinnen uit jouw gespeelde route. Er wordt geen antwoordtekst bewaard om dit mogelijk te maken.</p>
                <a href="{{ route('game.madrid.panaderia') }}">Start bij La Espiga</a>
            @endif
        </section>

        <section class="review-stage" data-review-stage {{ count($deck['cards']) === 0 ? 'hidden' : '' }} aria-labelledby="review-card-title">
            <div class="review-progress-row">
                <div><span data-review-position>Kaart 1</span><strong data-review-total>van {{ count($deck['cards']) }}</strong></div>
                <div class="review-progress-track" aria-hidden="true"><span data-review-progress></span></div>
                <span class="review-due-pill" data-review-due>Voor jou gekozen</span>
            </div>

            <div class="review-layout">
                <aside class="review-scene" aria-label="Je persoonlijke herhoek in Madrid">
                    <div class="review-scene-art" aria-hidden="true"></div>
                    <div class="review-source-card">
                        <span class="review-avatar" data-review-avatar aria-hidden="true">M</span>
                        <div><strong data-review-npc>Madrid</strong><span data-review-setting>Jouw eerdere missie</span></div>
                    </div>
                    <blockquote>
                        <span lang="es" data-review-npc-es></span>
                        <small data-review-npc-nl></small>
                    </blockquote>
                </aside>

                <div class="review-card-panel">
                    <p class="bakery-eyebrow" data-review-mission>Persoonlijke kaart</p>
                    <h2 id="review-card-title" data-review-prompt>Zeg je antwoord in het Spaans</h2>

                    <section class="bakery-recorder review-recorder" data-speech-recorder data-transcription-url="{{ route('game.madrid.review.transcription') }}" data-maximum-seconds="12" aria-labelledby="review-recorder-title">
                        <div class="bakery-recorder-heading">
                            <div><span class="bakery-recorder-icon" aria-hidden="true">●</span><div><h3 id="review-recorder-title">Spreek je zin</h3><p>WebM/Opus · maximaal 12 seconden</p></div></div>
                            <span class="bakery-recorder-timer" data-recording-timer>0:00 / 0:12</span>
                        </div>
                        <p class="bakery-recorder-privacy"><strong>Jij start de microfoon.</strong> De opname wordt alleen voor transcriptie verzonden en niet opgeslagen.</p>
                        <div class="bakery-recorder-actions">
                            <button type="button" data-record-start>Neem op</button>
                            <button type="button" data-record-stop hidden>Stop</button>
                        </div>
                        <p class="bakery-recorder-status" data-recorder-status role="status" aria-live="polite">De microfoon start pas wanneer jij op opnemen drukt.</p>
                        <div class="bakery-recording-preview" data-recording-preview hidden>
                            <label for="review-speech-playback">Luister terug voordat je het transcript maakt</label>
                            <audio id="review-speech-playback" controls preload="metadata" data-recording-playback></audio>
                            <div><button type="button" data-record-retry>Opnieuw</button><button type="button" data-record-transcribe>Maak transcript</button></div>
                        </div>
                        <p class="bakery-transcript-note" data-transcript-note hidden></p>
                    </section>

                    <label for="review-response" class="review-response-label">Of typ je Spaanse zin</label>
                    <input id="review-response" type="text" lang="es" autocomplete="off" spellcheck="false" data-player-response data-review-response placeholder="Schrijf hier wat je zou zeggen">

                    <div class="review-actions">
                        <button type="button" class="review-help-button" data-review-help>Ik wil een voorbeeld</button>
                        <button type="button" class="review-check-button" data-review-check>Bekijk het voorbeeld</button>
                    </div>
                    <p class="review-status" data-review-status role="status" aria-live="polite">Probeer de zin eerst zelf. Fouten maken hoort bij ophalen uit je geheugen.</p>

                    <section class="review-answer" data-review-answer hidden aria-labelledby="review-answer-title">
                        <p class="bakery-eyebrow">Vergelijk, niet kopiëren</p>
                        <h3 id="review-answer-title">Een bruikbaar voorbeeld</h3>
                        <p lang="es" data-review-example></p>
                        <p data-review-hint></p>
                    </section>

                    <fieldset class="review-ratings" data-review-ratings hidden>
                        <legend>Hoe ging het ophalen?</legend>
                        <button type="button" data-review-rating="again"><strong>Nog eens</strong><span>Over 10 minuten</span></button>
                        <button type="button" data-review-rating="hard"><strong>Moeilijk</strong><span>Binnenkort terug</span></button>
                        <button type="button" data-review-rating="good"><strong>Goed</strong><span>Over enkele dagen</span></button>
                        <button type="button" data-review-rating="easy"><strong>Makkelijk</strong><span>Later terug</span></button>
                    </fieldset>
                </div>
            </div>
        </section>

        <section class="review-complete" data-review-complete hidden tabindex="-1" aria-labelledby="review-complete-title">
            <span aria-hidden="true">✓</span>
            <p class="bakery-eyebrow">Herhaling afgerond</p>
            <h2 id="review-complete-title">Je kaarten zijn opnieuw gepland</h2>
            <p data-review-complete-message>Je persoonlijke herhaling staat veilig in je account.</p>
            <div class="review-reward" data-review-reward hidden><strong data-review-earned-xp>0 XP</strong><span data-review-earned-confidence>0 Confianza</span></div>
            <div class="review-complete-actions"><a href="{{ route('trial-week.show') }}">Terug naar de proefweek</a><button type="button" data-review-retry-save hidden>Opslaan opnieuw proberen</button></div>
        </section>
    </main>
</body>
</html>
