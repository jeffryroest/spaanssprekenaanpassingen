<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Rond je proefweek Spaans af met Lucía in La Espiga en blik terug op je ontmoetingen in Madrid.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>El reto final · Spaansspreken.nl</title>
    <link rel="preload" href="{{ asset('images/game/la-espiga-interior.webp') }}" as="image" type="image/webp">
    <link rel="preload" href="{{ asset('images/game/lucia-expressions.webp') }}" as="image" type="image/webp">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bakery-body final-body">
    <a href="#dialogue-content" class="hub-skip-link">Ga naar het slotgesprek met Lucía</a>

    <div
        class="bakery-app"
        data-scenario-dialogue
        data-final-dialogue
        data-scene="final_text_dialogue"
        data-scenario-slug="madrid-final-lucia"
        data-storage-key="madrid-final-dialogue-v1"
        data-npc-name="Lucía"
        data-source="{{ route('game.madrid.final.content', ['locale' => 'nl-NL']) }}"
        data-hub-route="{{ route('game.madrid') }}"
        data-transcription-url="{{ route('game.madrid.final.transcription') }}"
        data-assessment-url="{{ route('game.madrid.final.feedback') }}"
        data-authenticated="true"
        data-completion-url="{{ route('game.madrid.final.complete') }}"
        data-progress-url="{{ route('player.progress') }}"
        data-memory-returning="{{ $npcMemory['returning_to_lucia'] ? 'true' : 'false' }}"
    >
        <header class="bakery-topbar">
            <a href="{{ route('trial-week.show') }}" class="bakery-back-link"><span aria-hidden="true">←</span>Terug naar de proefweek</a>
            <div class="bakery-mission-meta">
                <a href="{{ route('player.progress') }}">Mijn voortgang</a>
                <span class="bakery-mode-chip">Finale · NPC-herkenning</span>
                <span data-level-chip>Niveau kiezen</span>
                <button type="button" data-translation-toggle aria-pressed="false">Nederlandse vertaling</button>
                <button type="button" data-restart-dialogue>Opnieuw beginnen</button>
            </div>
        </header>

        <main id="dialogue-content" class="bakery-main">
            <section class="bakery-heading" aria-labelledby="final-title">
                <div>
                    <p class="bakery-eyebrow">Dag 7 · Madrid · Terug bij La Espiga</p>
                    <h1 id="final-title" data-mission-title>De slotmissie</h1>
                    <p data-mission-objective>De actuele, gepubliceerde dialoog wordt geladen.</p>
                </div>
                <div class="bakery-progress" aria-label="Missievoortgang">
                    <div><span data-progress-label>Voorbereiden</span><strong><span data-progress-current>0</span>/<span data-progress-total>5</span></strong></div>
                    <span class="bakery-progress-track" aria-hidden="true"><span data-progress-bar></span></span>
                </div>
            </section>

            <section class="final-memory" data-npc-memory aria-labelledby="memory-title">
                <div class="final-memory-heading">
                    <span aria-hidden="true">✦</span>
                    <div>
                        <p class="bakery-eyebrow">Jouw route door Madrid</p>
                        <h2 id="memory-title">
                            {{ $npcMemory['returning_to_lucia'] ? 'Lucía herkent je' : 'Je ontmoet Lucía hier voor het eerst' }}
                        </h2>
                    </div>
                    <strong>{{ $npcMemory['met_count'] }}/5 ontmoet</strong>
                </div>
                <p lang="es" data-memory-greeting-es>
                    {{ $npcMemory['returning_to_lucia']
                        ? '¡Qué alegría verte otra vez! Me acuerdo de tu primer pedido.'
                        : '¡Bienvenido a La Espiga! Hoy cerramos tu semana en Madrid.' }}
                </p>
                <p data-memory-greeting-nl hidden></p>
                <ul aria-label="Ontmoetingen uit voltooide missies">
                    @foreach ($npcMemory['encounters'] as $encounter)
                        <li class="{{ $encounter['met'] ? 'is-met' : '' }}">
                            <span aria-hidden="true">{{ $encounter['met'] ? '✓' : '○' }}</span>
                            <strong>{{ $encounter['name'] }}</strong>
                            <small>{{ $encounter['setting'] }}</small>
                        </li>
                    @endforeach
                </ul>
                <details>
                    <summary>Wat onthoudt de game?</summary>
                    <p data-memory-privacy>Alleen welke missies je hebt voltooid. Vrije antwoorden, transcripties, audio en feedback worden niet gebruikt als NPC-geheugen.</p>
                </details>
            </section>

            <div class="bakery-status" data-dialogue-status role="status" aria-live="polite">We controleren of het goedgekeurde slotgesprek beschikbaar is.</div>

            <section class="bakery-stage" data-dialogue-stage hidden>
                <aside class="bakery-scene final-scene" aria-label="Een warme Madrileense bakkerij met Lucía achter de toonbank">
                    <div class="bakery-scene-art" aria-hidden="true"></div>
                    <div class="bakery-scene-light" aria-hidden="true"></div>
                    <div class="bakery-lucia-frame" data-npc-state="listening" aria-hidden="true">
                        <img src="{{ asset('images/game/lucia-expressions.webp') }}" width="1724" height="862" alt="" data-npc-expression-sheet>
                        <span class="bakery-lucia-reaction" data-npc-reaction>Lucía luistert</span>
                    </div>
                    <div class="bakery-npc-card">
                        <span class="bakery-npc-avatar" aria-hidden="true">L</span>
                        <div>
                            <strong data-npc-name>Lucía Martín</strong>
                            <span><span lang="es" data-npc-role-es>panadera y anfitriona</span> · <span data-npc-role-nl>bakker en gastvrouw</span></span>
                        </div>
                    </div>
                </aside>

                <div class="bakery-dialogue-column">
                    <section class="bakery-conversation" aria-labelledby="conversation-title">
                        <div class="bakery-turn-heading">
                            <div><p class="bakery-eyebrow">Lucía zegt</p><h2 id="conversation-title">Jij bent aan de beurt</h2></div>
                            <span data-turn-label>Beurt 1</span>
                        </div>
                        <div class="bakery-npc-bubble"><p lang="es" data-npc-line-es>Hola.</p><p data-npc-line-nl hidden></p></div>

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
                            <label for="final-player-response" data-step-prompt>Wat wil je zeggen?</label>
                            <section class="bakery-recorder" data-speech-recorder data-transcription-url="{{ route('game.madrid.final.transcription') }}" data-maximum-seconds="12" aria-labelledby="final-recorder-title">
                                <div class="bakery-recorder-heading">
                                    <div><span class="bakery-recorder-icon" aria-hidden="true">●</span><div><h3 id="final-recorder-title">Spreek je antwoord</h3><p>WebM/Opus · maximaal 12 seconden</p></div></div>
                                    <strong data-recording-timer>0:00 / 0:12</strong>
                                </div>
                                <p class="bakery-recorder-status" data-recorder-status role="status" aria-live="polite">De microfoon start pas wanneer jij op opnemen drukt.</p>
                                <div class="bakery-recorder-controls">
                                    <button type="button" class="bakery-record-button" data-record-start><span aria-hidden="true">●</span>Opnemen</button>
                                    <button type="button" data-record-stop hidden><span aria-hidden="true">■</span>Stop opname</button>
                                </div>
                                <div class="bakery-recording-preview" data-recording-preview hidden>
                                    <label for="final-speech-playback">Luister terug voordat je verzendt</label>
                                    <audio id="final-speech-playback" controls preload="metadata" data-recording-playback></audio>
                                    <div><button type="button" data-record-retry>Opnieuw opnemen</button><button type="button" class="bakery-transcribe-button" data-record-transcribe>Transcript maken <span aria-hidden="true">→</span></button></div>
                                </div>
                                <p class="bakery-transcript-note" data-transcript-note hidden></p>
                            </section>
                            <details class="bakery-privacy-note"><summary>Wat gebeurt er met mijn antwoord?</summary><p>Audio, antwoord, transcript en feedback worden niet als accountvoortgang of NPC-geheugen opgeslagen. Alleen de structurele missiestatus wordt bewaard. <a href="{{ route('privacy') }}#spraakopnamen">Lees het privacybeleid.</a></p></details>
                            <div class="bakery-input-divider" aria-hidden="true"><span>of typ je antwoord</span></div>
                            <div class="bakery-input-row"><input id="final-player-response" name="response" type="text" autocomplete="off" spellcheck="false" lang="es" data-player-response required><button type="submit">Gebruik antwoord</button></div>
                            <div class="bakery-assist-row"><button type="button" data-hint-toggle aria-expanded="false">Toon een hint</button><p data-step-hint hidden></p></div>
                            <details class="bakery-choice-assist"><summary>Ik wil een voorbeeldzin</summary><div data-choice-list></div></details>
                        </form>
                        <button type="button" class="bakery-continue-button" data-dialogue-continue hidden>Verder met de finale <span aria-hidden="true">→</span></button>
                    </section>

                    <aside class="bakery-history" aria-labelledby="final-history-title">
                        <div><p class="bakery-eyebrow">Gespreksverloop</p><h2 id="final-history-title">Jouw laatste gesprek in Madrid</h2></div>
                        <ol data-dialogue-history><li data-history-empty>Nog geen antwoorden. Lucía wacht rustig op je.</li></ol>
                    </aside>
                </div>
            </section>

            <section class="bakery-level-select" data-level-select aria-labelledby="final-level-title" hidden>
                <p class="bakery-eyebrow">Voor het slotgesprek</p>
                <h2 id="final-level-title">Hoeveel steun wil je?</h2>
                <p>Je blikt op ieder niveau terug en kiest een vervolgdoel. Alleen de onverwachte wijziging in La Espiga verschilt.</p>
                <div>
                    <button type="button" data-level="A0"><strong>A0 · Veel steun</strong><span>Kies een drankje om de week te vieren.</span></button>
                    <button type="button" data-level="A1"><strong>A1 · Een beetje steun</strong><span>Accepteer een alternatief voor je eerste keuze.</span></button>
                    <button type="button" data-level="A2"><strong>A2 · Zelf proberen</strong><span>Kies zoet of hartig en motiveer je voorkeur.</span></button>
                </div>
            </section>

            <section class="bakery-complete" data-dialogue-complete hidden tabindex="-1" aria-labelledby="final-complete-title">
                <div class="bakery-complete-hero final-complete-hero">
                    <div class="bakery-complete-lucia" aria-hidden="true"><img src="{{ asset('images/game/lucia-expressions.webp') }}" width="1724" height="862" alt="" data-npc-expression-sheet-complete></div>
                    <div><div class="bakery-stamp"><span>MADRID</span><strong>7 DÍAS</strong><small>✓</small></div><p class="bakery-eyebrow">Proefweek voltooid</p><h2 id="final-complete-title">¡Lo has conseguido!</h2><p lang="es" data-farewell-es></p><p data-farewell-nl hidden></p></div>
                </div>
                <dl class="bakery-rewards"><div><dt>XP</dt><dd data-reward-xp>160</dd></div><div><dt>Confianza</dt><dd data-reward-confidence>+1</dd></div><div><dt>Valentía</dt><dd data-reward-courage>+1</dd></div></dl>
                <div class="bakery-spoken-goal" data-spoken-goal><span aria-hidden="true">●</span><div><strong>Spreekdoel: <span data-spoken-turns>0</span>/3 beurten</strong><p data-spoken-goal-message>Je kunt de missie met tekst afronden en later opnieuw spreken.</p></div></div>
                <div class="bakery-reward-cards"><div><span aria-hidden="true">▣</span><p>Paspoortstempel</p><strong data-reward-stamp></strong></div><div><span aria-hidden="true">◇</span><p>Verzamelitem</p><strong data-reward-item></strong></div><div data-repair-badge hidden><span aria-hidden="true">★</span><p>Bonusbadge</p><strong data-reward-badge></strong></div></div>
                <div class="bakery-account-sync" data-account-sync><div role="status" aria-live="polite"><strong data-account-sync-title>Je missieresultaat is lokaal klaar.</strong><p data-account-sync-message></p><p data-account-balances hidden></p></div><button type="button" data-account-sync-retry hidden>Opnieuw opslaan</button></div>
                <div class="bakery-complete-actions"><a href="{{ route('trial-week.show') }}">Bekijk de voltooide proefweek</a><a href="{{ route('player.progress') }}">Bekijk mijn voortgang</a><button type="button" data-replay-dialogue>Speel opnieuw</button></div>
            </section>

            <section class="bakery-error" data-dialogue-error hidden aria-labelledby="final-error-title">
                <span aria-hidden="true">✦</span><h2 id="final-error-title">Lucía kan de finale nog niet starten</h2><p>Publiceer het gespreksscenario <strong>madrid-final-lucia</strong> met de gereviewde scène en media via een productierelease om dag 7 te openen.</p><button type="button" data-dialogue-retry>Probeer opnieuw</button><a href="{{ route('trial-week.show') }}">Terug naar de proefweek</a>
            </section>
            <noscript><section class="bakery-error"><h2>JavaScript is nodig voor deze dialoog</h2><p>Gebruik een browser met JavaScript om de actieve gespreksroute te spelen.</p></section></noscript>
        </main>
    </div>
</body>
</html>
