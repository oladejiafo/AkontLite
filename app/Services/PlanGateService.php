<?php

namespace App\Services;

use App\Models\User;

class PlanGateService
{
    private function getPlan(User $user): ?\App\Models\Plan
    {
        $company = $user->activeCompany();
        if (!$company) return null;

        $subscription = $company->subscription;
        if (!$subscription || $subscription->status !== 'active') return null;

        return $subscription->plan;
    }

    public function canRemoveWatermark(User $user): bool
    {
        return $this->getPlan($user)?->remove_watermark ?? false;
    }

    public function canUploadLogo(User $user): bool
    {
        return $this->getPlan($user)?->logo_upload ?? false;
    }

    public function canUsePaymentGateways(User $user): bool
    {
        return $this->getPlan($user)?->payment_gateways ?? false;
    }
}