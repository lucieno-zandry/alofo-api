<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;

class UserPreferenceController extends Controller
{
    /**
     * Get the authenticated user's preferences.
     */
    public function show(): JsonResponse
    {
        $preferences = auth()->user()->preferences;

        if (!$preferences) {
            // Return defaults if record doesn't exist (shouldn't happen if we create on registration)
            return response()->json([
                'theme'    => 'system',
                'language' => 'en',
                'timezone' => 'UTC',
                'currency' => 'USD',
            ]);
        }

        return response()->json(["preferences" => $preferences]);
    }

    /**
     * Update the authenticated user's preferences.
     */
    public function update(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $user = auth()->user();
        $preferences = $user->preferences;

        if (!$preferences) {
            // Create if missing (fallback)
            $preferences = $user->preferences()->create($request->validated());
        } else {
            $preferences->update($request->validated());
        }

        return response()->json(["preferences" => $preferences]);
    }
}
