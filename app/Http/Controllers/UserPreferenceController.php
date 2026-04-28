<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Models\UserPreference;
use App\Services\CurrencyService;
use App\Services\PreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    /**
     * Get the authenticated user's preferences.
     */
    public function show(): JsonResponse
    {
        /** @var \App\Models\User | null */
        $user = auth('sanctum')->user();
        $preferences = app(PreferenceService::class)->toArray();

        if ($user && $user->preferences) {
            $preferences = $user->preferences;
        }

        $response = response()
            ->json($preferences);

        return app(PreferenceService::class)
            ->addToResponseCookie($response);
    }

    /**
     * Update the authenticated user's preferences.
     */
    public function update(UpdateUserPreferenceRequest $request): JsonResponse
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();
        $data = $request->validated();

        $preferences = app(PreferenceService::class)->setFromArray($data);

        if ($user) {
            if (!$user->preferences) {
                // Create if missing (fallback)
                $user->preferences()->create($preferences->toArray());
            } else {
                $user->preferences()->update($request->validated());
            }
        }

        $response = response()->json($preferences->toArray());

        return $preferences
            ->addToResponseCookie($response);
    }

    public function geolocate(Request $request)
    {

        $ip = $request->validate(['ip' => 'required'])['ip'];
        $token = env('FIND_IP_API_KEY');
        $language = app(PreferenceService::class)->get('language');

        $url = "https://api.findip.net/{$ip}/?token={$token}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        echo "City Name: " . $data['city']['names'][$language] . "\n";
        echo "Continent Code: " . $data['continent']['code'] . "\n";
        echo "Country Name: " . $data['country']['names'][$language] . "\n";
        echo "Latitude: " . $data['location']['latitude'] . "\n";
        echo "Longitude: " . $data['location']['longitude'] . "\n";
        echo "Time Zone: " . $data['location']['time_zone'] . "\n";
        echo "Weather Code: " . $data['location']['weather_code'] . "\n";

        foreach ($data['subdivisions'] as $subdivision) {
            if (isset($subdivision['names'][$language])) {
                echo "Subdivision Name: " . $subdivision['names'][$language] . "\n";
            }
        }

        echo "Autonomous System Number: " . $data['traits']['autonomous_system_number'] . "\n";
        echo "Autonomous System Organization: " . $data['traits']['autonomous_system_organization'] . "\n";
        echo "Connection Type: " . $data['traits']['connection_type'] . "\n";
        echo "ISP: " . $data['traits']['isp'] . "\n";
        echo "User Type: " . $data['traits']['user_type'] . "\n";
    }
}
