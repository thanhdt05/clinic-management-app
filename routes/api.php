<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\SpecialtyController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::middleware('permission')->group(function () {
        Route::apiResource('users', UserController::class);

        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
            ->name('users.updateStatus');

        Route::apiResource('specialties', SpecialtyController::class);
        Route::apiResource('doctors', DoctorController::class);
        Route::apiResource('patients', PatientController::class);
        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'store', 'show', 'update']);

        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
            ->name('appointments.updateStatus');

        Route::apiResource('examinations', ExaminationController::class)->only(['index', 'store', 'show', 'update']);

        Route::apiResource('medicines', MedicineController::class);

        Route::patch('medicines/{medicine}/stock', [MedicineController::class, 'adjustStock'])
            ->name('medicines.adjustStock');

        Route::post('prescriptions', [PrescriptionController::class, 'store'])
            ->name('prescriptions.store');
    });
});
