<?php

namespace App\Http\Controllers;

use App\Constants\Messages\StatsMessage;
use App\Services\StatsService;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly StatsService $statsService
    ) {}

    public function show(): JsonResponse
    {
        $stats = $this->statsService->getAdminDashboardStats();

        return $this->success(
            $stats,
            StatsMessage::STATS_RETRIEVED
        );
    }
}
