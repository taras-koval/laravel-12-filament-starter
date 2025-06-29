<?php

namespace Database\Seeders;

use App\Enums\UserPermissionEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define specific permissions needed for each role.
        $rolePermissions = [
            UserRoleEnum::ADMINISTRATOR->value => [
                ...UserPermissionEnum::values(),
            ],
            UserRoleEnum::MANAGER->value => [
                UserPermissionEnum::ADMIN_PANEL_ACCESS->value,

                UserPermissionEnum::VIEW_USERS->value,
                UserPermissionEnum::CREATE_USERS->value,
                UserPermissionEnum::EDIT_USERS->value,
            ],
            UserRoleEnum::USER->value => [
            ],
        ];

        $this->createPermissions();
        $this->createRolesWithPermissions($rolePermissions);
    }

    private function createPermissions(): void
    {
        collect(UserPermissionEnum::cases())->each(function ($permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        });
    }

    private function createRolesWithPermissions(array $rolePermissions): void
    {
        collect($rolePermissions)->each(function ($permissions, $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => config('auth.defaults.guard'),
            ]);

            $role->syncPermissions($permissions);
        });
    }
}
