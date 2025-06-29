<?php

namespace App\Policies;

use App\Enums\UserPermissionEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(UserPermissionEnum::VIEW_ROLES->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(UserPermissionEnum::VIEW_ROLES->value);
    }

    public function create(User $user): bool
    {
        return $user->can(UserPermissionEnum::CREATE_ROLES->value);
    }

    public function update(User $user, Role $role): bool
    {
        // Prevent editing of core system roles
        if (in_array($role->name, UserRoleEnum::values())) {
            return false;
        }

        return $user->can(UserPermissionEnum::EDIT_ROLES->value);
    }

    public function delete(User $user, Role $role): bool
    {
        // Prevent deletion of core roles
        if (in_array($role->name, UserRoleEnum::values())) {
            return false;
        }

        return $user->can(UserPermissionEnum::DELETE_ROLES->value);
    }
}
