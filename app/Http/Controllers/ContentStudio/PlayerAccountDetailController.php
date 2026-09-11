<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\Accounts\ErasePlayerAccount;
use App\Actions\ContentStudio\AssignContentRole;
use App\Enums\AccountDeletionStatus;
use App\Enums\AccountSupportCategory;
use App\Enums\ContentRole;
use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\AccountSupportNote;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class PlayerAccountDetailController extends Controller
{
    public function show(User $user): Response
    {
        $user->load([
            'latestSubscription.plan',
            'subscriptionOrders' => fn ($query) => $query->latest('id')->limit(10),
            'supportNotes' => fn ($query) => $query->with(['actor', 'resolvedBy'])->latest('id'),
            'deletionRequests' => fn ($query) => $query->with(['requestedBy', 'processedBy'])->latest('id'),
            'contentRoleAudits' => fn ($query) => $query->with('actor')->latest('id')->limit(20),
        ])->loadCount(['missionAttempts', 'missionProgress', 'rewards']);

        return response()->view('content-studio.accounts.show', [
            'account' => $user,
            'roles' => ContentRole::cases(),
            'supportCategories' => AccountSupportCategory::cases(),
        ])->withHeaders([
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function updateRole(Request $request, User $user, AssignContentRole $assignRole): RedirectResponse
    {
        $validated = $request->validateWithBag('role', [
            'role' => ['nullable', Rule::enum(ContentRole::class)],
        ]);
        $role = filled($validated['role'] ?? null) ? ContentRole::from($validated['role']) : null;

        try {
            $assignRole->handle($user, $role, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['role' => $exception->getMessage()], 'role');
        }

        return back()->with('role_status', 'De Content Studio-rol is bijgewerkt en vastgelegd in de auditgeschiedenis.');
    }

    public function storeNote(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validateWithBag('support', [
            'category' => ['required', Rule::enum(AccountSupportCategory::class)],
            'summary' => ['required', 'string', 'max:500'],
            'follow_up_at' => ['nullable', 'date'],
        ]);

        $user->supportNotes()->create([
            'actor_id' => $request->user()->getKey(),
            'category' => AccountSupportCategory::from($validated['category']),
            'summary' => Str::squish($validated['summary']),
            'follow_up_at' => $validated['follow_up_at'] ?? null,
        ]);

        return back()->with('support_status', 'De supportnotitie is veilig opgeslagen.');
    }

    public function resolveNote(Request $request, User $user, AccountSupportNote $supportNote): RedirectResponse
    {
        abort_unless($supportNote->user_id === $user->getKey(), 404);

        if ($supportNote->resolved_at === null) {
            $supportNote->forceFill([
                'resolved_by_id' => $request->user()->getKey(),
                'resolved_at' => now(),
            ])->save();
        }

        return back()->with('support_status', 'De supportnotitie is als opgelost gemarkeerd.');
    }

    public function processDeletion(
        Request $request,
        User $user,
        AccountDeletionRequest $deletionRequest,
        ErasePlayerAccount $erasePlayerAccount,
    ): RedirectResponse {
        abort_unless($deletionRequest->user_id === $user->getKey(), 404);

        $validated = $request->validateWithBag('deletion', [
            'confirmation_email' => ['required', 'string', 'email', 'max:255'],
            'current_password' => ['required', 'current_password:web'],
        ]);

        if (Str::lower(trim($validated['confirmation_email'])) !== Str::lower($user->email)) {
            return back()->withErrors([
                'confirmation_email' => 'Het bevestigingsadres komt niet overeen met dit account.',
            ], 'deletion');
        }

        if (! in_array($deletionRequest->status, [AccountDeletionStatus::Requested, AccountDeletionStatus::Blocked], true)) {
            return back()->withErrors(['confirmation_email' => 'Dit verwijderverzoek is al afgehandeld.'], 'deletion');
        }

        try {
            $erasePlayerAccount->handle($deletionRequest, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['confirmation_email' => $exception->getMessage()], 'deletion');
        }

        return redirect()
            ->route('content-studio.accounts.show', $user)
            ->with('deletion_status', 'De speel- en accountgegevens zijn gewist. Eventuele fiscale betaalgegevens blijven apart bewaard tot de getoonde datum.');
    }
}
