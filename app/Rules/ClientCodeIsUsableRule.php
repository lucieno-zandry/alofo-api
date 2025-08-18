<?php

namespace App\Rules;

use App\Models\ClientCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ClientCodeIsUsableRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $client_code = ClientCode::where('code', $value)
            ->where('user_id', null)
            ->first();

        if (!$client_code)
            $fail('The client code is not usable.');
    }
}
