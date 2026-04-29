<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Services\CurrencyService;
use App\Traits\DynamicConditionApplicable;
use App\Traits\HasEffectivePrice;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use WithOrdering, WithPagination, WithRelationships, DynamicConditionApplicable, HasFactory, HasEffectivePrice;

    protected $fillable = [
        'code',
        'type',
        'discount',
        'min_order_value',
        'max_uses',
        'uses_count',
        'start_date',
        'end_date',
        'is_active'
    ];

    public function is_active()
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date > $now && $this->is_active;
    }

    public function is_usable()
    {
        return $this->is_active() && $this->max_uses > $this->uses_count;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function convertCurrency()
    {
        if ($this?->type === DiscountType::FIXED_AMOUNT->value) {
            $this->setValueToConvertedCurrency('discount', $this->discount);
        }

        return $this;
    }

    public function snapshot(): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'type'            => $this->type,
            'discount'        => $this->discount,
            'min_order_value' => $this->min_order_value,
        ];
    }

    public function convertSnapshotCurrency(array $snapshot): array
    {
        if ($snapshot['type'] === DiscountType::FIXED_AMOUNT->value)
            $snapshot['discount'] = app(CurrencyService::class)->convert($snapshot['discount']);

        return $snapshot;
    }

    public static function fetch(int|string $code_or_id): ?self
    {
        $coupon = null;

        if (is_string($code_or_id)) {
            $coupon = Coupon::where('code', $code_or_id)->first();
        } else {
            $coupon = Coupon::find($code_or_id);
        }

        return $coupon;
    }
}
