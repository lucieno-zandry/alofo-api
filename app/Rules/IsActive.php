<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class IsActive implements ValidationRule
{

    public function __construct(protected ?Model $model)
    {
        $this->model = $model;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->model || $this->model->is_active())
            return;

        $fail('The :attribute should be active');
    }
}
