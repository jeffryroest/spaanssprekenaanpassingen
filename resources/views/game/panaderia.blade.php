<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Voer je eerste Spaanse gesprek met Lucía in Panadería La Espiga.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>La Espiga · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bakery-body">
    <a href="#dialogue-content" class="hub-skip-link">Ga naar het gesprek met Lucía</a>

    <div
        class="bakery-app"
        data-panaderia-dialogue
        data-source="{{ url('/api/v1/conversations/la-espiga-lucia?locale=nl-NL') }}"
        data-hub-route="{{ route('game.madrid') }}"
        data-transcription-url="{{ route('game.madrid.panaderia.transcription') }}"
        data-assessment-url="{{ route('game.madrid.panaderia.feedback') }}"
        data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
        data-completion-url="{{ route('game.madrid.panaderia.complete') }}"
        data-progress-url="{{ route('player.progress') }}"
    >
        <header class="bakery-topbar">
            <a href="{{ route('game.madrid') }}" class="bakery-back-link">
                <span aria-hidden="true">←</span>
                Terug naar Madrid
            </a>

            <div class="bakery-mission-meta">
                @auth
                    <a href="{{ route('player.progress') }}">Mijn voortgang</a>
                @else
                    <a href="{{ route('login', ['redirect' => route('game.madrid.panaderia', absolute: false)]) }}">Inloggen</a>
                @endauth
                <span class="bakery-mode-chip">Spreken + tekst</span>
                <span data-level-chip>Niveau kiezen</span>
                <button type="button" data-translation-toggle aria-pressed="false">Nederlandse vertaling</button>
                <button type="button" data-restart-dialogue>Opnieuw beginnen</button>
            </div>
        </header>

        <main id="dialogue-content" class="bakery-main">
            <section class="bakery-heading" aria-labelledby="bakery-title">
                <div>
                    <p class="bakery-eyebrow">Madrid · Panadería La Espiga</p>
                    <h1 id="bakery-title" data-mission-title>Je eerste bestelling</h1>
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
                We controleren of de goedgekeurde dialoog beschikbaar is.
            </div>

            <section class="bakery-stage" data-dialogue-stage hidden>
                <aside class="bakery-scene" aria-label="Interieur van Panadería La Espiga">
                    <div class="bakery-awning" aria-hidden="true"></div>
                    <div class="bakery-sign" aria-hidden="true">
                        <span>Panadería</span>
                        <strong>La Espiga</strong>
                        <small>Pan artesano · Madrid</small>
                    </div>
                    <div class="bakery-shelves" aria-hidden="true">
                        <span class="bakery-shelf bakery-shelf-one"></span>
                        <span class="bakery-shelf bakery-shelf-two"></span>
                        <span class="bakery-loaf bakery-loaf-one"></span>
                        <span class="bakery-loaf bakery-loaf-two"></span>
                        <span class="bakery-loaf bakery-loaf-three"></span>
                        <span class="bakery-pastry bakery-pastry-one"></span>
                        <span class="bakery-pastry bakery-pastry-two"></span>
                    </div>
                    <div class="bakery-counter" aria-hidden="true"></div>

                    <div class="bakery-lucia" aria-hidden="true">
                        <span class="bakery-lucia-hair"></span>
                        <span class="bakery-lucia-head"></span>
                        <span class="bakery-lucia-neck"></span>
                        <span class="bakery-lucia-body"></span>
                        <span class="bakery-lucia-apron"></span>
                        <span class="bakery-lucia-badge">Lucía</span>
                    </div>

                    <div class="bakery-npc-card">
                        <span class="bakery-npc-avatar" aria-hidden="true">L</span>
                        <div>
                            <strong data-npc-name>Lucía Martín</strong>
                            <span><span lang="es" data-npc-role-es>panadera</span> · <span data-npc-role-nl>bakker</span></span>
                        </div>
                    </div>
                </aside>

                <div class="bakery-dialogue-column">
                    <section class="bakery-conversation" aria-labelledby="conversation-title">
                        <div class="bakery-turn-heading">
                            <div>
                                <p class="bakery-eyebrow">Lucía zegt</p>
                                <h2 id="conversation-title">Jij bent aan de beurt</h2>
                            </div>
                            <span data-turn-label>Beurt 1</span>
                        </div>

                        <div class="bakery-npc-bubble">
                            <p lang="es" data-npc-line-es>Hola, buenos días.</p>
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
                                    <div class="bakery-feedback-overall">
                                        <span>Gewogen resultaat</span>
                                        <strong data-feedback-overall></strong>
                                    </div>
                                    <ul data-feedback-rubric></ul>
                                </details>

                                <button type="button" class="bakery-feedback-retry" data-feedback-retry hidden>
                                    Probeer deze beurt opnieuw
                                </button>
                            </div>
                        </div>

                        <form data-dialogue-form class="bakery-response-form">
                            <label for="player-response" data-step-prompt>Wat wil je zeggen?</label>

                            <section
                                class="bakery-recorder"
                                data-speech-recorder
                                data-transcription-url="{{ route('game.madrid.panaderia.transcription') }}"
                                data-maximum-seconds="12"
                                aria-labelledby="recorder-title"
                            >
                                <div class="bakery-recorder-heading">
                                    <div>
                                        <span class="bakery-recorder-icon" aria-hidden="true">●</span>
                                        <div>
                                            <h3 id="recorder-title">Spreek je antwoord</h3>
                                            <p>WebM/Opus · maximaal 12 seconden</p>
                                        </div>
                                    </div>
                                    <strong data-recording-timer>0:00 / 0:12</strong>
                                </div>

                                <p class="bakery-recorder-status" data-recorder-status role="status" aria-live="polite">
                                    De microfoon start pas wanneer jij op opnemen drukt.
                                </p>

                                <div class="bakery-recorder-controls">
                                    <button type="button" class="bakery-record-button" data-record-start>
                                        <span aria-hidden="true">●</span>
                                        Opnemen
                                    </button>
                                    <button type="button" data-record-stop hidden>
                                        <span aria-hidden="true">■</span>
                                        Stop opname
                                    </button>
                                </div>

                                <div class="bakery-recording-preview" data-recording-preview hidden>
                                    <label for="speech-playback">Luister terug voordat je verzendt</label>
                                    <audio id="speech-playback" controls preload="metadata" data-recording-playback></audio>
                                    <div>
                                        <button type="button" data-record-retry>Opnieuw opnemen</button>
                                        <button type="button" class="bakery-transcribe-button" data-record-transcribe>
                                            Transcript maken
                                            <span aria-hidden="true">→</span>
                                        </button>
                                    </div>
                                </div>

                                <p class="bakery-transcript-note" data-transcript-note hidden></p>
                            </section>

                            <p class="bakery-privacy-note">
                                Je opname wordt alleen na jouw klik naar de transcriptiedienst (OpenAI) verzonden. Voor persoonlijke feedback gaat daarna alleen je gecontroleerde transcript met oefencontext naar OpenAI. Wij slaan beide niet server-side op en sturen ze nooit naar analytics.
                                <a href="{{ route('privacy') }}#spraakopnamen">Lees het privacybeleid.</a>
                            </p>

                            <div class="bakery-input-divider" aria-hidden="true"><span>of typ je antwoord</span></div>
                            <div class="bakery-input-row">
                                <input
                                    id="player-response"
                                    name="response"
                                    type="text"
                                    autocomplete="off"
                                    spellcheck="false"
                                    lang="es"
                                    data-player-response
                                    required
                                >
                                <button type="submit">Gebruik antwoord</button>
                            </div>

                            <div class="bakery-assist-row">
                                <button type="button" data-hint-toggle aria-expanded="false">Toon een hint</button>
                                <p data-step-hint hidden></p>
                            </div>

                            <fieldset class="bakery-choice-assist">
                                <legend>Of kies een voorbeeldzin</legend>
                                <div data-choice-list></div>
                            </fieldset>
                        </form>

                        <button type="button" class="bakery-continue-button" data-dialogue-continue hidden>
                            Verder met de bestelling
                            <span aria-hidden="true">→</span>
                        </button>
                    </section>

                    <aside class="bakery-history" aria-labelledby="history-title">
                        <div>
                            <p class="bakery-eyebrow">Gespreksverloop</p>
                            <h2 id="history-title">Wat je al hebt gezegd</h2>
                        </div>
                        <ol data-dialogue-history>
                            <li data-history-empty>Nog geen antwoorden. Lucía wacht rustig op je.</li>
                        </ol>
                    </aside>
                </div>
            </section>

            <section class="bakery-level-select" data-level-select aria-labelledby="level-title" hidden>
                <p class="bakery-eyebrow">Voor je begint</p>
                <h2 id="level-title">Hoeveel steun wil je?</h2>
                <p>Dit bepaalt alleen welke onverwachte vervolgvraag Lucía stelt. Je kunt altijd voorbeeldzinnen gebruiken.</p>
                <div>
                    <button type="button" data-level="A0">
                        <strong>A0 · Veel steun</strong>
                        <span>Lucía vraagt hoeveel stuks je wilt.</span>
                    </button>
                    <button type="button" data-level="A1">
                        <strong>A1 · Een beetje steun</strong>
                        <span>Een product blijkt niet beschikbaar.</span>
                    </button>
                    <button type="button" data-level="A2">
                        <strong>A2 · Zelf proberen</strong>
                        <span>Lucía vraagt naar je broodvoorkeur.</span>
                    </button>
                </div>
            </section>

            <section class="bakery-complete" data-dialogue-complete hidden tabindex="-1" aria-labelledby="complete-title">
                <div class="bakery-stamp" aria-hidden="true">✓</div>
                <p class="bakery-eyebrow">Missie voltooid</p>
                <h2 id="complete-title">¡Lo has conseguido!</h2>
                <p lang="es" data-farewell-es></p>
                <p data-farewell-nl hidden></p>

                <dl class="bakery-rewards">
                    <div><dt>XP</dt><dd data-reward-xp>80</dd></div>
                    <div><dt>Confianza</dt><dd data-reward-confidence>+1</dd></div>
                    <div><dt>Valentía</dt><dd data-reward-courage>+1</dd></div>
                </dl>

                <div class="bakery-spoken-goal" data-spoken-goal>
                    <span aria-hidden="true">●</span>
                    <div>
                        <strong>Spreekdoel: <span data-spoken-turns>0</span>/3 beurten</strong>
                        <p data-spoken-goal-message>Je kunt de missie met tekst afronden en de spreekbeurten later opnieuw doen.</p>
                    </div>
                </div>

                <div class="bakery-reward-cards">
                    <div><span aria-hidden="true">▣</span><p>Paspoortstempel</p><strong data-reward-stamp></strong></div>
                    <div><span aria-hidden="true">◇</span><p>Verzamelitem</p><strong data-reward-item></strong></div>
                    <div data-repair-badge hidden><span aria-hidden="true">★</span><p>Bonusbadge</p><strong data-reward-badge></strong></div>
                </div>

                <div class="bakery-account-sync" data-account-sync>
                    <div role="status" aria-live="polite">
                        <strong data-account-sync-title>Je missieresultaat is lokaal klaar.</strong>
                        <p data-account-sync-message></p>
                        <p data-account-balances hidden></p>
                    </div>
                    <button type="button" data-account-sync-retry hidden>Opnieuw opslaan</button>
                    @guest
                        <a data-account-login href="{{ route('login', ['redirect' => route('game.madrid.panaderia', absolute: false)]) }}">Log in en bewaar dit resultaat</a>
                    @endguest
                </div>

                <div class="bakery-complete-actions">
                    <a href="{{ route('game.madrid') }}">Terug naar Madrid</a>
                    @auth
                        <a href="{{ route('player.progress') }}">Bekijk mijn voortgang</a>
                    @endauth
                    <button type="button" data-replay-dialogue>Speel opnieuw</button>
                </div>
            </section>

            <section class="bakery-error" data-dialogue-error hidden aria-labelledby="dialogue-error-title">
                <span aria-hidden="true">🥖</span>
                <h2 id="dialogue-error-title">Lucía is nog aan het voorbereiden</h2>
                <p>Publiceer de conversatie <strong>la-espiga-lucia</strong> via een productierelease om de bakkerij te openen.</p>
                <button type="button" data-dialogue-retry>Probeer opnieuw</button>
                <a href="{{ route('game.madrid') }}">Terug naar Madrid</a>
            </section>

            <noscript>
                <section class="bakery-error">
                    <h2>JavaScript is nodig voor deze dialoog</h2>
                    <p>De volledig server-side gesprekservaring volgt in een latere toegankelijkheidsiteratie.</p>
                </section>
            </noscript>
        </main>
    </div>
</body>
</html>
