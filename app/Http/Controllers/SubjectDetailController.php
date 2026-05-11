<?php

namespace App\Http\Controllers;

use App\UseCases\Subject\GetSubjectMonthlyGoalUseCase;
use App\UseCases\Subject\GetSubjectSettingsUseCase;
use App\UseCases\Subject\UpsertSubjectMonthlyGoalUseCase;
use App\UseCases\Subject\UpsertSubjectSettingsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectDetailController extends Controller
{
    public function __construct(
        private readonly GetSubjectSettingsUseCase     $getSettings,
        private readonly UpsertSubjectSettingsUseCase  $upsertSettings,
        private readonly GetSubjectMonthlyGoalUseCase  $getMonthlyGoal,
        private readonly UpsertSubjectMonthlyGoalUseCase $upsertMonthlyGoal,
    ) {}

    public function showSettings(Request $request, string $subject): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        return response()->json(($this->getSettings)($user->id, urldecode($subject)));
    }

    public function upsertSettings(Request $request, string $subject): JsonResponse
    {
        $validated = $request->validate([
            'finalTarget' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user() ?? auth('sanctum')->user();
        return response()->json(($this->upsertSettings)($user->id, urldecode($subject), $validated['finalTarget'] ?? null));
    }

    public function showMonthlyGoal(Request $request, string $subject, int $year, int $month): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        return response()->json(($this->getMonthlyGoal)($user->id, urldecode($subject), $year, $month));
    }

    public function upsertMonthlyGoal(Request $request, string $subject, int $year, int $month): JsonResponse
    {
        $validated = $request->validate([
            'goal' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user() ?? auth('sanctum')->user();
        return response()->json(($this->upsertMonthlyGoal)($user->id, urldecode($subject), $year, $month, $validated['goal'] ?? null));
    }
}
