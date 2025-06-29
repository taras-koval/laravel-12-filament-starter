<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->call(RolesAndPermissionsSeeder::class);

            $this->seedUsers();
        });
    }

    private function seedUsers(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->syncRoles(UserRoleEnum::ADMINISTRATOR);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
        ])->syncRoles(UserRoleEnum::MANAGER);

        User::factory()->withRole(UserRoleEnum::USER)->count(10)->create();
    }
}
