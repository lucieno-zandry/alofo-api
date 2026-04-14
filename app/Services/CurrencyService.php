<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class CurrencyService
{
    public function __construct(protected bool $allowed, protected SettingService $setting) {}

    public function isAllowed()
    {
        return $this->allowed;
    }

    public function getUserCurrency(): ?string
    {

        if (!$this->allowed)
            return $this->getFrom();

        /** @var \App\Models\User */
        $user = auth('sanctum')->user();
        return $user?->preferences?->currency;
    }

    public function getCurrencyFromRequest(): ?string
    {
        $currency = request()->header('X-Currency', $this->getFrom());
        return $currency;
    }

    public function getCurrencyPreference(): string
    {
        return $this->getUserCurrency() ?? $this->getCurrencyFromRequest() ?? 'USD';
    }

    public function isValidCurrency(string $currency): bool
    {
        $rates = $this->getRates();
        return isset($rates[$currency]);
    }

    public function getFrom(): string
    {
        return $this->setting->get('currency', 'EUR');
    }

    protected function getTo(): string
    {
        $currency = $this->getCurrencyPreference();
        $currency = strtoupper($currency);
        $rates = $this->getRates(); // cached, safe to call

        if ($currency !== 'EUR') {
            if (!$currency || !isset($rates[$currency])) {
                return $this->getFrom(); // fallback to EUR
            }
        }

        return $currency;
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

    public function convert(float $amount, ?string $from = '', ?string $to = ''): float
    {
        $rates = $this->getRates();

        if (!$from) {
            $from = $this->getFrom();
        }

        if (!$to) {
            $to = $this->getTo();
        }

        // Convert to EUR first if needed
        $inEur = ($from === 'EUR') ? $amount : $amount / $rates[$from];
        return ($to === 'EUR') ? $inEur : $inEur * $rates[$to];
    }

    public function invert(float $amount): float
    {
        $to = $this->getFrom();
        $from = $this->getTo();

        return $this->convert(amount: $amount, to: $to, from: $from);
    }
}
