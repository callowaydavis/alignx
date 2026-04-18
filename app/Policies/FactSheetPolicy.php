<?php

namespace App\Policies;

use App\Models\FactSheet;
use App\Models\User;

class FactSheetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FactSheet $factSheet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FactSheet $factSheet): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, FactSheet $factSheet): bool
    {
        return $user->isAdmin();
    }
}
