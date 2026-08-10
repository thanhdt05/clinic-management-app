<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'ADMIN', 'display_name' => 'Administrator'],
            ['name' => 'RECEPTIONIST', 'display_name' => 'Receptionist'],
            ['name' => 'DOCTOR', 'display_name' => 'Doctor'],
            ['name' => 'PHARMACIST', 'display_name' => 'Pharmacist'],
            ['name' => 'CASHIER', 'display_name' => 'Cashier'],
        ];
        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['display_name' => $role['display_name']]
            );
        }
    }
}
