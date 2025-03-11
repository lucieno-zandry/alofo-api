<?php

namespace App\Rules;

use App\Models\Promotion;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class BelongsToOne implements ValidationRule
{
    public function __construct(protected ?Model $model, protected string $relation, protected mixed $relation_id)
    {

    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->model)
            return;

        $relation = $this->relation;

        $data = $this->model->$relation()->where('id', $this->relation_id)->first();

        if (!$data)
            $fail("The :attribute is not linked to any {$relation}");
    }
}
