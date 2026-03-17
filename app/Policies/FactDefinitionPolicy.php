<?php

namespace App\Policies;

use App\Models\FactDefinition;
use App\Models\User;

class FactDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FactDefinition $factDefinition): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FactDefinition $factDefinition): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, FactDefinition $factDefinition): bool
    {
        return $user->isAdmin();
    }
}
