@extends('layouts.player-auth')

@section('title', 'Wachtwoord vergeten')
@section('description', 'Vraag veilig een link aan om je Spaansspreken.nl-wachtwoord opnieuw in te stellen.')

@section('content')
    <p class="player-auth-kicker">Cuenta segura</p>
    <h2 id="auth-title" class="player-auth-title">Wachtwoord vergeten?</h2>
    <p class="player-auth-description">Vul je e-mailadres in. Als het bij ons bekend is, stuurt Postmark een persoonlijke herstellink.</p>

    @if (session('status'))
        <div class="player-auth-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <div>
            <label for="email" class="player-auth-label">E-mailadres</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required autofocus class="player-auth-field {{ $errors->has('email') ? 'player-auth-field-error' : '' }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="player-auth-primary">Stuur herstellink</button>
    </form>

    <a href="{{ route('login') }}" class="mt-7 inline-flex min-h-11 items-center text-sm font-black text-[#a9472b] underline decoration-[#a9472b]/30 underline-offset-4">Terug naar inloggen</a>
@endsection
