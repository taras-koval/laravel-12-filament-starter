<?php

namespace App\Policies;

use App\Enums\UserPermissionEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(UserPermissionEnum::VIEW_USERS->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(UserPermissionEnum::VIEW_USERS->value);
    }

    public function create(User $user): bool
    {
        return $user->can(UserPermissionEnum::CREATE_USERS->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(UserPermissionEnum::EDIT_USERS->value);
    }

    public function assignRoles(User $user, ?User $model = null): bool
    {
        if (!$model) {
            return $user->can(UserPermissionEnum::ASSIGN_ROLES->value);
        }

        // Don't allow assigning roles to users with the ADMINISTRATOR role
        if ($model->hasRole(UserRoleEnum::ADMINISTRATOR)) {
            return false;
        }

        // Don't allow user to assign roles to themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(UserPermissionEnum::ASSIGN_ROLES->value);
    }

    public function delete(User $user, User $model): bool
    {
        // Don't allow user to delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(UserPermissionEnum::DELETE_USERS->value);
    }

    public function viewManagers(User $user): bool
    {
        return $user->can(UserPermissionEnum::VIEW_MANAGERS->value);
    }

    public function createManagers(User $user): bool
    {
        return $user->can(UserPermissionEnum::CREATE_MANAGERS->value);
    }

    public function updateManagers(User $user, User $manager): bool
    {
        return $user->can(UserPermissionEnum::EDIT_MANAGERS->value);
    }

    public function deleteManagers(User $user, User $manager): bool
    {
        // Don't allow user to delete themselves
        if ($user->id === $manager->id) {
            return false;
        }

        return $user->can(UserPermissionEnum::DELETE_MANAGERS->value);
    }
}
