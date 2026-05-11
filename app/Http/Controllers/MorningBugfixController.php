<?php

namespace App\Http\Controllers;

use App\Models\AiUserProfile;
use App\Models\UserProfile;
use App\UseCases\MorningBugfix\GenerateMorningQuizUseCase;
use App\UseCases\MorningBugfix\SelectMorningProblemsUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MorningBugfixController extends Controller
{
    public function __construct(
        private readonly SelectMorningProblemsUseCase $selectProblems,
        private readonly GenerateMorningQuizUseCase   $generateQuiz,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $problems = ($this->selectProblems)($user->id, $date);

        if ($problems->isEmpty()) {
            return response()->json([
                'session_id' => 'morning_bugfix_' . $date->format('Ymd'),
                'meta'       => [
                    'total_questions' => 0,
                    'strategy'        => 'definition_focused',
                ],
                'questions'  => [],
            ]);
        }

        $geminiToken = UserProfile::where('user_id', $user->id)->value('gemini_token');
        $geminiModel = AiUserProfile::where('user_id', $user->id)->value('gemini_model');
        $quizByProblemId = ($this->generateQuiz)($problems, $geminiToken ?: null, $geminiModel ?: null);

        $questions = $problems->map(function ($problem) use ($quizByProblemId) {
            $quiz = $quizByProblemId[$problem->id] ?? null;

            return [
                'id'              => $problem->id,
                'subject'         => $problem->subject?->name ?? '',
                'sub_category'    => $problem->subCategory?->name,
                'problem_context' => [
                    'original_ref' => $problem->question_ref,
                    'user_memo'    => $problem->note,
                ],
                'quiz'            => $quiz,
                'last_touched_at' => $problem->last_touched_at?->toDateString(),
            ];
        })->values()->toArray();

        return response()->json([
            'session_id' => 'morning_bugfix_' . $date->format('Ymd'),
            'meta'       => [
                'total_questions' => count($questions),
                'strategy'        => 'definition_focused',
            ],
            'questions'  => $questions,
        ]);
    }
}
