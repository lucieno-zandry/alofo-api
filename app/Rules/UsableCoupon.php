<?php

namespace App\Rules;

use App\Models\Coupon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class UsableCoupon implements ValidationRule, DataAwareRule
{
    protected array $data = [];
    protected ?Coupon $coupon = null;

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If already resolved (avoid duplicate queries)
        if (!$this->coupon) {
            $this->coupon = $this->resolveCoupon($attribute, $value);
        }

        if (!$this->coupon) {
            $fail('This coupon does not exist.');
        }

        if (!$this->coupon->is_usable()) {
            $fail('This coupon is not usable.');
        }
    }

    protected function resolveCoupon(string $attribute, mixed $value): ?Coupon
    {
        // Optional: support both `coupon_code` and `coupon_id`
        if (str_contains($attribute, 'id')) {
            return Coupon::fetch((int) $value);
        }

        return Coupon::fetch((string) $value);
    }
}
