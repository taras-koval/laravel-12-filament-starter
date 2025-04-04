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

            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'password',
            ])->assignRole(UserRoleEnum::ADMINISTRATOR);

        });
    }
}
