<?php

namespace App\Http\Controllers;

use App\Constants\Messages\ExaminationMessage;
use App\Http\Requests\Examination\IndexExaminationRequest;
use App\Http\Requests\Examination\StoreExaminationRequest;
use App\Http\Requests\Examination\UpdateExaminationRequest;
use App\Http\Resources\ExaminationResource;
use App\Models\Examination;
use App\Services\ExaminationService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExaminationController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly ExaminationService $examinationService
    ) {}

    public function index(IndexExaminationRequest $request): JsonResponse
    {
        $examinations = $this->examinationService->getAll($request->user(), $request->validated());

        return $this->paginated(
            ExaminationResource::collection($examinations),
            $examinations,
            ExaminationMessage::EXAMINATION_LIST_RETRIEVED
        );
    }

    public function store(StoreExaminationRequest $request): JsonResponse
    {
        $examination = $this->examinationService->create($request->user(), $request->validated());

        return $this->success(
            ExaminationResource::make($examination),
            ExaminationMessage::EXAMINATION_CREATED,
            Response::HTTP_CREATED
        );
    }

    public function show(Request $request, Examination $examination): JsonResponse
    {
        $examination = $this->examinationService->getDetail($request->user(), $examination);

        return $this->success(
            ExaminationResource::make($examination),
            ExaminationMessage::EXAMINATION_DETAILS_RETRIEVED
        );
    }

    public function update(UpdateExaminationRequest $request, Examination $examination): JsonResponse
    {
        $examination = $this->examinationService->update($request->user(), $examination, $request->validated());

        return $this->success(
            ExaminationResource::make($examination),
            ExaminationMessage::EXAMINATION_UPDATED
        );
    }
}
