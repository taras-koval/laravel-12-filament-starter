<?php

namespace Database\Seeders;

use App\Enums\UserPermissionEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->createPermissions();
        $roles = $this->createRoles();

        // Define specific permissions needed for each role.
        $rolePermissions = [
            UserRoleEnum::ADMINISTRATOR->value => [
                UserPermissionEnum::READ->value,
                UserPermissionEnum::WRITE->value,
            ],
            UserRoleEnum::USER->value => [
                UserPermissionEnum::READ->value,
            ],
        ];

        $this->assignPermissionsToRoles($roles, $permissions, $rolePermissions);
    }

    function createPermissions(): Collection
    {
        // Map each enum to a database permission entry
        return collect(UserPermissionEnum::cases())->mapWithKeys(function ($permissionEnum) {
            $permissionName = $permissionEnum->value;
            $permissionModel = Permission::create(['name' => $permissionName, 'guard_name' => config('auth.defaults.guard')]);

            return [$permissionName => $permissionModel];
        });
    }

    private function createRoles(): Collection
    {
        // Map each enum to a database role entry
        return collect(UserRoleEnum::cases())->mapWithKeys(function ($roleEnum) {
            $roleName = $roleEnum->value;
            $roleModel = Role::create(['name' => $roleName, 'guard_name' => config('auth.defaults.guard')]);

            return [$roleName => $roleModel];
        });
    }

    private function assignPermissionsToRoles($roles, $permissions, $rolePermissions): void
    {
        foreach ($rolePermissions as $roleKey => $permissionKeys) {
            // Map permission keys to the actual permission objects
            $permissionsToAssign = collect($permissionKeys)->map(function ($permissionKey) use ($permissions) {
                return $permissions[$permissionKey];
            });

            // Assign the resolved permissions to the role
            $roles[$roleKey]->givePermissionTo($permissionsToAssign);
        }
    }
}
