<?php

namespace App\Http\Controllers;

use App\Http\Requests\DegBugfix\DegWordCardRequest;
use App\UseCases\DegBugfix\SelectDegWordCardQuizzesUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DegWordCardController extends Controller
{
    public function __construct(
        private readonly SelectDegWordCardQuizzesUseCase $selectQuizzes,
    ) {}

    public function show(DegWordCardRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $subject     = $request->input('subject');
        $limit       = $request->integer('limit', 5);
        $formulaOnly = $request->boolean('formulaOnly', false);

        $quizzes = ($this->selectQuizzes)($user->id, $subject, $limit, $formulaOnly);

        if ($quizzes->isEmpty()) {
            return response()->json(
                ['message' => '登録済み単語カードがありません'],
                422
            );
        }

        $sessionId = 'deg_word_card_' . Carbon::today()->format('Ymd');

        $questions = $quizzes->map(function ($quiz) {
            $problem = $quiz->problem;

            return [
                'id'              => $problem->id,
                'subject'         => $problem->subject?->name ?? '',
                'sub_category'    => $problem->subCategory?->name,
                'problem_context' => [
                    'original_ref'  => $problem->question_ref,
                    'user_memo'     => $problem->note,
                    'material_name' => $problem->material?->name,
                ],
                'quiz'            => [
                    'question'      => $quiz->question,
                    'options'       => [],
                    'correct_index' => null,
                    'explanation'   => $quiz->explanation,
                ],
                'last_touched_at' => $problem->last_touched_at?->toDateString(),
            ];
        })->values()->toArray();

        return response()->json([
            'session_id' => $sessionId,
            'meta'       => [
                'total_questions' => count($questions),
                'strategy'        => 'word_card',
            ],
            'questions'  => $questions,
        ]);
    }
}
