<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medicines = [
            [
                'code' => 'MED-0001',
                'name' => 'Paracetamol 500mg',
                'unit' => 'Box',
                'price' => 50000.00,
                'stock' => 500,
                'is_active' => true,
            ],
            [
                'code' => 'MED-0002',
                'name' => 'Amoxicillin 500mg',
                'unit' => 'Box',
                'price' => 120000.00,
                'stock' => 300,
                'is_active' => true,
            ],
            [
                'code' => 'MED-0003',
                'name' => 'Ibuprofen 400mg',
                'unit' => 'Box',
                'price' => 75000.00,
                'stock' => 250,
                'is_active' => true,
            ],
        ];

        foreach ($medicines as $med) {
            Medicine::firstOrCreate(['code' => $med['code']], $med);
        }

        Medicine::factory()->count(10)->create();
    }
}
