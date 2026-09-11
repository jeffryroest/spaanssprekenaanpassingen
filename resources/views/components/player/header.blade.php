@props([
    'backUrl' => null,
    'backLabel' => null,
])

@php
    $hasContext = $backUrl !== null || trim((string) $slot) !== '';
@endphp

<header {{ $attributes->class(['player-site-header']) }}>
    <div class="player-site-header-main">
        <x-player.brand />

        <nav class="player-nav player-nav-desktop" aria-label="Spelersnavigatie">
            <x-player.navigation />
        </nav>

        <details class="player-nav-menu">
            <summary><span aria-hidden="true">☰</span> Menu</summary>
            <nav class="player-nav player-nav-mobile" aria-label="Spelersnavigatie mobiel">
                <x-player.navigation />
            </nav>
        </details>
    </div>

    @if ($hasContext)
        <div class="player-context-bar">
            @if ($backUrl)
                <a href="{{ $backUrl }}" class="player-context-back"><span aria-hidden="true">←</span>{{ $backLabel ?? 'Terug' }}</a>
            @endif
            @if (trim((string) $slot) !== '')
                <div class="player-context-actions">{{ $slot }}</div>
            @endif
        </div>
    @endif
</header>
