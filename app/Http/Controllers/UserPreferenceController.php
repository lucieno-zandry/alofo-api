<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Models\UserPreference;
use App\Services\CurrencyService;
use App\Services\PreferenceService;
use Illuminate\Http\JsonResponse;

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
            if (!$user->$preferences) {
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
}
