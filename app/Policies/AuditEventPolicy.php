<?php

namespace App\Policies;

use App\Models\Complex;
use App\Models\User;

class AuditEventPolicy
{
    public function viewAny(User $user, ?Complex $complex = null): bool
    {
        return $complex
            ? $user->can('manage', $complex)
            : $user->userRoles()->where('role', 'admin')->where('active', true)->exists();
    }
}
