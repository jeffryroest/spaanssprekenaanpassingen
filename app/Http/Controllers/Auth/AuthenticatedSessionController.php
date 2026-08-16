<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ContentPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View
    {
        $redirect = $request->query('redirect');
        $allowedRedirects = [
            route('game.madrid.panaderia', absolute: false),
            route('player.progress', absolute: false),
        ];

        if (is_string($redirect) && in_array($redirect, $allowedRedirects, true)) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $fallback = $request->user()->hasContentPermission(ContentPermission::View)
            ? route('content-studio.dashboard', absolute: false)
            : route('player.progress', absolute: false);

        return redirect()->intended($fallback);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
