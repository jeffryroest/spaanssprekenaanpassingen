<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Oefen een volledige Spaanse taxirit met Diego in Madrid.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>En taxi · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bakery-body taxi-body">
    <a href="#dialogue-content" class="hub-skip-link">Ga naar het gesprek met Diego</a>

    <div
        class="bakery-app"
        data-scenario-dialogue
        data-taxi-dialogue
        data-scene="taxi_text_dialogue"
        data-scenario-slug="taxi-diego"
        data-storage-key="taxi-text-dialogue-v1"
        data-npc-name="Diego"
        data-source="{{ route('game.madrid.taxi.content', ['locale' => 'nl-NL']) }}"
        data-hub-route="{{ route('game.madrid') }}"
        data-transcription-url="{{ route('game.madrid.taxi.transcription') }}"
        data-assessment-url="{{ route('game.madrid.taxi.feedback') }}"
        data-authenticated="true"
        data-completion-url="{{ route('game.madrid.taxi.complete') }}"
        data-progress-url="{{ route('player.progress') }}"
    >
        <header class="bakery-topbar">
            <a href="{{ route('trial-week.show') }}" class="bakery-back-link">
                <span aria-hidden="true">←</span>
                Terug naar de proefweek
            </a>

            <div class="bakery-mission-meta">
                <a href="{{ route('player.progress') }}">Mijn voortgang</a>
                <span class="bakery-mode-chip">Spreken + tekst</span>
                <span data-level-chip>Niveau kiezen</span>
                <button type="button" data-translation-toggle aria-pressed="false">Nederlandse vertaling</button>
                <button type="button" data-restart-dialogue>Opnieuw beginnen</button>
            </div>
        </header>

        <main id="dialogue-content" class="bakery-main">
            <section class="bakery-heading" aria-labelledby="taxi-title">
                <div>
                    <p class="bakery-eyebrow">Dag 2 · Madrid · En taxi</p>
                    <h1 id="taxi-title" data-mission-title>Mijn eerste taxirit</h1>
                    <p data-mission-objective>De actuele, gepubliceerde dialoog wordt geladen.</p>
                </div>

                <div class="bakery-progress" aria-label="Missievoortgang">
                    <div>
                        <span data-progress-label>Voorbereiden</span>
                        <strong><span data-progress-current>0</span>/<span data-progress-total>5</span></strong>
                    </div>
                    <span class="bakery-progress-track" aria-hidden="true"><span data-progress-bar></span></span>
                </div>
            </section>

            <div class="bakery-status" data-dialogue-status role="status" aria-live="polite">
                We controleren of de goedgekeurde taxidialoog beschikbaar is.
            </div>

            <section class="bakery-stage" data-dialogue-stage hidden>
                <aside class="bakery-scene taxi-scene" aria-label="Een gele taxi rijdt door Madrid">
                    <div class="taxi-sky" aria-hidden="true"></div>
                    <div class="taxi-city" aria-hidden="true">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <div class="taxi-road" aria-hidden="true"></div>
                    <div class="taxi-car" aria-hidden="true">
                        <span class="taxi-sign">TAXI</span>
                        <span class="taxi-window taxi-window-front"></span>
                        <span class="taxi-window taxi-window-back"></span>
                        <span class="taxi-wheel taxi-wheel-front"></span>
                        <span class="taxi-wheel taxi-wheel-back"></span>
                    </div>

                    <div class="bakery-npc-card taxi-npc-card">
                        <span class="bakery-npc-avatar" aria-hidden="true">D</span>
                        <div>
                            <strong data-npc-name>Diego Ruiz</strong>
                            <span><span lang="es" data-npc-role-es>taxista</span> · <span data-npc-role-nl>taxichauffeur</span></span>
                        </div>
                    </div>
                </aside>

                <div class="bakery-dialogue-column">
                    <section class="bakery-conversation" aria-labelledby="conversation-title">
                        <div class="bakery-turn-heading">
                            <div>
                                <p class="bakery-eyebrow">Diego zegt</p>
                                <h2 id="conversation-title">Jij bent aan de beurt</h2>
                            </div>
                            <span data-turn-label>Beurt 1</span>
                        </div>

                        <div class="bakery-npc-bubble">
                            <p lang="es" data-npc-line-es>Buenas tardes.</p>
                            <p data-npc-line-nl hidden></p>
                        </div>

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
                            <label for="taxi-player-response" data-step-prompt>Wat wil je zeggen?</label>

                            <section
                                class="bakery-recorder"
                                data-speech-recorder
                                data-transcription-url="{{ route('game.madrid.taxi.transcription') }}"
                                data-maximum-seconds="12"
                                aria-labelledby="taxi-recorder-title"
                            >
                                <div class="bakery-recorder-heading">
                                    <div>
                                        <span class="bakery-recorder-icon" aria-hidden="true">●</span>
                                        <div><h3 id="taxi-recorder-title">Spreek je antwoord</h3><p>WebM/Opus · maximaal 12 seconden</p></div>
                                    </div>
                                    <strong data-recording-timer>0:00 / 0:12</strong>
                                </div>
                                <p class="bakery-recorder-status" data-recorder-status role="status" aria-live="polite">De microfoon start pas wanneer jij op opnemen drukt.</p>
                                <div class="bakery-recorder-controls">
                                    <button type="button" class="bakery-record-button" data-record-start><span aria-hidden="true">●</span>Opnemen</button>
                                    <button type="button" data-record-stop hidden><span aria-hidden="true">■</span>Stop opname</button>
                                </div>
                                <div class="bakery-recording-preview" data-recording-preview hidden>
                                    <label for="taxi-speech-playback">Luister terug voordat je verzendt</label>
                                    <audio id="taxi-speech-playback" controls preload="metadata" data-recording-playback></audio>
                                    <div>
                                        <button type="button" data-record-retry>Opnieuw opnemen</button>
                                        <button type="button" class="bakery-transcribe-button" data-record-transcribe>Transcript maken <span aria-hidden="true">→</span></button>
                                    </div>
                                </div>
                                <p class="bakery-transcript-note" data-transcript-note hidden></p>
                            </section>

                            <p class="bakery-privacy-note">Je opname wordt alleen na jouw klik voor Spaanse transcriptie verzonden. Alleen het gecontroleerde transcript gaat naar de feedbacklaag; audio, transcript en feedback worden niet als voortgang opgeslagen. <a href="{{ route('privacy') }}#spraakopnamen">Lees het privacybeleid.</a></p>
                            <div class="bakery-input-divider" aria-hidden="true"><span>of typ je antwoord</span></div>
                            <div class="bakery-input-row">
                                <input id="taxi-player-response" name="response" type="text" autocomplete="off" spellcheck="false" lang="es" data-player-response required>
                                <button type="submit">Gebruik antwoord</button>
                            </div>
                            <div class="bakery-assist-row"><button type="button" data-hint-toggle aria-expanded="false">Toon een hint</button><p data-step-hint hidden></p></div>
                            <fieldset class="bakery-choice-assist"><legend>Of kies een voorbeeldzin</legend><div data-choice-list></div></fieldset>
                        </form>

                        <button type="button" class="bakery-continue-button" data-dialogue-continue hidden>Verder met de rit <span aria-hidden="true">→</span></button>
                    </section>

                    <aside class="bakery-history" aria-labelledby="taxi-history-title">
                        <div><p class="bakery-eyebrow">Gespreksverloop</p><h2 id="taxi-history-title">Wat je al hebt gezegd</h2></div>
                        <ol data-dialogue-history><li data-history-empty>Nog geen antwoorden. Diego wacht rustig op je.</li></ol>
                    </aside>
                </div>
            </section>

            <section class="bakery-level-select" data-level-select aria-labelledby="taxi-level-title" hidden>
                <p class="bakery-eyebrow">Voor je instapt</p>
                <h2 id="taxi-level-title">Hoeveel steun wil je?</h2>
                <p>Dit bepaalt alleen welke onverwachte routevraag Diego stelt. Je formuleert ieder antwoord zelf; voorbeeldzinnen blijven optionele hulp.</p>
                <div>
                    <button type="button" data-level="A0"><strong>A0 · Veel steun</strong><span>Diego vraagt alleen om je bestemming te bevestigen.</span></button>
                    <button type="button" data-level="A1"><strong>A1 · Een beetje steun</strong><span>Druk verkeer vraagt om een alternatieve route.</span></button>
                    <button type="button" data-level="A2"><strong>A2 · Zelf proberen</strong><span>Je kiest tussen de snelste en voordeligste route.</span></button>
                </div>
            </section>

            <section class="bakery-complete" data-dialogue-complete hidden tabindex="-1" aria-labelledby="taxi-complete-title">
                <div class="bakery-stamp" aria-hidden="true">✓</div>
                <p class="bakery-eyebrow">Dag 2 voltooid</p>
                <h2 id="taxi-complete-title">¡Has llegado!</h2>
                <p lang="es" data-farewell-es></p><p data-farewell-nl hidden></p>
                <dl class="bakery-rewards">
                    <div><dt>XP</dt><dd data-reward-xp>90</dd></div>
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

            <section class="bakery-error" data-dialogue-error hidden aria-labelledby="taxi-error-title">
                <span aria-hidden="true">🚕</span>
                <h2 id="taxi-error-title">Diego kan nog niet vertrekken</h2>
                <p>Publiceer het gespreksscenario <strong>taxi-diego</strong> via een productierelease om dag 2 te openen.</p>
                <button type="button" data-dialogue-retry>Probeer opnieuw</button>
                <a href="{{ route('trial-week.show') }}">Terug naar de proefweek</a>
            </section>

            <noscript><section class="bakery-error"><h2>JavaScript is nodig voor deze dialoog</h2><p>Gebruik een browser met JavaScript om de actieve gespreksroute te spelen.</p></section></noscript>
        </main>
    </div>
</body>
</html>
