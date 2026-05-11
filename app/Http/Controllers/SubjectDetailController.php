<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlashcardResource;
use App\UseCases\Problem\SelectReviewProblemsUseCase;
use App\UseCases\Subject\GetSubjectActivityUseCase;
use App\UseCases\Subject\GetSubjectMonthlyGoalUseCase;
use App\UseCases\Subject\GetSubjectSettingsUseCase;
use App\UseCases\Subject\UpsertSubjectMonthlyGoalUseCase;
use App\UseCases\Subject\UpsertSubjectSettingsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectDetailController extends Controller
{
    public function __construct(
        private readonly GetSubjectSettingsUseCase       $getSettings,
        private readonly UpsertSubjectSettingsUseCase    $upsertSettings,
        private readonly GetSubjectMonthlyGoalUseCase    $getMonthlyGoal,
        private readonly UpsertSubjectMonthlyGoalUseCase $upsertMonthlyGoal,
        private readonly SelectReviewProblemsUseCase     $selectReviewProblems,
        private readonly GetSubjectActivityUseCase       $getActivity,
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

    public function reviewProblems(Request $request, string $subject): JsonResponse
    {
        $user     = $request->user() ?? auth('sanctum')->user();
        $problems = ($this->selectReviewProblems)($user->id, urldecode($subject));

        return response()->json(FlashcardResource::collection($problems));
    }

    public function activity(Request $request, string $subject): JsonResponse
    {
        $validated = $request->validate([
            'year'  => ['required', 'integer'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $user = $request->user() ?? auth('sanctum')->user();
        $data = ($this->getActivity)($user->id, urldecode($subject), (int) $validated['year'], (int) $validated['month']);

        return response()->json($data);
    }
}
