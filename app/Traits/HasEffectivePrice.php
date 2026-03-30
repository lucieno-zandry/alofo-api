<?php

namespace App\Traits;

use App\Services\CurrencyService;

trait HasEffectivePrice
{
    function setValueToConvertedCurrency(string $key, float $value)
    {
        $converted = app(CurrencyService::class)->convert($value);
        $this->setAttribute($key, $converted);
        return $converted;
    }

    function setValuesToConvertedCurrency(array $amounts)
    {
        foreach ($amounts as $key => $value) {
            $this->setValueToConvertedCurrency($key, $value);
        }
    }
}
