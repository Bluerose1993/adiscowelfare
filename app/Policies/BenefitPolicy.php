<?php

namespace App\Policies;

use App\Models\Benefit;
use App\Models\User;

class BenefitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Staff Member']);
    }

    public function view(User $user, Benefit $benefit): bool
    {
        return $user->hasRole('Administrator') || $user->staff?->id === $benefit->staff_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    public function update(User $user, Benefit $benefit): bool
    {
        return $user->hasRole('Administrator');
    }
}
