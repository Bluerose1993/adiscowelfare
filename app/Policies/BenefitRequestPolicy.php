<?php

namespace App\Policies;

use App\Models\BenefitRequest;
use App\Models\User;

class BenefitRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Staff Member']);
    }

    public function view(User $user, BenefitRequest $benefitRequest): bool
    {
        return $user->hasRole('Administrator') || $user->staff?->id === $benefitRequest->staff_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Staff Member') && (bool) $user->staff;
    }

    public function review(User $user, BenefitRequest $benefitRequest): bool
    {
        return $user->hasRole('Administrator');
    }
}
