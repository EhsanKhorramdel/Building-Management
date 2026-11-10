<?php

namespace App\Policies;

use App\Models\Complex;
use App\Models\User;

class ComplexPolicy
{
    public function view(User $user, Complex $complex): bool
    {
        return $user->userRoles()->where('complex_id', $complex->id)->where('active', true)
            ->whereIn('role', ['resident', 'manager'])->exists();
    }

    public function viewAttachment(User $user, Complex $complex): bool
    {
        return $this->view($user, $complex)
            || $user->userRoles()->where('role', 'admin')->where('active', true)->exists();
    }

    public function manage(User $user, Complex $complex): bool
    {
        return $user->userRoles()->where('complex_id', $complex->id)->where('active', true)
            ->where('role', 'manager')->exists();
    }
}
