<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Verken Madrid en start je eerste Spaanse spreekmissie bij Panadería La Espiga.">
    <title>Madrid · Spaansspreken.nl</title>
    <link rel="preload" href="{{ asset('images/game/madrid-morning.webp') }}" as="image" type="image/webp">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="hub-body">
    <a href="#hub-content" class="hub-skip-link">Ga naar de Madrid-kaart</a>

    <div
        class="hub-app"
        data-madrid-hub
        data-source="{{ url('/api/v1/worlds/madrid?locale=nl-NL') }}"
        data-home="{{ route('home') }}"
        data-panaderia-route="{{ route('game.madrid.panaderia') }}"
        data-restaurant-route="{{ route('game.madrid.restaurant') }}"
        data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
        data-progress-url="{{ route('game.progress') }}"
        data-trial-week-url="{{ route('game.trial-week.status') }}"
    >
        <header class="hub-topbar">
            <a href="{{ route('home') }}" class="hub-brand" aria-label="Spaansspreken.nl startpagina">
                <span class="hub-brand-mark" aria-hidden="true">S</span>
                <span>Spaansspreken<span>.nl</span></span>
            </a>

            <div class="hub-topbar-actions" aria-label="Spelinstellingen">
                @auth
                    <a href="{{ route('player.progress') }}" class="hub-icon-button">Mijn voortgang</a>
                @else
                    <a href="{{ route('login') }}" class="hub-icon-button">Inloggen</a>
                @endauth
                <span class="hub-live-chip"><span aria-hidden="true"></span> Madrid</span>
                <button type="button" class="hub-icon-button" data-hub-sound aria-pressed="false">
                    <span aria-hidden="true">♪</span>
                    <span data-sound-label>Geluid uit</span>
                </button>
                <button type="button" class="hub-icon-button" data-hub-view aria-pressed="false">
                    <span aria-hidden="true">☷</span>
                    <span data-view-label>Lijstweergave</span>
                </button>
            </div>
        </header>

        <main id="hub-content" class="hub-main">
            <section class="hub-intro" aria-labelledby="hub-title">
                <div>
                    <p class="hub-eyebrow" data-hub-eyebrow>Madrid wordt geladen…</p>
                    <h1 id="hub-title" data-hub-title>Je Spaanse wereld opent</h1>
                    <p class="hub-lead" data-hub-description>We halen de actuele, gepubliceerde Madrid-kaart op.</p>
                </div>

                <dl class="hub-scoreboard" aria-label="Jouw voortgang in Madrid">
                    <div>
                        <dt>XP</dt>
                        <dd data-account-xp>0</dd>
                    </div>
                    <div>
                        <dt>Confianza</dt>
                        <dd data-account-confianza>0</dd>
                    </div>
                    <div>
                        <dt>Valentía</dt>
                        <dd data-account-valentia>0</dd>
                    </div>
                    <div class="hub-score-curiosity">
                        <dt>Curiosidad</dt>
                        <dd><span data-curiosity-score>0</span>/3</dd>
                    </div>
                </dl>
            </section>

            <section class="hub-objective" aria-labelledby="hub-objective-title">
                <span class="hub-objective-icon" aria-hidden="true">◎</span>
                <div>
                    <p id="hub-objective-title">Je eerste missie</p>
                    <p data-hub-objective>Even geduld. De opdracht wordt geladen.</p>
                </div>
                <span class="hub-objective-status" data-hub-progress>0 van 3 ontdekt</span>
            </section>

            <div class="hub-feedback" data-hub-status role="status" aria-live="polite">
                De productiecontent voor Madrid wordt gecontroleerd.
            </div>

            <section class="hub-stage" aria-labelledby="map-title">
                <div class="hub-map" data-hub-map>
                    <div class="hub-sky" aria-hidden="true">
                        <span class="hub-sun"></span>
                        <span class="hub-cloud hub-cloud-one"></span>
                        <span class="hub-cloud hub-cloud-two"></span>
                    </div>
                    <div class="hub-city" aria-hidden="true">
                        <span class="hub-building hub-building-one"></span>
                        <span class="hub-building hub-building-two"></span>
                        <span class="hub-building hub-building-three"></span>
                        <span class="hub-building hub-building-four"></span>
                        <span class="hub-plaza"></span>
                        <span class="hub-road hub-road-one"></span>
                        <span class="hub-road hub-road-two"></span>
                        <span class="hub-fountain"></span>
                        <span class="hub-tree hub-tree-one"></span>
                        <span class="hub-tree hub-tree-two"></span>
                        <span class="hub-tree hub-tree-three"></span>
                    </div>

                    <div class="hub-map-heading">
                        <p class="hub-eyebrow">Barrio del Centro</p>
                        <h2 id="map-title">Kies een plek op de kaart</h2>
                    </div>

                    <ul class="hub-hotspots" data-hub-hotspots aria-label="Locaties in Madrid"></ul>
                    <ul class="hub-inspectables" data-hub-inspectables aria-label="Dingen om te onderzoeken"></ul>

                    <div class="hub-map-loading" data-hub-loading>
                        <span aria-hidden="true"></span>
                        <p>Madrid opbouwen…</p>
                    </div>
                </div>

                <aside class="hub-detail-panel" data-hub-panel hidden tabindex="-1" aria-labelledby="hub-panel-title">
                    <button type="button" class="hub-panel-close" data-hub-panel-close aria-label="Informatiepaneel sluiten">×</button>
                    <p class="hub-eyebrow" data-hub-panel-kind>Ontdekken</p>
                    <h2 id="hub-panel-title" data-hub-panel-title>Madrid</h2>
                    <p class="hub-panel-body" data-hub-panel-body></p>
                    <div class="hub-language-card" data-hub-language hidden>
                        <strong lang="es" data-hub-language-es></strong>
                        <span data-hub-language-nl></span>
                    </div>
                    <ul class="hub-word-list" data-hub-word-list></ul>
                    <div class="hub-panel-reward" data-hub-panel-reward hidden>
                        <span aria-hidden="true">✦</span>
                        <span>+1 Curiosidad</span>
                    </div>
                    <button type="button" class="hub-mission-button" data-hub-mission-button hidden>
                        <span data-hub-mission-label>Open deze missie</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </aside>
            </section>

            <section class="hub-list-view" data-hub-list-view hidden aria-labelledby="hub-list-title">
                <div>
                    <p class="hub-eyebrow">Toegankelijke kaartweergave</p>
                    <h2 id="hub-list-title" tabindex="-1">Alle plekken in Madrid</h2>
                    <p>Gebruik deze lijst als alternatief voor de visuele buurtkaart.</p>
                </div>
                <ul data-hub-location-list></ul>
            </section>

            <section class="hub-error" data-hub-error hidden aria-labelledby="hub-error-title">
                <span aria-hidden="true">☀</span>
                <h2 id="hub-error-title">Madrid wordt nog klaargezet</h2>
                <p>De kaart kan pas openen nadat de wereld <strong>madrid</strong> via een productierelease is gepubliceerd.</p>
                <button type="button" data-hub-retry>Probeer opnieuw</button>
            </section>

            <dialog class="hub-arrival-dialog" data-hub-arrival aria-labelledby="hub-arrival-title">
                <div class="hub-arrival-visual" aria-hidden="true"></div>
                <div class="hub-dialog-content">
                    <p class="hub-eyebrow">Aankomst · Metro Sol</p>
                    <h2 id="hub-arrival-title">Je bent in Madrid</h2>
                    <p data-hub-arrival-description>De stad wordt wakker. Vanaf hier regel je zelf je eerste ontbijt.</p>
                    <button type="button" data-hub-arrival-continue>Bekijk de buurt <span aria-hidden="true">→</span></button>
                </div>
            </dialog>

            <dialog class="hub-preparation-dialog" data-hub-preparation aria-labelledby="hub-preparation-title">
                <div class="hub-dialog-content">
                    <button type="button" class="hub-dialog-close" data-hub-preparation-close aria-label="Voorbereiding sluiten">×</button>
                    <p class="hub-eyebrow">Voor je naar binnen gaat</p>
                    <h2 id="hub-preparation-title">Wat wil je meenemen?</h2>
                    <p data-hub-preparation-objective>Kies iets zoets voor bij je brood. Dit is je boodschappenkaart, geen verplicht script.</p>
                    <div class="hub-basket-card">
                        <span aria-hidden="true">🥖</span>
                        <div><small>Brood</small><strong lang="es" data-hub-bread-choice>el pan</strong></div>
                    </div>
                    <fieldset class="hub-sweet-choices">
                        <legend>Kies iets zoets</legend>
                        <div data-hub-sweet-choices></div>
                    </fieldset>
                    <p class="hub-preparation-note"><span aria-hidden="true">♡</span> Je formuleert je bestelling straks zelf. Fouten houden de rij nooit op.</p>
                    <button type="button" class="hub-mission-button" data-hub-enter-bakery>
                        Ga La Espiga binnen
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </dialog>

            <noscript>
                <section class="hub-error">
                    <h2>JavaScript is nodig voor de interactieve kaart</h2>
                    <p>Schakel JavaScript in of gebruik later de volledig server-side toegankelijke route.</p>
                </section>
            </noscript>
        </main>

        <footer class="hub-footer">
            <p>Een rustige buurt, één echte missie, stap voor stap meer vertrouwen.</p>
            <a href="{{ route('home') }}">Terug naar Spaansspreken.nl</a>
        </footer>
    </div>
</body>
</html>
