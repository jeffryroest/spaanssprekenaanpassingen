<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Oefen een volledig Spaans restaurantgesprek met Carmen in Café El Reloj.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>En el restaurante · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bakery-body restaurant-body">
    <a href="#dialogue-content" class="hub-skip-link">Ga naar het gesprek met Carmen</a>

    <div
        class="bakery-app"
        data-scenario-dialogue
        data-restaurant-dialogue
        data-scene="restaurant_text_dialogue"
        data-scenario-slug="restaurant-el-reloj"
        data-storage-key="restaurant-text-dialogue-v1"
        data-npc-name="Carmen"
        data-source="{{ route('game.madrid.restaurant.content', ['locale' => 'nl-NL']) }}"
        data-hub-route="{{ route('game.madrid') }}"
        data-transcription-url="{{ route('game.madrid.restaurant.transcription') }}"
        data-assessment-url="{{ route('game.madrid.restaurant.feedback') }}"
        data-authenticated="true"
        data-completion-url="{{ route('game.madrid.restaurant.complete') }}"
        data-progress-url="{{ route('player.progress') }}"
    >
        <header class="bakery-topbar">
            <a href="{{ route('trial-week.show') }}" class="bakery-back-link"><span aria-hidden="true">←</span>Terug naar de proefweek</a>
            <div class="bakery-mission-meta">
                <a href="{{ route('player.progress') }}">Mijn voortgang</a>
                <span class="bakery-mode-chip">Spreken + tekst</span>
                <span data-level-chip>Niveau kiezen</span>
                <button type="button" data-translation-toggle aria-pressed="false">Nederlandse vertaling</button>
                <button type="button" data-restart-dialogue>Opnieuw beginnen</button>
            </div>
        </header>

        <main id="dialogue-content" class="bakery-main">
            <section class="bakery-heading" aria-labelledby="restaurant-title">
                <div>
                    <p class="bakery-eyebrow">Dag 3 · Madrid · Café El Reloj</p>
                    <h1 id="restaurant-title" data-mission-title>Mijn eerste diner</h1>
                    <p data-mission-objective>De actuele, gepubliceerde dialoog wordt geladen.</p>
                </div>
                <div class="bakery-progress" aria-label="Missievoortgang">
                    <div><span data-progress-label>Voorbereiden</span><strong><span data-progress-current>0</span>/<span data-progress-total>5</span></strong></div>
                    <span class="bakery-progress-track" aria-hidden="true"><span data-progress-bar></span></span>
                </div>
            </section>

            <div class="bakery-status" data-dialogue-status role="status" aria-live="polite">We controleren of het goedgekeurde restaurantgesprek beschikbaar is.</div>

            <section class="bakery-stage" data-dialogue-stage hidden>
                <aside class="bakery-scene restaurant-scene" aria-label="Een warme tafel in Café El Reloj">
                    <div class="restaurant-wall" aria-hidden="true">
                        <span class="restaurant-clock"><i></i><b></b></span>
                        <span class="restaurant-window"><i></i></span>
                        <span class="restaurant-menu">MENÚ<br><small>del día</small></span>
                    </div>
                    <div class="restaurant-tiles" aria-hidden="true"></div>
                    <div class="restaurant-table" aria-hidden="true">
                        <span class="restaurant-plate"></span>
                        <span class="restaurant-glass"></span>
                        <span class="restaurant-bottle"></span>
                        <span class="restaurant-candle"></span>
                    </div>
                    <div class="bakery-npc-card restaurant-npc-card">
                        <span class="bakery-npc-avatar restaurant-avatar" aria-hidden="true">C</span>
                        <div>
                            <strong data-npc-name>Carmen Santos</strong>
                            <span><span lang="es" data-npc-role-es>camarera</span> · <span data-npc-role-nl>serveerster</span></span>
                        </div>
                    </div>
                </aside>

                <div class="bakery-dialogue-column">
                    <section class="bakery-conversation" aria-labelledby="conversation-title">
                        <div class="bakery-turn-heading">
                            <div><p class="bakery-eyebrow">Carmen zegt</p><h2 id="conversation-title">Jij bent aan de beurt</h2></div>
                            <span data-turn-label>Beurt 1</span>
                        </div>

                        <div class="bakery-npc-bubble"><p lang="es" data-npc-line-es>Buenas noches.</p><p data-npc-line-nl hidden></p></div>

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
                            <label for="restaurant-player-response" data-step-prompt>Wat wil je zeggen?</label>
                            <section class="bakery-recorder" data-speech-recorder data-transcription-url="{{ route('game.madrid.restaurant.transcription') }}" data-maximum-seconds="12" aria-labelledby="restaurant-recorder-title">
                                <div class="bakery-recorder-heading">
                                    <div><span class="bakery-recorder-icon" aria-hidden="true">●</span><div><h3 id="restaurant-recorder-title">Spreek je antwoord</h3><p>WebM/Opus · maximaal 12 seconden</p></div></div>
                                    <strong data-recording-timer>0:00 / 0:12</strong>
                                </div>
                                <p class="bakery-recorder-status" data-recorder-status role="status" aria-live="polite">De microfoon start pas wanneer jij op opnemen drukt.</p>
                                <div class="bakery-recorder-controls">
                                    <button type="button" class="bakery-record-button" data-record-start><span aria-hidden="true">●</span>Opnemen</button>
                                    <button type="button" data-record-stop hidden><span aria-hidden="true">■</span>Stop opname</button>
                                </div>
                                <div class="bakery-recording-preview" data-recording-preview hidden>
                                    <label for="restaurant-speech-playback">Luister terug voordat je verzendt</label>
                                    <audio id="restaurant-speech-playback" controls preload="metadata" data-recording-playback></audio>
                                    <div>
                                        <button type="button" data-record-retry>Opnieuw opnemen</button>
                                        <button type="button" class="bakery-transcribe-button" data-record-transcribe>Transcript maken <span aria-hidden="true">→</span></button>
                                    </div>
                                </div>
                                <p class="bakery-transcript-note" data-transcript-note hidden></p>
                            </section>

                            <details class="bakery-privacy-note"><summary>Wat gebeurt er met mijn opname?</summary><p>Je opname wordt alleen na jouw klik voor Spaanse transcriptie verzonden. Alleen het gecontroleerde transcript gaat naar de feedbacklaag; audio, transcript en feedback worden niet als voortgang opgeslagen. <a href="{{ route('privacy') }}#spraakopnamen">Lees het privacybeleid.</a></p></details>
                            <div class="bakery-input-divider" aria-hidden="true"><span>of typ je antwoord</span></div>
                            <div class="bakery-input-row">
                                <input id="restaurant-player-response" name="response" type="text" autocomplete="off" spellcheck="false" lang="es" data-player-response required>
                                <button type="submit">Gebruik antwoord</button>
                            </div>
                            <div class="bakery-assist-row"><button type="button" data-hint-toggle aria-expanded="false">Toon een hint</button><p data-step-hint hidden></p></div>
                            <details class="bakery-choice-assist"><summary>Ik wil een voorbeeldzin</summary><div data-choice-list></div></details>
                        </form>
                        <button type="button" class="bakery-continue-button" data-dialogue-continue hidden>Verder met het diner <span aria-hidden="true">→</span></button>
                    </section>

                    <aside class="bakery-history" aria-labelledby="restaurant-history-title">
                        <div><p class="bakery-eyebrow">Gespreksverloop</p><h2 id="restaurant-history-title">Wat je al hebt gezegd</h2></div>
                        <ol data-dialogue-history><li data-history-empty>Nog geen antwoorden. Carmen wacht rustig op je.</li></ol>
                    </aside>
                </div>
            </section>

            <section class="bakery-level-select" data-level-select aria-labelledby="restaurant-level-title" hidden>
                <p class="bakery-eyebrow">Voor je aan tafel gaat</p>
                <h2 id="restaurant-level-title">Hoeveel steun wil je?</h2>
                <p>Dit bepaalt alleen welke onverwachte vraag Carmen stelt. Je formuleert ieder antwoord zelf; voorbeeldzinnen blijven optionele hulp.</p>
                <div>
                    <button type="button" data-level="A0"><strong>A0 · Veel steun</strong><span>Carmen vraagt alleen: met of zonder bubbels?</span></button>
                    <button type="button" data-level="A1"><strong>A1 · Een beetje steun</strong><span>Je eerste drankje is op en je kiest een alternatief.</span></button>
                    <button type="button" data-level="A2"><strong>A2 · Zelf proberen</strong><span>Je vraagt om een alcoholvrije aanbeveling.</span></button>
                </div>
            </section>

            <section class="bakery-complete" data-dialogue-complete hidden tabindex="-1" aria-labelledby="restaurant-complete-title">
                <div class="bakery-stamp" aria-hidden="true">✓</div>
                <p class="bakery-eyebrow">Dag 3 voltooid</p>
                <h2 id="restaurant-complete-title">¡Buen provecho!</h2>
                <p lang="es" data-farewell-es></p><p data-farewell-nl hidden></p>
                <dl class="bakery-rewards">
                    <div><dt>XP</dt><dd data-reward-xp>100</dd></div>
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

            <section class="bakery-error" data-dialogue-error hidden aria-labelledby="restaurant-error-title">
                <span aria-hidden="true">🍽</span>
                <h2 id="restaurant-error-title">Carmen kan je tafel nog niet klaarmaken</h2>
                <p>Publiceer het gespreksscenario <strong>restaurant-el-reloj</strong> via een productierelease om dag 3 te openen.</p>
                <button type="button" data-dialogue-retry>Probeer opnieuw</button>
                <a href="{{ route('trial-week.show') }}">Terug naar de proefweek</a>
            </section>

            <noscript><section class="bakery-error"><h2>JavaScript is nodig voor deze dialoog</h2><p>Gebruik een browser met JavaScript om de actieve gespreksroute te spelen.</p></section></noscript>
        </main>
    </div>
</body>
</html>
