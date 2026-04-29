<?php

namespace App\Http\Controllers;

use App\UseCases\Dashboard\GetDashboardStatsUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GetDashboardStatsUseCase $statsUseCase,
    ) {}

    public function stats(Request $request): JsonResponse
    {
        // Requestインスタンス、または明示的なsanctumガードからユーザーを取得
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $stats = ($this->statsUseCase)($user->id);

        return response()->json($stats);
    }
}
