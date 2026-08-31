<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Regel in een fictief Spaans stationsgesprek een treinreis, controleer de vertrektijd en vraag naar het perron.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>En la estación · Spaansspreken.nl</title>
    <link rel="preload" href="{{ asset('images/game/madrid-station-hall.webp') }}" as="image" type="image/webp">
    <link rel="preload" href="{{ asset('images/game/mateo-station-expressions.webp') }}" as="image" type="image/webp">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bakery-body station-body">
    <a href="#dialogue-content" class="hub-skip-link">Ga naar het gesprek met Mateo</a>

    <div
        class="bakery-app"
        data-scenario-dialogue
        data-station-dialogue
        data-scene="station_text_dialogue"
        data-scenario-slug="estacion-mateo"
        data-storage-key="station-text-dialogue-v1"
        data-npc-name="Mateo"
        data-source="{{ route('game.madrid.station.content', ['locale' => 'nl-NL']) }}"
        data-hub-route="{{ route('game.madrid') }}"
        data-transcription-url="{{ route('game.madrid.station.transcription') }}"
        data-assessment-url="{{ route('game.madrid.station.feedback') }}"
        data-authenticated="true"
        data-completion-url="{{ route('game.madrid.station.complete') }}"
        data-progress-url="{{ route('player.progress') }}"
    >
        <header class="bakery-topbar">
            <a href="{{ route('trial-week.show') }}" class="bakery-back-link"><span aria-hidden="true">←</span>Terug naar de proefweek</a>
            <div class="bakery-mission-meta">
                <a href="{{ route('player.progress') }}">Mijn voortgang</a>
                <span class="bakery-mode-chip">Fictieve oefenreis</span>
                <span data-level-chip>Niveau kiezen</span>
                <button type="button" data-translation-toggle aria-pressed="false">Nederlandse vertaling</button>
                <button type="button" data-restart-dialogue>Opnieuw beginnen</button>
            </div>
        </header>

        <main id="dialogue-content" class="bakery-main">
            <section class="bakery-heading" aria-labelledby="station-title">
                <div>
                    <p class="bakery-eyebrow">Dag 6 · Madrid · Estación del Centro</p>
                    <h1 id="station-title" data-mission-title>Op het station</h1>
                    <p data-mission-objective>De actuele, gepubliceerde dialoog wordt geladen.</p>
                </div>
                <div class="bakery-progress" aria-label="Missievoortgang">
                    <div><span data-progress-label>Voorbereiden</span><strong><span data-progress-current>0</span>/<span data-progress-total>5</span></strong></div>
                    <span class="bakery-progress-track" aria-hidden="true"><span data-progress-bar></span></span>
                </div>
            </section>

            <div class="bakery-status" data-dialogue-status role="status" aria-live="polite">We controleren of het goedgekeurde stationsgesprek beschikbaar is.</div>

            <section class="station-journey-card" data-journey-card aria-labelledby="station-journey-title" hidden>
                <div class="station-journey-heading">
                    <span aria-hidden="true">▤</span>
                    <div>
                        <p class="bakery-eyebrow">Voor je naar het loket gaat</p>
                        <h2 id="station-journey-title" data-journey-title>Jouw oefenreis</h2>
                    </div>
                </div>
                <dl data-journey-details></dl>
                <p class="station-journey-notice"><strong>Let op:</strong> <span data-journey-notice>Dit is een fictieve taalopdracht.</span></p>
            </section>

            <section class="bakery-stage" data-dialogue-stage hidden>
                <aside class="bakery-scene station-scene" aria-label="Een warme geïllustreerde Madrileense stationshal met loket en perrons">
                    <div class="bakery-scene-art station-scene-art" aria-hidden="true"></div>
                    <div class="bakery-scene-light station-scene-light" aria-hidden="true"></div>
                    <div class="station-board" aria-hidden="true"><span></span><span></span><span></span></div>

                    <div class="bakery-lucia-frame station-mateo-frame" data-npc-state="listening" aria-hidden="true">
                        <img src="{{ asset('images/game/mateo-station-expressions.webp') }}" width="1724" height="862" alt="" data-npc-expression-sheet>
                        <span class="bakery-lucia-reaction" data-npc-reaction>Mateo luistert</span>
                    </div>

                    <div class="bakery-npc-card station-npc-card">
                        <span class="bakery-npc-avatar station-avatar" aria-hidden="true">M</span>
                        <div>
                            <strong data-npc-name>Mateo Álvarez</strong>
                            <span><span lang="es" data-npc-role-es>empleado de estación</span> · <span data-npc-role-nl>stationsmedewerker</span></span>
                        </div>
                    </div>
                </aside>

                <div class="bakery-dialogue-column">
                    <section class="bakery-conversation" aria-labelledby="conversation-title">
                        <div class="bakery-turn-heading">
                            <div><p class="bakery-eyebrow">Mateo zegt</p><h2 id="conversation-title">Jij bent aan de beurt</h2></div>
                            <span data-turn-label>Beurt 1</span>
                        </div>

                        <div class="bakery-npc-bubble"><p lang="es" data-npc-line-es>Buenos días.</p><p data-npc-line-nl hidden></p></div>

                        <div class="bakery-feedback-card" data-feedback hidden role="status" aria-live="polite">
                            <div class="bakery-feedback-icon" aria-hidden="true">✓</div>
                            <div class="bakery-feedback-content">
                                <strong data-feedback-strength></strong>
                                <p data-feedback-focus></p>
                                <p lang="es" class="bakery-feedback-example" data-feedback-example></p>
                                <p class="bakery-feedback-note" data-feedback-note></p>
                                <details class="bakery-feedback-details" data-feedback-details hidden>
                                    <summary>Bekijk de rubric</summary>
                                    <div class="bakery-feedback-overall"><span>Gewogen resultaat</span><strong data-feedback-overall></strong></div>
                                    <ul data-feedback-rubric></ul>
                                </details>
                                <button type="button" class="bakery-feedback-retry" data-feedback-retry hidden>Probeer deze beurt opnieuw</button>
                            </div>
                        </div>

                        <form data-dialogue-form class="bakery-response-form">
                            <label for="station-player-response" data-step-prompt>Wat wil je zeggen?</label>
                            <section class="bakery-recorder" data-speech-recorder data-transcription-url="{{ route('game.madrid.station.transcription') }}" data-maximum-seconds="12" aria-labelledby="station-recorder-title">
                                <div class="bakery-recorder-heading">
                                    <div><span class="bakery-recorder-icon" aria-hidden="true">●</span><div><h3 id="station-recorder-title">Spreek je antwoord</h3><p>WebM/Opus · maximaal 12 seconden</p></div></div>
                                    <strong data-recording-timer>0:00 / 0:12</strong>
                                </div>
                                <p class="bakery-recorder-status" data-recorder-status role="status" aria-live="polite">De microfoon start pas wanneer jij op opnemen drukt.</p>
                                <div class="bakery-recorder-controls">
                                    <button type="button" class="bakery-record-button" data-record-start><span aria-hidden="true">●</span>Opnemen</button>
                                    <button type="button" data-record-stop hidden><span aria-hidden="true">■</span>Stop opname</button>
                                </div>
                                <div class="bakery-recording-preview" data-recording-preview hidden>
                                    <label for="station-speech-playback">Luister terug voordat je verzendt</label>
                                    <audio id="station-speech-playback" controls preload="metadata" data-recording-playback></audio>
                                    <div>
                                        <button type="button" data-record-retry>Opnieuw opnemen</button>
                                        <button type="button" class="bakery-transcribe-button" data-record-transcribe>Transcript maken <span aria-hidden="true">→</span></button>
                                    </div>
                                </div>
                                <p class="bakery-transcript-note" data-transcript-note hidden></p>
                            </section>

                            <details class="bakery-privacy-note"><summary>Wat gebeurt er met mijn antwoord?</summary><p>De opname wordt alleen na jouw klik voor Spaanse transcriptie verzonden. Audio, antwoord, transcript en feedback worden niet als accountvoortgang opgeslagen. De reisgegevens zijn fictieve leercontent en vormen geen boeking. <a href="{{ route('privacy') }}#spraakopnamen">Lees het privacybeleid.</a></p></details>
                            <div class="bakery-input-divider" aria-hidden="true"><span>of typ je antwoord</span></div>
                            <div class="bakery-input-row">
                                <input id="station-player-response" name="response" type="text" autocomplete="off" spellcheck="false" lang="es" data-player-response required>
                                <button type="submit">Gebruik antwoord</button>
                            </div>
                            <div class="bakery-assist-row"><button type="button" data-hint-toggle aria-expanded="false">Toon een hint</button><p data-step-hint hidden></p></div>
                            <details class="bakery-choice-assist"><summary>Ik wil een voorbeeldzin</summary><div data-choice-list></div></details>
                        </form>
                        <button type="button" class="bakery-continue-button" data-dialogue-continue hidden>Verder met het gesprek <span aria-hidden="true">→</span></button>
                    </section>

                    <aside class="bakery-history" aria-labelledby="station-history-title">
                        <div><p class="bakery-eyebrow">Gespreksverloop</p><h2 id="station-history-title">Jouw gesprek met Mateo</h2></div>
                        <ol data-dialogue-history><li data-history-empty>Nog geen antwoorden. Mateo wacht rustig op je.</li></ol>
                    </aside>
                </div>
            </section>

            <section class="bakery-level-select" data-level-select aria-labelledby="station-level-title" hidden>
                <p class="bakery-eyebrow">Voor je naar het loket gaat</p>
                <h2 id="station-level-title">Hoeveel steun wil je?</h2>
                <p>Elk niveau gebruikt dezelfde fictieve reis. Alleen de onverwachte keuze bij de vertrektijd verandert.</p>
                <div>
                    <button type="button" data-level="A0"><strong>A0 · Veel steun</strong><span>Kies uit twee duidelijke vertrektijden.</span></button>
                    <button type="button" data-level="A1"><strong>A1 · Een beetje steun</strong><span>Accepteer een latere trein wanneer je eerste keuze vol is.</span></button>
                    <button type="button" data-level="A2"><strong>A2 · Zelf proberen</strong><span>Vergelijk een overstap met een rechtstreekse trein en motiveer je keuze.</span></button>
                </div>
            </section>

            <section class="bakery-complete" data-dialogue-complete hidden tabindex="-1" aria-labelledby="station-complete-title">
                <div class="bakery-complete-hero station-complete-hero">
                    <div class="bakery-complete-lucia station-complete-mateo" aria-hidden="true">
                        <img src="{{ asset('images/game/mateo-station-expressions.webp') }}" width="1724" height="862" alt="" data-npc-expression-sheet-complete>
                    </div>
                    <div>
                        <div class="bakery-stamp"><span>ESTACIÓN</span><strong>VIAJE</strong><small>✓</small></div>
                        <p class="bakery-eyebrow">Dag 6 voltooid</p>
                        <h2 id="station-complete-title">¡Ya tienes tu billete!</h2>
                        <p lang="es" data-farewell-es></p><p data-farewell-nl hidden></p>
                    </div>
                </div>
                <dl class="bakery-rewards">
                    <div><dt>XP</dt><dd data-reward-xp>120</dd></div>
                    <div><dt>Confianza</dt><dd data-reward-confidence>+1</dd></div>
                    <div><dt>Valentía</dt><dd data-reward-courage>+1</dd></div>
                </dl>
                <div class="bakery-spoken-goal" data-spoken-goal><span aria-hidden="true">●</span><div><strong>Spreekdoel: <span data-spoken-turns>0</span>/3 beurten</strong><p data-spoken-goal-message>Je kunt de missie met tekst afronden en later opnieuw spreken.</p></div></div>
                <div class="bakery-reward-cards">
                    <div><span aria-hidden="true">▣</span><p>Paspoortstempel</p><strong data-reward-stamp></strong></div>
                    <div><span aria-hidden="true">◇</span><p>Verzamelitem</p><strong data-reward-item></strong></div>
                    <div data-repair-badge hidden><span aria-hidden="true">★</span><p>Bonusbadge</p><strong data-reward-badge></strong></div>
                </div>
                <div class="bakery-account-sync" data-account-sync>
                    <div role="status" aria-live="polite"><strong data-account-sync-title>Je missieresultaat is lokaal klaar.</strong><p data-account-sync-message></p><p data-account-balances hidden></p></div>
                    <button type="button" data-account-sync-retry hidden>Opnieuw opslaan</button>
                </div>
                <div class="bakery-complete-actions">
                    <a href="{{ route('trial-week.show') }}">Terug naar de proefweek</a>
                    <a href="{{ route('player.progress') }}">Bekijk mijn voortgang</a>
                    <button type="button" data-replay-dialogue>Speel opnieuw</button>
                </div>
            </section>

            <section class="bakery-error" data-dialogue-error hidden aria-labelledby="station-error-title">
                <span aria-hidden="true">▤</span>
                <h2 id="station-error-title">Mateo kan het loket nog niet openen</h2>
                <p>Publiceer het gespreksscenario <strong>estacion-mateo</strong> met de gereviewde scène en karakterasset via een productierelease om dag 6 te openen.</p>
                <button type="button" data-dialogue-retry>Probeer opnieuw</button>
                <a href="{{ route('trial-week.show') }}">Terug naar de proefweek</a>
            </section>

            <noscript><section class="bakery-error"><h2>JavaScript is nodig voor deze dialoog</h2><p>Gebruik een browser met JavaScript om de actieve gespreksroute te spelen.</p></section></noscript>
        </main>
    </div>
</body>
</html>
