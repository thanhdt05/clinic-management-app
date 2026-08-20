<?php

namespace App\Services;

use App\Constants\ActivityAction;
use App\Constants\Messages\MedicineMessage;
use App\Events\ActivityLogged;
use App\Models\Medicine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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
        $medicine = Medicine::create($data);

        ActivityLogged::dispatch(
            ActivityAction::MEDICINE_CREATED,
            $medicine,
            Auth::user(),
            ['name' => $medicine->name, 'stock' => $medicine->stock, 'price' => $medicine->price]
        );

        return $medicine;
    }

    public function update(Medicine $medicine, array $data): Medicine
    {
        $medicine->update($data);
        $loadedMedicine = $medicine->refresh();

        ActivityLogged::dispatch(
            ActivityAction::MEDICINE_UPDATED,
            $loadedMedicine,
            Auth::user(),
            ['updated_fields' => array_keys($data)]
        );

        return $loadedMedicine;
    }

    public function delete(Medicine $medicine): void
    {
        ActivityLogged::dispatch(
            ActivityAction::MEDICINE_DELETED,
            $medicine,
            Auth::user(),
            ['name' => $medicine->name]
        );

        $medicine->delete();
    }

    public function adjustStock(Medicine $medicine, array $data): Medicine
    {
        return DB::transaction(function () use ($medicine, $data) {
            $lockedMedicine = Medicine::query()
                ->lockForUpdate()
                ->findOrFail($medicine->id);

            $oldStock = $lockedMedicine->stock;
            $newStock = $oldStock + $data['quantity'];

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => MedicineMessage::STOCK_CANNOT_BE_NEGATIVE,
                ]);
            }

            $lockedMedicine->update([
                'stock' => $newStock,
            ]);

            $loadedMedicine = $lockedMedicine->refresh();

            ActivityLogged::dispatch(
                ActivityAction::MEDICINE_STOCK_CHANGED,
                $loadedMedicine,
                Auth::user(),
                [
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'delta' => $data['quantity'],
                ]
            );

            return $loadedMedicine;
        });
    }
}
