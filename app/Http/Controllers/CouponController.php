<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponDeleteRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function store(CouponCreateRequest $request)
    {
        $data = $request->validated();

        $coupon = Coupon::create($data);

        return [
            'coupon' => $coupon
        ];
    }

    public function update(CouponUpdateRequest $request, Coupon $coupon)
    {
        $data = $request->validated();
        $coupon->update($data);

        return [
            'coupon' => $coupon
        ];
    }

    public function show(string $code)
    {
        $coupon = Coupon::withRelations()->where("code", $code)->first();

        if (!$coupon || !$coupon->is_active() || !$coupon->is_usable()) $coupon = null;

        $coupon?->convertCurrency();

        return response()
            ->json([
                'coupon' => $coupon
            ])
            ->cookie('coupon_code', $coupon?->code, 60);
    }

    public function showById(Coupon $coupon)
    {
        $coupon->convertCurrency();

        return [
            'coupon' => $coupon
        ];
    }

    public function index(Request $request)
    {
        // Allowed sort columns to prevent SQL injection
        $allowedSortColumns = ['id', 'code', 'name', 'type', 'is_active', 'created_at', 'expires_at'];
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        // Validate sort column
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'id';
        }
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        // Prepare eager loads
        $withParam = $request->get('with');
        $relations = $withParam ? explode(',', $withParam) : [];

        // Build query
        $query = Coupon::query();

        // Apply search filter (search on code and name)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
            });
        }

        // Apply active status filter (if provided and not 'all')
        if ($request->has('is_active') && $request->get('is_active') !== 'all') {
            $isActive = filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Apply type filter (if provided and not 'all')
        if ($request->has('type') && $request->get('type') !== 'all') {
            $type = $request->get('type');
            $query->where('type', $type);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Apply eager loading
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Paginate
        $perPage = $request->get('per_page', 15);
        $coupons = $query->paginate($perPage);

        /** @var \App\Models\Coupon */
        foreach ($coupons as $coupon) {
            $coupon->convertCurrency();
        }

        return response()->json($coupons);
    }

    public function destroy(CouponDeleteRequest $request)
    {
        $coupon_ids = explode(',', $request->coupon_ids);

        $deleted = Coupon::whereIn('id', $coupon_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }
}
