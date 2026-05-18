<?php

namespace App\Http\Controllers;

use App\Http\Requests\DegBugfix\DegBugfixRequest;
use App\UseCases\DegBugfix\SelectDegBugfixQuizzesUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DegBugfixController extends Controller
{
    public function __construct(
        private readonly SelectDegBugfixQuizzesUseCase $selectQuizzes,
    ) {}

    public function show(DegBugfixRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $subject = $request->input('subject');
        $limit   = $request->integer('limit', 5);
        $ranks   = (array) $request->input('ranks', []);

        $quizzes = ($this->selectQuizzes)($user->id, $subject, $limit, $ranks);

        if ($quizzes->isEmpty()) {
            return response()->json(
                ['message' => '良問クイズが登録されていません'],
                422
            );
        }

        $sessionId = 'deg_bugfix_' . Carbon::today()->format('Ymd');

        $questions = $quizzes->map(function ($quiz) {
            $problem = $quiz->problem;

            return [
                'id'                => $problem->id,
                'subject'           => $problem->subject?->name ?? '',
                'sub_category'      => $problem->subCategory?->name,
                'sub_category_rank' => $problem->subCategory?->rank?->value,
                'problem_context'   => [
                    'original_ref'  => $problem->question_ref,
                    'user_memo'     => $problem->note,
                    'material_name' => $problem->material?->name,
                ],
                'quiz'              => [
                    'question'      => $quiz->question,
                    'options'       => $quiz->options,
                    'correct_index' => $quiz->correct_index,
                    'explanation'   => $quiz->explanation,
                ],
                'last_touched_at'   => $problem->last_touched_at?->toDateString(),
            ];
        })->values()->toArray();

        return response()->json([
            'session_id' => $sessionId,
            'meta'       => [
                'total_questions' => count($questions),
                'strategy'        => 'deg_bugfix',
            ],
            'questions'  => $questions,
        ]);
    }
}
