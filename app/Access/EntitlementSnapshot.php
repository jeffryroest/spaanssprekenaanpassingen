<?php

namespace App\Access;

use Carbon\CarbonImmutable;

final readonly class EntitlementSnapshot
{
    /**
     * @param  list<string>  $entitlements
     */
    public function __construct(
        public string $state,
        public bool $accessActive,
        public array $entitlements,
        public ?string $planCode,
        public ?string $planName,
        public ?CarbonImmutable $validUntil,
        public ?int $trialDay,
        public ?int $trialDays,
    ) {}

    public function allows(string $entitlement): bool
    {
        return $this->accessActive && in_array($entitlement, $this->entitlements, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'access_active' => $this->accessActive,
            'entitlements' => $this->entitlements,
            'plan' => $this->planCode === null ? null : [
                'code' => $this->planCode,
                'name' => $this->planName,
            ],
            'valid_until' => $this->validUntil?->toAtomString(),
            'trial_day' => $this->trialDay,
            'trial_days' => $this->trialDays,
            'can_access_trial_week' => $this->allows('trial_week'),
        ];
    }
}
