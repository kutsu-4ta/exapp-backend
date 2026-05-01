<?php

namespace App\Http\Controllers;

use App\UseCases\AlertSetting\GetAlertSettingUseCase;
use App\UseCases\AlertSetting\UpsertAlertSettingUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertSettingController extends Controller
{
    public function __construct(
        private readonly GetAlertSettingUseCase $getUseCase,
        private readonly UpsertAlertSettingUseCase $upsertUseCase,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        $setting = ($this->getUseCase)($user->id);

        return response()->json([
            'thresholdDays'    => $setting->threshold_days,
            'includeUntouched' => $setting->include_untouched,
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'thresholdDays'    => ['required', 'integer', 'min:1'],
            'includeUntouched' => ['required', 'boolean'],
        ]);

        $user = $request->user() ?? auth('sanctum')->user();
        $setting = ($this->upsertUseCase)(
            $user->id,
            $validated['thresholdDays'],
            $validated['includeUntouched'],
        );

        return response()->json([
            'thresholdDays'    => $setting->threshold_days,
            'includeUntouched' => $setting->include_untouched,
        ]);
    }
}
