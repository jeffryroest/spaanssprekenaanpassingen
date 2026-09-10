<?php

namespace App\Http\Controllers;

use App\Access\EntitlementService;
use App\Models\SubscriptionOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class PlayerAccountController extends Controller
{
    public function show(Request $request, EntitlementService $entitlements): Response
    {
        $user = $request->user();
        $user->load('latestSubscription.plan');

        return response()->view('player.account', [
            'access' => $entitlements->snapshotFor($user)->toArray(),
            'latestOrder' => SubscriptionOrder::query()
                ->where('user_id', $user->getKey())
                ->latest('id')
                ->first(),
        ])->withHeaders([
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $normalizedEmail = Str::lower(trim((string) $request->input('email')));
        $request->merge([
            'name' => Str::squish((string) $request->input('name')),
            'email' => $normalizedEmail,
        ]);
        $emailChanges = $normalizedEmail !== Str::lower($user->email);
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ];

        if ($emailChanges) {
            $rules['current_password'] = ['required', 'current_password:web'];
        }

        $validated = $request->validateWithBag('profile', $rules);
        $user->name = $validated['name'];
        $user->email = $normalizedEmail;

        if ($emailChanges) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('profile_status', 'Je accountgegevens zijn bijgewerkt.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $validated['password']]);
        $request->session()->regenerate();

        return back()->with('password_status', 'Je wachtwoord is gewijzigd.');
    }
}
