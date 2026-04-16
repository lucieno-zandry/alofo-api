<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardKpiRequest;
use App\Http\Requests\DashboardSalesTrendRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Get dashboard KPI cards: total revenue, total orders,
     * average order value, and pending refund requests.
     *
     * @param DashboardKpiRequest $request
     * @return JsonResponse
     */
    public function kpi(DashboardKpiRequest $request): JsonResponse
    {
        $kpis = $this->dashboardService->getAllKpis();

        return response()->json($kpis);
    }

    /**
     * Get daily revenue trend for the last 14 days (or custom days).
     */
    public function salesTrend(DashboardSalesTrendRequest $request): JsonResponse
    {
        $days = $request->input('days', 14);
        $trend = $this->dashboardService->getDailyRevenueTrend($days);

        return response()->json($trend);
    }
}
