<?php

namespace App\Http\Controllers;

use App\Http\Requests\MorningBugfix\MorningBugfixRequest;
use App\Models\AiUserProfile;
use App\Models\UserProfile;
use App\UseCases\MorningBugfix\BugfixFilter;
use App\UseCases\MorningBugfix\GenerateMorningQuizUseCase;
use App\UseCases\MorningBugfix\SelectMorningProblemsUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MorningBugfixController extends Controller
{
    public function __construct(
        private readonly SelectMorningProblemsUseCase $selectProblems,
        private readonly GenerateMorningQuizUseCase   $generateQuiz,
    ) {}

    private function cleanUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    public function show(MorningBugfixRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        // 新パラメータが1つでも指定されていれば Flash Bugfix モード
        $isMorningMode = !$request->hasAny(['failureType', 'subCategoryId', 'touchedOrder', 'proficiency', 'limit']);

        if ($isMorningMode) {
            $date      = $request->filled('date')
                ? Carbon::parse($request->input('date'))
                : Carbon::today();
            $filter    = BugfixFilter::morningDefault($date);
            $sessionId = 'morning_bugfix_' . $date->format('Ymd');
            $strategy  = 'definition_focused';
        } else {
            $filter = new BugfixFilter(
                failureTypes:   (array) $request->input('failureTypes', []),
                subCategoryIds: (array) $request->input('subCategoryIds', []),
                touchedOrder:  $request->input('touchedOrder'),
                limit:         $request->integer('limit', BugfixFilter::DEFAULT_LIMIT),
                proficiencies: $request->input('proficiency', BugfixFilter::defaultProficiencies()),
                morningMode:   false,
                date:          null,
                ranks:         (array) $request->input('ranks', []),
            );
            $sessionId = 'flash_bugfix_' . Carbon::today()->format('Ymd');
            $strategy  = 'flash_bugfix';
        }

        $problems = ($this->selectProblems)($user->id, $filter);

        if ($problems->isEmpty()) {
            return response()->json([
                'session_id' => $sessionId,
                'meta'       => [
                    'total_questions' => 0,
                    'strategy'        => $strategy,
                ],
                'questions'  => [],
            ]);
        }

        $geminiToken     = UserProfile::where('user_id', $user->id)->value('gemini_token');
        $geminiModel     = AiUserProfile::where('user_id', $user->id)->value('gemini_model');

        // 将来的にはDBやパラメータから渡す
        $exam     = '中小企業診断士資格試験';
        $theme    = '定義理解';
        $count    = 4;

        $quizByProblemId = ($this->generateQuiz)($problems, $geminiToken ?: null, $geminiModel ?: null, $exam, $theme, $count);

        $questions = $problems->map(function ($problem) use ($quizByProblemId) {
            $quiz = $quizByProblemId[$problem->id] ?? null;

            return [
                'id'                 => $problem->id,
                'subject'            => $this->cleanUtf8($problem->subject?->name ?? ''),
                'sub_category'       => $this->cleanUtf8($problem->subCategory?->name),
                'sub_category_rank'  => $problem->subCategory?->rank?->value,
                'problem_context'    => [
                    'original_ref'  => $this->cleanUtf8($problem->question_ref),
                    'user_memo'     => $this->cleanUtf8($problem->note),
                    'material_name' => $this->cleanUtf8($problem->material?->name),
                ],
                'quiz'               => $quiz,
                'last_touched_at'    => $problem->last_touched_at?->toDateString(),
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
