<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Services\AI\AiModelCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AiSettingsController extends Controller
{
    public function __construct(private readonly AiModelCatalog $catalog) {}

    public function index(): JsonResponse
    {
        return response()->json(AiSetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(array_column($this->catalog->providers(), 'id'))],
            'model' => ['nullable', 'string'],
        ]);

        $setting = AiSetting::current();
        $setting->update($validated);

        return response()->json($setting);
    }

    public function providers(): JsonResponse
    {
        return response()->json($this->catalog->providers());
    }

    public function models(string $provider): JsonResponse
    {
        Validator::make(
            ['provider' => $provider],
            ['provider' => ['required', Rule::in(array_column($this->catalog->providers(), 'id'))]],
        )->validate();

        return response()->json($this->catalog->modelsFor($provider));
    }
}
