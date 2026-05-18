<?php

namespace App\Http\Controllers;

use App\Http\Requests\BugfixSession\BugfixSessionRequest;
use App\Models\AiUserProfile;
use App\Models\ProblemQuiz;
use App\Models\UserProfile;
use App\UseCases\BugfixSession\SelectSavedBugfixQuizzesUseCase;
use App\UseCases\FlashCard\FlashCardFilter;
use App\UseCases\FlashCard\GenerateFlashCardQuizUseCase;
use App\UseCases\FlashCard\SelectFlashCardProblemsUseCase;
use App\UseCases\MorningBugfix\BugfixFilter;
use App\UseCases\MorningBugfix\GenerateMorningQuizUseCase;
use App\UseCases\MorningBugfix\SelectMorningProblemsUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BugfixSessionController extends Controller
{
    public function __construct(
        private readonly SelectMorningProblemsUseCase    $selectAiMultipleChoice,
        private readonly SelectFlashCardProblemsUseCase  $selectAiWordCard,
        private readonly SelectSavedBugfixQuizzesUseCase $selectSaved,
        private readonly GenerateMorningQuizUseCase      $generateMultipleChoice,
        private readonly GenerateFlashCardQuizUseCase    $generateWordCard,
    ) {}

    public function show(BugfixSessionRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $source         = $request->input('source', 'ai');
        $quizMode       = $request->input('quizMode', 'multiple_choice');
        $subjects       = (array) $request->input('subjects', []);
        $formulaOnly    = $request->boolean('formulaOnly', false);
        $failureTypes   = (array) $request->input('failureTypes', []);
        $subCategoryIds = array_map('intval', (array) $request->input('subCategoryIds', []));
        $proficiencies  = (array) $request->input('proficiency', []);
        $touchedOrder   = $request->input('touchedOrder');
        $limit          = $request->integer('limit', BugfixFilter::DEFAULT_LIMIT);

        $sessionId = 'bugfix_session_' . Carbon::today()->format('Ymd');

        if ($source === 'saved') {
            return $this->savedResponse(
                $user->id, $quizMode, $subjects, $failureTypes, $subCategoryIds,
                $proficiencies, $touchedOrder, $formulaOnly, $limit, $sessionId,
            );
        }

        // source=ai
        $subject = $subjects[0] ?? null;

        if ($quizMode === 'word_card') {
            return $this->aiWordCardResponse(
                $user->id, $subject, $failureTypes, $subCategoryIds, $proficiencies,
                $touchedOrder, $formulaOnly, $limit, $sessionId,
            );
        }

        return $this->aiMultipleChoiceResponse(
            $user->id, $subject, $failureTypes, $subCategoryIds, $proficiencies,
            $touchedOrder, $formulaOnly, $limit, $sessionId,
        );
    }

    // ── source=saved ──────────────────────────────────────────────────────────

    private function savedResponse(
        int $userId,
        string $quizMode,
        array $subjects,
        array $failureTypes,
        array $subCategoryIds,
        array $proficiencies,
        ?string $touchedOrder,
        bool $formulaOnly,
        int $limit,
        string $sessionId,
    ): JsonResponse {
        $quizzes = ($this->selectSaved)(
            $userId, $quizMode, $subjects, $failureTypes, $subCategoryIds,
            $proficiencies, $touchedOrder, $formulaOnly, $limit,
        );

        $strategy = $quizMode === 'word_card' ? 'word_card' : 'deg_bugfix';

        if ($quizzes->isEmpty()) {
            return response()->json([
                'session_id' => $sessionId,
                'meta'       => ['total_questions' => 0, 'strategy' => $strategy],
                'questions'  => [],
            ]);
        }

        $questions = $quizzes->map(function (ProblemQuiz $quiz) {
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
                    'options'       => $quiz->options ?? [],
                    'correct_index' => $quiz->correct_index,
                    'explanation'   => $quiz->explanation,
                ],
                'last_touched_at' => $problem->last_touched_at?->toDateString(),
            ];
        })->values()->toArray();

        return response()->json([
            'session_id' => $sessionId,
            'meta'       => ['total_questions' => count($questions), 'strategy' => $strategy],
            'questions'  => $questions,
        ]);
    }

    // ── source=ai, quizMode=word_card ─────────────────────────────────────────

    private function aiWordCardResponse(
        int $userId,
        ?string $subject,
        array $failureTypes,
        array $subCategoryIds,
        array $proficiencies,
        ?string $touchedOrder,
        bool $formulaOnly,
        int $limit,
        string $sessionId,
    ): JsonResponse {
        if ($subject === null) {
            return response()->json(['message' => 'quizMode=word_card の場合は subjects の指定が必要です'], 422);
        }

        $filter = new FlashCardFilter(
            subject:        $subject,
            failureTypes:   $failureTypes,
            subCategoryIds: $subCategoryIds,
            touchedOrder:   $touchedOrder,
            limit:          $limit,
            proficiencies:  $proficiencies ?: FlashCardFilter::defaultProficiencies(),
            formulaOnly:    $formulaOnly,
        );

        $problems = ($this->selectAiWordCard)($userId, $filter);
        $strategy = $formulaOnly ? 'formula_check' : 'word_card';

        if ($problems->isEmpty()) {
            return response()->json([
                'session_id' => $sessionId,
                'meta'       => ['total_questions' => 0, 'strategy' => $strategy],
                'questions'  => [],
            ]);
        }

        [$geminiToken, $geminiModel] = $this->geminiCredentials($userId);
        $quizByProblemId = ($this->generateWordCard)($problems, $geminiToken, $geminiModel, $formulaOnly);

        return $this->buildAiSessionResponse($sessionId, $strategy, $problems, $quizByProblemId);
    }

    // ── source=ai, quizMode=multiple_choice ───────────────────────────────────

    private function aiMultipleChoiceResponse(
        int $userId,
        ?string $subject,
        array $failureTypes,
        array $subCategoryIds,
        array $proficiencies,
        ?string $touchedOrder,
        bool $formulaOnly,
        int $limit,
        string $sessionId,
    ): JsonResponse {
        $filter = new BugfixFilter(
            failureTypes:   $failureTypes,
            subCategoryIds: $subCategoryIds,
            touchedOrder:   $touchedOrder,
            limit:          $limit,
            proficiencies:  $proficiencies ?: BugfixFilter::defaultProficiencies(),
            morningMode:    false,
            date:           null,
            subject:        $subject,
            formulaOnly:    $formulaOnly,
        );

        $problems = ($this->selectAiMultipleChoice)($userId, $filter);
        $strategy = 'flash_bugfix';

        if ($problems->isEmpty()) {
            return response()->json([
                'session_id' => $sessionId,
                'meta'       => ['total_questions' => 0, 'strategy' => $strategy],
                'questions'  => [],
            ]);
        }

        [$geminiToken, $geminiModel] = $this->geminiCredentials($userId);
        $quizByProblemId = ($this->generateMultipleChoice)(
            $problems, $geminiToken, $geminiModel,
            '中小企業診断士資格試験', '定義理解', 4,
        );

        return $this->buildAiSessionResponse($sessionId, $strategy, $problems, $quizByProblemId);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function geminiCredentials(int $userId): array
    {
        return [
            UserProfile::where('user_id', $userId)->value('gemini_token') ?: null,
            AiUserProfile::where('user_id', $userId)->value('gemini_model') ?: null,
        ];
    }

    private function buildAiSessionResponse(
        string $sessionId,
        string $strategy,
        Collection $problems,
        array $quizByProblemId,
    ): JsonResponse {
        $questions = $problems->map(function ($problem) use ($quizByProblemId) {
            return [
                'id'              => $problem->id,
                'subject'         => $problem->subject?->name ?? '',
                'sub_category'    => $problem->subCategory?->name,
                'problem_context' => [
                    'original_ref'  => $problem->question_ref,
                    'user_memo'     => $problem->note,
                    'material_name' => $problem->material?->name,
                ],
                'quiz'            => $quizByProblemId[$problem->id] ?? null,
                'last_touched_at' => $problem->last_touched_at?->toDateString(),
            ];
        })->values()->toArray();

        return response()->json([
            'session_id' => $sessionId,
            'meta'       => ['total_questions' => count($questions), 'strategy' => $strategy],
            'questions'  => $questions,
        ]);
    }
}
