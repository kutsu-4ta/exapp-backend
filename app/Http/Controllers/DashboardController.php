<?php

namespace App\Http\Controllers;

use App\Http\Resources\DailyLogResource;
use App\UseCases\Dashboard\GetDashboardUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GetDashboardUseCase $dashboardUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ['stats' => $stats, 'todayLog' => $todayLog, 'prevDayLog' => $prevDayLog, 'alertItems' => $alertItems]
            = ($this->dashboardUseCase)($user->id);

        return response()->json([
            'stats'      => $stats,
            'todayLog'   => $todayLog ? new DailyLogResource($todayLog) : null,
            'prevDayLog' => $prevDayLog ? new DailyLogResource($prevDayLog) : null,
            'alertItems' => $alertItems,
        ]);
    }
}
