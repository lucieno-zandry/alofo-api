<?php

namespace App\Rules;

use App\Models\ClientCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanBeUsedClientCode implements ValidationRule
{
    public ?ClientCode $clientCode = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->clientCode = ClientCode::where('id', $value)
            ->canBeUsed()
            ->first();

        if (! $this->clientCode) {
            $fail('The selected client code cannot be used.');
        }
    }
}
