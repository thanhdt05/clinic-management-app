<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Services\DoctorService;
use App\Traits\HttpResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly DoctorService $doctorService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = $this->doctorService->getAll();

        return $this->paginated(
            DoctorResource::collection($doctors),
            $doctors,
            'Lấy danh sách bác sĩ thành công.'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDoctorRequest $request)
    {
        $doctor = $this->doctorService->create($request->validated());

        return $this->success(
            DoctorResource::make($doctor),
            'Thêm bác sĩ thành công.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor)
    {
        return $this->success(
            DoctorResource::make($doctor->load(['user', 'specialty'])),
            'Lấy thông tin bác sĩ thành công.'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $doctor = $this->doctorService->update($doctor, $request->validated());

        return $this->success(
            DoctorResource::make($doctor),
            'Cập nhật thông tin bác sĩ thành công.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        $this->doctorService->delete($doctor);

        return $this->success(
            [],
            'Xóa bác sĩ thành công.'
        );
    }
}
