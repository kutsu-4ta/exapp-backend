<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeminiSetting\UpdateGeminiSettingRequest;
use App\Models\AiUserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeminiSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user      = $request->user() ?? auth('sanctum')->user();
        $aiProfile = AiUserProfile::where('user_id', $user->id)->first();

        return response()->json([
            'geminiModel' => $aiProfile?->gemini_model,
        ]);
    }

    public function update(UpdateGeminiSettingRequest $request): JsonResponse
    {
        $user      = $request->user() ?? auth('sanctum')->user();
        $validated = $request->validated();

        $aiProfile = AiUserProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['gemini_model' => $validated['geminiModel']],
        );

        return response()->json([
            'geminiModel' => $aiProfile->gemini_model,
        ]);
    }
}
