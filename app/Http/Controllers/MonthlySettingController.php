<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthlySetting\UpsertMonthlySettingRequest;
use App\UseCases\MonthlySetting\GetMonthlySettingUseCase;
use App\UseCases\MonthlySetting\UpsertMonthlySettingUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonthlySettingController extends Controller
{
    public function __construct(
        private readonly GetMonthlySettingUseCase $getUseCase,
        private readonly UpsertMonthlySettingUseCase $upsertUseCase,
    ) {}

    public function show(Request $request, int $year, int $month): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $setting = ($this->getUseCase)($user->id, $year, $month);

        return response()->json($setting);
    }

    public function upsert(UpsertMonthlySettingRequest $request, int $year, int $month): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $setting = ($this->upsertUseCase)($user->id, $year, $month, [
            'target_min' => $validated['targetMin'],
            'target_max' => $validated['targetMax'],
        ]);

        return response()->json([
            'year' => $setting->year,
            'month' => $setting->month,
            'targetMin' => $setting->target_min,
            'targetMax' => $setting->target_max,
        ]);
    }
}
