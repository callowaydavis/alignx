<?php

namespace App\Policies;

use App\Models\Component;
use App\Models\User;

class ComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Component $component): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canEdit();
    }

    public function update(User $user, Component $component): bool
    {
        return $user->canEdit();
    }

    public function delete(User $user, Component $component): bool
    {
        return $user->canEdit();
    }
}
