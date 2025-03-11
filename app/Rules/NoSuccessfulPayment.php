<?php

namespace App\Rules;

use App\Models\Order;
use App\Models\Transaction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSuccessfulPayment implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $successful_transaction = Transaction::where('order_uuid', $value)->first();

        if (!$successful_transaction)
            return;

        $fail("The payment of this order was already successful.");
    }
}
