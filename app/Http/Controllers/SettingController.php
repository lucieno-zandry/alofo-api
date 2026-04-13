<?php
// app/Http/Controllers/Api/SettingController.php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Requests\SettingRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Display a listing of the settings (admin only).
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Setting::class);
        $settings = Setting::orderBy('group')->orderBy('key')->get();
        return response()->json($settings);
    }

    /**
     * Display public settings (accessible to everyone).
     */
    public function publicIndex(): JsonResponse
    {
        $settings = Setting::public()->get()
            ->mapWithKeys(fn(Setting $setting) => [$setting->key => $setting->value]);

        return response()->json($settings);
    }

    /**
     * Store a newly created setting.
     */
    public function store(SettingRequest $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $setting = Setting::create($request->validated());
        return response()->json($setting, 201);
    }

    /**
     * Display the specified setting.
     */
    public function show(Setting $setting): JsonResponse
    {
        $this->authorize('view', $setting);
        return response()->json($setting);
    }

    /**
     * Update the specified setting.
     */
    public function update(SettingRequest $request, Setting $setting): JsonResponse
    {
        $this->authorize('update', $setting);

        $setting->update($request->validated());
        return response()->json($setting);
    }

    /**
     * Remove the specified setting.
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $this->authorize('delete', $setting);
        $setting->delete();
        return response()->json(null, 204);
    }
}