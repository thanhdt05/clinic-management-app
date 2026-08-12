<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'ADMIN')->firstOrFail();
        $receptionistRole = Role::where('name', 'RECEPTIONIST')->firstOrFail();
        $doctorRole = Role::where('name', 'DOCTOR')->firstOrFail();
        $pharmacistRole = Role::where('name', 'PHARMACIST')->firstOrFail();
        $cashierRole = Role::where('name', 'CASHIER')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@clinic.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'receptionist@clinic.test'],
            [
                'name' => 'Receptionist',
                'password' => Hash::make('12345678'),
                'role_id' => $receptionistRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'doctor@clinic.test'],
            [
                'name' => 'Doctor',
                'password' => Hash::make('12345678'),
                'role_id' => $doctorRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'pharmacist@clinic.test'],
            [
                'name' => 'Pharmacist',
                'password' => Hash::make('12345678'),
                'role_id' => $pharmacistRole->id,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'cashier@clinic.test'],
            [
                'name' => 'Cashier',
                'password' => Hash::make('12345678'),
                'role_id' => $cashierRole->id,
                'is_active' => true,
            ]
        );
    }
}
