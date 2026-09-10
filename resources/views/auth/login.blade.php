@extends('layouts.player-auth')

@section('title', 'Inloggen')
@section('description', 'Log in om verder te spelen in je interactieve Spaanse wereld.')

@section('content')
    <p class="player-auth-kicker">Welkom terug</p>
    <h2 id="auth-title" class="player-auth-title">Ga verder in Madrid</h2>
    <p class="player-auth-description">Log in en open je bewaarde missieroute. Heb je nog geen account? Aanmelden is gratis en schrijft niets af.</p>

    @if (session('status'))
        <div class="player-auth-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="player-auth-error" role="alert">
            <p class="font-black">Inloggen is niet gelukt.</p>
            <p class="mt-1">Controleer je gegevens en probeer het opnieuw.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" novalidate>
        @csrf

        <div>
            <label for="email" class="player-auth-label">E-mailadres</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="player-auth-field {{ $errors->has('email') ? 'player-auth-field-error' : '' }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-4">
                <label for="password" class="player-auth-label">Wachtwoord</label>
                <a href="{{ route('password.request') }}" class="text-xs font-black text-[#a9472b] underline decoration-[#a9472b]/30 underline-offset-4">Vergeten?</a>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="player-auth-field {{ $errors->has('password') ? 'player-auth-field-error' : '' }}" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')<p id="password-error" class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>

        <label class="flex w-fit items-center gap-3 text-sm font-semibold text-[#64574f]">
            <input name="remember" type="checkbox" value="1" class="size-4 rounded border-[#493429]/20 text-[#a9472b] focus:ring-[#bd5a34]">
            Ingelogd blijven
        </label>

        <button type="submit" class="player-auth-primary">Inloggen en verder spelen</button>
    </form>

    <p class="mt-7 text-center text-sm text-[#6f6158]">
        Nieuw in Madrid?
        <a href="{{ route('register') }}" class="font-black text-[#a9472b] underline decoration-[#a9472b]/30 underline-offset-4">Maak gratis een account</a>
    </p>
@endsection
