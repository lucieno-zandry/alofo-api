<?php
// app/Services/DashboardService.php

namespace App\Services;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Cache duration in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Get total revenue from successful payment transactions.
     */
    public function getTotalRevenue(): float
    {
        return Cache::remember('dashboard.total_revenue', self::CACHE_TTL, function () {
            return (float) Transaction::where('type', 'PAYMENT')
                ->where('status', 'SUCCESS')
                ->sum('amount');
        });
    }

    /**
     * Get total number of orders (excluding soft-deleted).
     */
    public function getTotalOrders(): int
    {
        return Cache::remember('dashboard.total_orders', self::CACHE_TTL, function () {
            return Order::count(); // Order model uses soft deletes? The schema has deleted_at, so count() excludes soft-deleted automatically if using SoftDeletes trait.
        });
    }

    /**
     * Get average order value (revenue / orders).
     * Returns 0 if there are no orders.
     */
    public function getAverageOrderValue(): float
    {
        return Cache::remember('dashboard.avg_order_value', self::CACHE_TTL, function () {
            $revenue = $this->getTotalRevenue();
            $orders = $this->getTotalOrders();

            return $orders > 0 ? $revenue / $orders : 0.0;
        });
    }

    /**
     * Get count of pending refund requests.
     */
    public function getPendingRefundsCount(): int
    {
        return Cache::remember('dashboard.pending_refunds', self::CACHE_TTL, function () {
            return RefundRequest::where('status', 'pending')->count();
        });
    }

    /**
     * Get all KPIs in one array (useful for single API call).
     */
    public function getAllKpis(): array
    {
        return [
            'total_revenue'          => $this->getTotalRevenue(),
            'total_orders'           => $this->getTotalOrders(),
            'average_order_value'    => $this->getAverageOrderValue(),
            'pending_refunds_count'  => $this->getPendingRefundsCount(),
        ];
    }

    /**
     * Get daily revenue for the last N days (default 14).
     * Returns array with 'labels' (date strings) and 'data' (revenue amounts).
     */
    public function getDailyRevenueTrend(int $days = 14): array
    {
        $cacheKey = "dashboard.revenue_trend.{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $results = Transaction::where('type', 'PAYMENT')
                ->where('status', 'SUCCESS')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as daily_revenue')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Fill missing dates with zero revenue
            $dates = collect();
            for ($i = 0; $i < $days; $i++) {
                $dates->push(Carbon::now()->subDays($days - 1 - $i)->toDateString());
            }

            $revenueByDate = $results->keyBy('date')->map(fn($item) => (float) $item->daily_revenue);

            $data = $dates->map(fn($date) => $revenueByDate->get($date, 0.0))->values();

            return [
                'labels' => $dates->values(),
                'data'   => $data,
            ];
        });
    }
}
