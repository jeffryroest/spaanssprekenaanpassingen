<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Stap Madrid in en leer Spaans spreken door echte missies te spelen.">
    <title>Spaansspreken.nl · Stap Madrid in</title>
    <link rel="preload" href="{{ asset('images/game/madrid-morning.webp') }}" as="image" type="image/webp">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="world-home-body">
    <a href="#main-content" class="hub-skip-link">Direct naar de eerste missie</a>

    <header class="world-home-nav">
        <a href="{{ route('home') }}" class="world-brand" aria-label="Spaansspreken.nl startpagina">
            <span aria-hidden="true">S</span>
            <strong>Spaansspreken<em>.nl</em></strong>
        </a>
        <nav aria-label="Account en voortgang">
            @auth
                <a href="{{ route('player.progress') }}">Mijn voortgang</a>
                <a href="{{ route('trial-week.show') }}">Mijn proefweek</a>
                @can('content-studio.view')
                    <a href="{{ route('content-studio.dashboard') }}">Content Studio</a>
                @endcan
            @else
                <a href="{{ route('login') }}">Inloggen</a>
            @endauth
        </nav>
    </header>

    <main id="main-content">
        <section class="world-hero" aria-labelledby="world-hero-title">
            <img
                src="{{ asset('images/game/madrid-morning.webp') }}"
                alt="Een warme Madrileense ochtendstraat met een metro-ingang, een plein en een buurtbakkerij."
                width="1672"
                height="941"
                fetchpriority="high"
            >
            <div class="world-hero-shade" aria-hidden="true"></div>
            <div class="world-hero-copy">
                <p class="world-kicker"><span aria-hidden="true"></span> Je eerste ochtend in Madrid</p>
                <h1 id="world-hero-title">Je leert Spaans zodra je de straat op stapt.</h1>
                <p>Vind La Espiga, kies je ontbijt en bestel het zelf bij Lucía. Je mag spreken, typen en altijd om hulp vragen.</p>
                <div class="world-hero-actions">
                    <a href="{{ route('game.madrid') }}" class="world-primary-cta">
                        Start je eerste missie
                        <span aria-hidden="true">→</span>
                    </a>
                    <span>Gratis te spelen · ongeveer 12 minuten</span>
                </div>
            </div>
            <aside class="world-mission-card" aria-labelledby="mission-card-title">
                <span class="world-mission-number" aria-hidden="true">01</span>
                <p>La panadería</p>
                <h2 id="mission-card-title">Regel je ontbijt bij Lucía</h2>
                <ul>
                    <li><span aria-hidden="true">◎</span> Verken een levendige buurt</li>
                    <li><span aria-hidden="true">●</span> Spreek zonder tijdsdruk</li>
                    <li><span aria-hidden="true">✦</span> Verdien je eerste paspoortstempel</li>
                </ul>
            </aside>
        </section>

        <section class="world-how" aria-labelledby="world-how-title">
            <div>
                <p class="world-kicker">Zo speel je</p>
                <h2 id="world-how-title">Geen losse woordenlijst. Eén echte situatie.</h2>
            </div>
            <ol>
                <li><span>1</span><div><strong>Kijk rond</strong><p>Ontdek woorden waar je ze echt nodig hebt.</p></div></li>
                <li><span>2</span><div><strong>Zeg het zelf</strong><p>Formuleer vrij; voorbeeldzinnen zijn alleen hulp.</p></div></li>
                <li><span>3</span><div><strong>Zie Madrid reageren</strong><p>Je keuzes openen nieuwe plekken en beloningen.</p></div></li>
            </ol>
        </section>

        <section class="world-promise" aria-labelledby="world-promise-title">
            <div>
                <p class="world-kicker">Spreken met vertrouwen</p>
                <h2 id="world-promise-title">Begrijpelijk Spaans telt. Foutloos hoeft niet.</h2>
            </div>
            <p>Lucía blijft in haar rol als bakker. De coachlaag vertelt eerst wat al duidelijk was en geeft daarna maar één concrete volgende stap. Geen levens, geen tijdsdruk.</p>
            <a href="{{ route('game.madrid') }}">Madrid wacht op je <span aria-hidden="true">→</span></a>
        </section>
    </main>

    <footer class="world-home-footer">
        <p>Spaansspreken.nl · leren door te doen</p>
        <a href="{{ route('privacy') }}">Privacy en spraakopnamen</a>
    </footer>
</body>
</html>
