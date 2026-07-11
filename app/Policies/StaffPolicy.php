<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    public function view(User $user, Staff $staff): bool
    {
        return $user->hasRole('Administrator') || $user->staff?->id === $staff->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    public function update(User $user, Staff $staff): bool
    {
        return $user->hasRole('Administrator');
    }

    public function delete(User $user, Staff $staff): bool
    {
        return $user->hasRole('Administrator');
    }
}
