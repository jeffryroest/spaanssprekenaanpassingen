<?php

namespace App\Http\Controllers;

use App\Enums\AccountDeletionStatus;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AccountDeletionRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validateWithBag('deletion', [
            'current_password' => ['required', 'current_password:web'],
            'confirm_deletion' => ['accepted'],
        ]);

        if ($user->content_role !== null) {
            return back()->withErrors([
                'confirm_deletion' => 'Accounts van het contentteam worden via het interne rollen- en toegangsproces afgehandeld.',
            ], 'deletion');
        }

        $existingRequest = $user->deletionRequests()
            ->whereIn('status', [AccountDeletionStatus::Requested, AccountDeletionStatus::Blocked])
            ->exists();

        if ($existingRequest) {
            return back()->with('deletion_status', 'Je verwijderverzoek is al in behandeling.');
        }

        $user->deletionRequests()->create([
            'public_id' => Str::ulid(),
            'requested_by_id' => $user->getKey(),
            'status' => AccountDeletionStatus::Requested,
            'requested_at' => now(),
        ]);

        return back()->with('deletion_status', 'Je verwijderverzoek is geregistreerd. Support controleert eerst je abonnements- en bewaarsituatie.');
    }

    public function cancel(Request $request, AccountDeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($deletionRequest->user_id === $request->user()->getKey(), 404);

        if (in_array($deletionRequest->status, [AccountDeletionStatus::Requested, AccountDeletionStatus::Blocked], true)) {
            $deletionRequest->forceFill([
                'status' => AccountDeletionStatus::Cancelled,
                'processed_at' => now(),
            ])->save();
        }

        return back()->with('deletion_status', 'Je verwijderverzoek is ingetrokken.');
    }
}
