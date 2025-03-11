<?php

namespace App\Helpers;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class DiscountHelpers
{
    public static function apply_discount(Model $model, float $price)
    {
        $price_after_applied_discount = $price;

        if ($model->type === DiscountType::PERCENTAGE->value) {
            $price_after_applied_discount -= ($model->discount * $price) / 100;
        } else {
            $price_after_applied_discount -= $model->discount;
        }

        $model->applied_discount = round($price - $price_after_applied_discount, 2);
        return $price_after_applied_discount;
    }
}