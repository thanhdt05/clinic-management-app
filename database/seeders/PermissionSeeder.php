<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'USERS.FINDALL',
            'USERS.CREATE',
            'USERS.FINDONE',
            'USERS.UPDATE',
            'USERS.DELETE',
            'USERS.UPDATESTATUS',

            'ROLES.FINDALL',
            
            'SPECIALTIES.FINDALL',
            'SPECIALTIES.CREATE',
            'SPECIALTIES.FINDONE',
            'SPECIALTIES.UPDATE',
            'SPECIALTIES.DELETE',

            'DOCTORS.FINDALL',
            'DOCTORS.CREATE',
            'DOCTORS.FINDONE',
            'DOCTORS.UPDATE',
            'DOCTORS.DELETE',

            'PATIENTS.FINDALL',
            'PATIENTS.CREATE',
            'PATIENTS.FINDONE',
            'PATIENTS.UPDATE',
            'PATIENTS.DELETE',

            'APPOINTMENTS.FINDALL',
            'APPOINTMENTS.CREATE',
            'APPOINTMENTS.FINDONE',
            'APPOINTMENTS.UPDATE',
            'APPOINTMENTS.UPDATESTATUS',

            'EXAMINATIONS.FINDALL',
            'EXAMINATIONS.CREATE',
            'EXAMINATIONS.FINDONE',
            'EXAMINATIONS.UPDATE',

            'MEDICINES.FINDALL',
            'MEDICINES.CREATE',
            'MEDICINES.FINDONE',
            'MEDICINES.UPDATE',
            'MEDICINES.DELETE',
            'MEDICINES.ADJUSTSTOCK',

            'PRESCRIPTIONS.FINDALL',
            'PRESCRIPTIONS.CREATE',
            'PRESCRIPTIONS.FINDONE',
            'PRESCRIPTIONS.UPDATE',
            'PRESCRIPTIONS.ADDITEM',
            'PRESCRIPTIONS.UPDATEITEM',
            'PRESCRIPTIONS.REMOVEITEM',

            'INVOICES.FINDALL',
            'INVOICES.CREATE',
            'INVOICES.FINDONE',
            'INVOICES.UPDATE',
            'INVOICES.UPDATESTATUS',

            'PAYMENTS.FINDALL',
            'PAYMENTS.CREATE',
            'PAYMENTS.CAPTURE',

            'STATS.SHOW'
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['display_name'=> $permission]
            );
        }
    }
}
