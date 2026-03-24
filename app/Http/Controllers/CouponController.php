<?php

namespace App\Http\Controllers;

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

        return [
            'coupon' => $coupon
        ];
    }

    public function showById(Coupon $coupon)
    {
        return [
            'coupon' => $coupon
        ];
    }

    public function index(Request $request)
    {
        $per_page = $request->get('per_page', 15);
        $coupons = Coupon::withRelations()->paginate($per_page);

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
