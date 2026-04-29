<?php

namespace App\Rules;

use App\Models\Variant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class InStock implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    protected Variant|array|null $variantSource = null;

    public function __construct(Variant|int|array|null $variant = null)
    {
        if (is_int($variant)) {
            $this->variantSource = Variant::find($variant);
        } else {
            $this->variantSource = $variant;
        }
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $variant = $this->resolveVariant($attribute);

        if (!$variant) {
            return;
        }

        if ($value > $variant->stock) {
            $fail('The :attribute must be under the available stock.');
        }
    }

    protected function resolveVariant(string $attribute): ?Variant
    {
        // 1. If a single Variant was passed
        if ($this->variantSource instanceof Variant) {
            return $this->variantSource;
        }

        // 2. Extract index from attribute
        preg_match('/variants\.(\d+)\.count/', $attribute, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        $index = $matches[1];
        $variantId = $this->data['variants'][$index]['variant_id'] ?? null;

        if (!$variantId) {
            return null;
        }

        // 3. If array of preloaded variants was passed
        if (is_array($this->variantSource)) {
            return $this->variantSource[$variantId] ?? null;
        }

        // 4. Fallback to DB
        return Variant::find($variantId);
    }
}
