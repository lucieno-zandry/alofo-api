<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponDeleteRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Models\Coupon;

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

        return [
            'coupon' => $coupon
        ];
    }

    public function index()
    {
        $coupons = Coupon::applyFilters()->get();

        return [
            'coupons' => $coupons
        ];
    }

    public function destroy(CouponDeleteRequest $request)
    {
        $coupon_ids = implode(',', $request->coupon_ids);

        $deleted = Coupon::whereIn('id', $coupon_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }
}
