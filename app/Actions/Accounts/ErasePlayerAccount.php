<?php

namespace App\Actions\Accounts;

use App\Enums\AccountDeletionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AccountDeletionRequest;
use App\Models\BillingInvoice;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ErasePlayerAccount
{
    public function handle(AccountDeletionRequest $deletionRequest, User $actor): void
    {
        $blocked = false;

        DB::transaction(function () use ($actor, &$blocked, $deletionRequest): void {
            $request = AccountDeletionRequest::query()
                ->lockForUpdate()
                ->findOrFail($deletionRequest->getKey());
            $user = User::query()->lockForUpdate()->findOrFail($request->user_id);

            if (! in_array($request->status, [AccountDeletionStatus::Requested, AccountDeletionStatus::Blocked], true)) {
                throw new DomainException('Dit verwijderverzoek is al afgehandeld.');
            }

            if ($user->content_role !== null) {
                throw new DomainException('Accounts van het contentteam kunnen niet via het spelersproces worden gewist.');
            }

            $hasUnresolvedSubscription = $user->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::Trialing,
                    SubscriptionStatus::Active,
                    SubscriptionStatus::PastDue,
                ])
                ->whereNull('ended_at')
                ->exists();

            if ($hasUnresolvedSubscription) {
                $request->forceFill([
                    'status' => AccountDeletionStatus::Blocked,
                    'processed_by_id' => $actor->getKey(),
                ])->save();

                $blocked = true;

                return;
            }

            $originalEmail = $user->email;
            $retainedUntil = $this->billingRetentionDate($user);

            DB::table('user_rewards')->where('user_id', $user->getKey())->delete();
            DB::table('user_practice_items')->where('user_id', $user->getKey())->delete();
            DB::table('game_ledger')->where('user_id', $user->getKey())->delete();
            DB::table('mission_attempts')->where('user_id', $user->getKey())->delete();
            DB::table('user_mission_progress')->where('user_id', $user->getKey())->delete();
            DB::table('user_game_states')->where('user_id', $user->getKey())->delete();
            DB::table('account_support_notes')->where('user_id', $user->getKey())->delete();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
            DB::table('password_reset_tokens')->where('email', $originalEmail)->delete();

            $user->forceFill([
                'name' => 'Verwijderd account',
                'email' => sprintf('deleted-%d-%s@invalid.spaansspreken.nl', $user->getKey(), Str::lower(Str::random(10))),
                'email_verified_at' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => Str::random(60),
                'privacy_erased_at' => now(),
            ])->save();

            $request->forceFill([
                'status' => AccountDeletionStatus::Completed,
                'processed_by_id' => $actor->getKey(),
                'processed_at' => now(),
                'billing_retained_until' => $retainedUntil,
            ])->save();
        });

        if ($blocked) {
            throw new DomainException('Rond het actieve abonnement eerst af; zo voorkomen we een nieuwe afschrijving na accountwissing.');
        }
    }

    private function billingRetentionDate(User $user): ?CarbonImmutable
    {
        $lastInvoiceDate = BillingInvoice::query()
            ->whereHas('subscription', fn ($query) => $query->where('user_id', $user->getKey()))
            ->max('issued_at');
        $lastOrderDate = SubscriptionOrder::query()
            ->where('user_id', $user->getKey())
            ->selectRaw('MAX(COALESCE(paid_at, completed_at, created_at)) as latest_billing_at')
            ->value('latest_billing_at');
        $lastBillingDate = collect([$lastInvoiceDate, $lastOrderDate])->filter()->max();

        if ($lastBillingDate === null) {
            return null;
        }

        return CarbonImmutable::parse($lastBillingDate)
            ->addYears(max(1, (int) config('privacy.fiscal_retention_years', 7)))
            ->endOfDay();
    }
}
