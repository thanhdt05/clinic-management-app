<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rolePermissions = [
            'RECEPTIONIST' => [
                'PATIENTS.FINDALL',
                'PATIENTS.CREATE',
                'PATIENTS.FINDONE',
                'PATIENTS.UPDATE',

                'APPOINTMENTS.FINDALL',
                'APPOINTMENTS.CREATE',
                'APPOINTMENTS.FINDONE',
                'APPOINTMENTS.UPDATE',
                'APPOINTMENTS.UPDATESTATUS',

                'SPECIALTIES.FINDALL',
                'SPECIALTIES.FINDONE',
                'DOCTORS.FINDALL',
                'DOCTORS.FINDONE',
            ],
            'DOCTOR' => [
                'SPECIALTIES.FINDALL',
                'SPECIALTIES.FINDONE',
                'DOCTORS.FINDALL',
                'DOCTORS.FINDONE',

                'APPOINTMENTS.FINDALL',
                'APPOINTMENTS.FINDONE',

                'EXAMINATIONS.FINDALL',
                'EXAMINATIONS.CREATE',
                'EXAMINATIONS.FINDONE',
                'EXAMINATIONS.UPDATE',

                'MEDICINES.FINDALL',
                'MEDICINES.FINDONE',

                'PATIENTS.FINDALL',
                'PATIENTS.FINDONE',

                'PRESCRIPTIONS.FINDALL',
                'PRESCRIPTIONS.CREATE',
                'PRESCRIPTIONS.FINDONE',
                'PRESCRIPTIONS.UPDATE',
                'PRESCRIPTIONS.ADDITEM',
                'PRESCRIPTIONS.UPDATEITEM',
                'PRESCRIPTIONS.REMOVEITEM',
            ],

            'PHARMACIST' => [
                'MEDICINES.FINDALL',
                'MEDICINES.CREATE',
                'MEDICINES.FINDONE',
                'MEDICINES.UPDATE',
                'MEDICINES.DELETE',
                'MEDICINES.ADJUSTSTOCK',

                'PRESCRIPTIONS.FINDALL',
                'PRESCRIPTIONS.FINDONE',
            ],

            'CASHIER' => [
                'INVOICES.FINDALL',
                'INVOICES.CREATE',
                'INVOICES.FINDONE',
                'INVOICES.UPDATE',
                'INVOICES.UPDATESTATUS',

                'PAYMENTS.FINDALL',
                'PAYMENTS.CREATE',
                'PAYMENTS.CAPTURE',

                'EXAMINATIONS.FINDALL',
                'EXAMINATIONS.FINDONE',

                'PATIENTS.FINDALL',
                'PATIENTS.FINDONE',

                'APPOINTMENTS.FINDALL',
                'APPOINTMENTS.FINDONE',
            ],
        ];

        $admin = Role::where('name', 'ADMIN')->firstOrFail();

        $permissions = Permission::pluck('id', 'name');

        $admin->permissions()->sync(
            $permissions->values()->all()
        );

        foreach ($rolePermissions as $roleName => $permissionName) {
            $role = Role::where('name', $roleName)->firstOrFail();

            $permissionIds = collect($permissionName)
                ->map(fn (string $name) => $permissions[$name])
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
