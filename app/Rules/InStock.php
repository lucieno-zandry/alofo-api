<?php

namespace App\Rules;

use App\Models\Variant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class InStock implements ValidationRule
{
    public function __construct(protected ?Model $variant)
    {

    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->variant)
            return;

        if ($value > $this->variant->stock) {
            $fail('The :attribute must be under the available stock.');
        }
    }
}
