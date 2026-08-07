<?php

namespace App\Http\Controllers;

use App\Http\Requests\Examination\IndexExaminationRequest;
use App\Http\Requests\Examination\StoreExaminationRequest;
use App\Http\Requests\Examination\UpdateExaminationRequest;
use App\Http\Resources\ExaminationResource;
use App\Models\Examination;
use App\Services\ExaminationService;
use App\Traits\HttpResponse;
use Illuminate\Http\Request;

class ExaminationController extends Controller
{
    use HttpResponse;

    public function __construct(private ExaminationService $examinationService)
    {}

    public function index(IndexExaminationRequest $request){
        $examinations = $this->examinationService->getAll($request->user(), $request->validated());

        return $this->paginated(
            ExaminationResource::collection($examinations),
            $examinations,
            'Examinations fetched successfully'
        );
    }

    public function store(StoreExaminationRequest $request){
        $examination = $this->examinationService->create($request->user(), $request->validated());

        return $this->success(
            ExaminationResource::make($examination),
            'Examination recorded successfully',
            201
        );
    }

    public function show(Request $request, Examination $examination){
        $examination = $this->examinationService->getDetail($request->user(), $examination);

        return $this->success(
            ExaminationResource::make($examination),
            'Examination retrieved successfully',
        );
    }

    public function update(UpdateExaminationRequest $request, Examination $examination)
    {
        $examination = $this->examinationService->update($request->user(), $examination, $request->validated());

        return $this->success(
            ExaminationResource::make($examination),
            'Examination updated successfully',
        );
    }
}
