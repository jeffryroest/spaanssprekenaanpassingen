@php
    $links = auth()->check()
        ? [
            ['Madrid', route('game.madrid'), request()->routeIs('game.madrid*')],
            ['Mijn proefweek', route('trial-week.show'), request()->routeIs('trial-week.*', 'billing.*')],
            ['Voortgang', route('player.progress'), request()->routeIs('player.progress')],
            ['Account', route('player.account'), request()->routeIs('player.account*')],
        ]
        : [
            ['Madrid', route('game.madrid'), request()->routeIs('game.madrid*')],
            ['Inloggen', route('login'), request()->routeIs('login*', 'password.*')],
            ['Aanmelden', route('register'), request()->routeIs('register*')],
        ];
@endphp

@foreach ($links as [$label, $href, $active])
    <a href="{{ $href }}" class="player-nav-link {{ $active ? 'player-nav-link-active' : '' }}" @if ($active) aria-current="page" @endif>{{ $label }}</a>
@endforeach

@can('content-studio.view')
    <a href="{{ route('content-studio.dashboard') }}" class="player-nav-link player-nav-link-studio">Content Studio</a>
@endcan

@auth
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="player-nav-link player-nav-logout">Uitloggen</button>
    </form>
@endauth
