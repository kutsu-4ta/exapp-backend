<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlashCard\FlashCardRequest;
use App\Models\AiUserProfile;
use App\Models\UserProfile;
use App\UseCases\FlashCard\FlashCardFilter;
use App\UseCases\FlashCard\GenerateFlashCardQuizUseCase;
use App\UseCases\FlashCard\SelectFlashCardProblemsUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class FlashCardSessionController extends Controller
{
    public function __construct(
        private readonly SelectFlashCardProblemsUseCase $selectProblems,
        private readonly GenerateFlashCardQuizUseCase   $generateQuiz,
    ) {}

    public function show(FlashCardRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $formulaOnly = $request->boolean('formulaOnly', false);

        $filter = new FlashCardFilter(
            subject:        $request->input('subject'),
            failureTypes:   (array) $request->input('failureTypes', []),
            subCategoryIds: (array) $request->input('subCategoryIds', []),
            touchedOrder:   $request->input('touchedOrder'),
            limit:          $request->integer('limit', FlashCardFilter::DEFAULT_LIMIT),
            proficiencies:  $request->input('proficiency', FlashCardFilter::defaultProficiencies()),
            formulaOnly:    $formulaOnly,
            ranks:          (array) $request->input('ranks', []),
        );

        $problems = ($this->selectProblems)($user->id, $filter);

        $strategy  = $formulaOnly ? 'formula_check' : 'word_card';
        $sessionId = $formulaOnly
            ? 'flash_card_formula_' . Carbon::today()->format('Ymd')
            : 'flash_card_' . Carbon::today()->format('Ymd');

        if ($problems->isEmpty()) {
            return response()->json([
                'session_id' => $sessionId,
                'meta'       => ['total_questions' => 0, 'strategy' => $strategy],
                'questions'  => [],
            ]);
        }

        $geminiToken = UserProfile::where('user_id', $user->id)->value('gemini_token');
        $geminiModel = AiUserProfile::where('user_id', $user->id)->value('gemini_model');

        $quizByProblemId = ($this->generateQuiz)(
            $problems,
            $geminiToken ?: null,
            $geminiModel ?: null,
            $formulaOnly,
        );

        $questions = $problems->map(function ($problem) use ($quizByProblemId) {
            $quiz = $quizByProblemId[$problem->id] ?? null;

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
                'quiz'              => $quiz,
                'last_touched_at'   => $problem->last_touched_at?->toDateString(),
            ];
        })->values()->toArray();

        return response()->json([
            'session_id' => $sessionId,
            'meta'       => [
                'total_questions' => count($questions),
                'strategy'        => $strategy,
            ],
            'questions'  => $questions,
        ]);
    }
}
