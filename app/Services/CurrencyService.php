<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    protected function isValidCurrency(string $currency): bool
    {
        $rates = $this->getRates();
        return isset($rates[$currency]);
    }

    public function getRates()
    {
        return Cache::remember('ecb_rates', 86400, function () {
            $response = Http::withOptions([
                'verify' => false,  // ⬅️ disable SSL verification for local dev
            ])->get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

            $xml = simplexml_load_string($response->body());
            $rates = [];
            foreach ($xml->Cube->Cube->Cube as $rate) {
                $currency = (string) $rate['currency'];
                $value = (float) $rate['rate'];
                $rates[$currency] = $value;
            }
            return $rates;
        });
    }

    public function convert(float $amount): float
    {
        $rates = $this->getRates();
        $from = $this->getFrom();
        $to = $this->getTo();

        // Convert to EUR first if needed
        $inEur = ($from === 'EUR') ? $amount : $amount / $rates[$from];
        return ($to === 'EUR') ? $inEur : $inEur * $rates[$to];
    }

    protected function getFrom(): string
    {
        return 'EUR';
    }

    protected function getTo(): string
    {
        $currency = request()->header('X-Currency');
        $rates = $this->getRates(); // cached, safe to call

        if (!$currency || !isset($rates[strtoupper($currency)])) {
            return $this->getFrom(); // fallback to EUR
        }

        return strtoupper($currency);
    }
}
