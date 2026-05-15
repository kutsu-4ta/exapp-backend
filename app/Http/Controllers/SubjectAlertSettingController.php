<?php

namespace App\Http\Controllers;

use App\UseCases\SubjectAlertSetting\GetSubjectAlertSettingUseCase;
use App\UseCases\SubjectAlertSetting\UpsertSubjectAlertSettingUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectAlertSettingController extends Controller
{
    public function __construct(
        private readonly GetSubjectAlertSettingUseCase    $getUseCase,
        private readonly UpsertSubjectAlertSettingUseCase $upsertUseCase,
    ) {}

    public function show(Request $request, string $subject): JsonResponse
    {
        $user    = $request->user() ?? auth('sanctum')->user();
        $setting = ($this->getUseCase)($user->id, urldecode($subject));

        return response()->json($this->toResponse($setting));
    }

    public function upsert(Request $request, string $subject): JsonResponse
    {
        $validated = $request->validate([
            'touchAlertEnabled'    => ['required', 'boolean'],
            'thresholdDays'        => ['required', 'integer', 'min:1'],
            'includeUntouched'     => ['required', 'boolean'],
            'minutesAlertEnabled'  => ['required', 'boolean'],
            'minutesThresholdDays' => ['required', 'integer', 'min:1'],
            'minutesThreshold'     => ['required', 'integer', 'min:1'],
        ]);

        $user    = $request->user() ?? auth('sanctum')->user();
        $setting = ($this->upsertUseCase)(
            $user->id,
            urldecode($subject),
            $validated['touchAlertEnabled'],
            $validated['thresholdDays'],
            $validated['includeUntouched'],
            $validated['minutesAlertEnabled'],
            $validated['minutesThresholdDays'],
            $validated['minutesThreshold'],
        );

        return response()->json($this->toResponse($setting));
    }

    private function toResponse(\App\Models\SubjectAlertSetting $setting): array
    {
        return [
            'touchAlertEnabled'    => $setting->touch_alert_enabled,
            'thresholdDays'        => $setting->threshold_days,
            'includeUntouched'     => $setting->include_untouched,
            'minutesAlertEnabled'  => $setting->minutes_alert_enabled,
            'minutesThresholdDays' => $setting->minutes_threshold_days,
            'minutesThreshold'     => $setting->minutes_threshold,
        ];
    }
}
