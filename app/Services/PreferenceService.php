<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;


class PreferenceService
{
    protected $currency;
    protected $theme;
    protected $timezone;
    protected $language;

    public function __construct()
    {
        $this->currency = app(CurrencyService::class)->getCurrencyPreference();
        $this->theme = request()->cookie('theme', 'system');
        $this->language = request()->cookie('lang', 'en');
        $this->timezone = request()->cookie('timezone', 'UTC');
    }

    function set(string $name, $value): self
    {
        $this->$name = $value;
        return $this;
    }

    function get(string $name): string
    {
        return $this->$name;
    }

    function setFromArray(array $preferences): self
    {
        foreach ($preferences as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'theme' => $this->theme,
            'timezone' => $this->timezone,
            'language' => $this->language,
        ];
    }

    function addToResponseCookie($response): Response
    {
        $response->cookie('currency', $this->get('currency'));
        $response->cookie('theme', $this->get('theme'));
        $response->cookie('language', $this->get('language'));
        $response->cookie('timezone', $this->get('timezone'));

        return $response;
    }
}
