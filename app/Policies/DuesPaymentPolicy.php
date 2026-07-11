<?php

namespace App\Policies;

use App\Models\DuesPayment;
use App\Models\User;

class DuesPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    public function view(User $user, DuesPayment $duesPayment): bool
    {
        return $user->hasRole('Administrator') || $user->staff?->id === $duesPayment->staff_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    public function update(User $user, DuesPayment $duesPayment): bool
    {
        return $user->hasRole('Administrator');
    }

    public function delete(User $user, DuesPayment $duesPayment): bool
    {
        return $user->hasRole('Administrator');
    }
}
