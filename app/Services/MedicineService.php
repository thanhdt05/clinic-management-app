<?php

namespace App\Services;

use App\Constants\Messages\MedicineMessage;
use App\Models\Medicine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicineService
{
    private const int PER_PAGE = 10;

    public function getAll(array $filter = []): LengthAwarePaginator
    {
        $query = Medicine::query()->orderBy('name');

        if (\in_array($filter['stock_status'] ?? null, ['in_stock', 'out_of_stock'])) {
            $query->where('stock', $filter['stock_status'] === 'in_stock' ? '>' : '=', 0);
        }

        return $query->paginate($filter['per_page'] ?? self::PER_PAGE);
    }

    public function create(array $data): Medicine
    {
        return Medicine::create($data);
    }

    public function update(Medicine $medicine, array $data): Medicine
    {
        $medicine->update($data);

        return $medicine->refresh();
    }

    public function delete(Medicine $medicine): void
    {
        $medicine->delete();
    }

    public function adjustStock(Medicine $medicine, array $data): Medicine
    {
        return DB::transaction(function () use ($medicine, $data) {
            $lockedMedicine = Medicine::query()
                ->lockForUpdate()
                ->findOrFail($medicine->id);

            $newStock = $lockedMedicine->stock + $data['quantity'];

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => MedicineMessage::STOCK_CANNOT_BE_NEGATIVE,
                ]);
            }

            $lockedMedicine->update([
                'stock' => $newStock,
            ]);

            return $lockedMedicine->refresh();
        });
    }
}
