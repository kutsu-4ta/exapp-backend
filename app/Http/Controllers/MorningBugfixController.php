<?php

namespace App\Http\Controllers;

use App\Http\Requests\MorningBugfix\MorningBugfixRequest;
use App\Models\AiUserProfile;
use App\Models\UserProfile;
use App\UseCases\MorningBugfix\BugfixFilter;
use App\UseCases\MorningBugfix\GenerateBugfixCardUseCase;
use App\UseCases\MorningBugfix\SelectMorningProblemsUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MorningBugfixController extends Controller
{
    public function __construct(
        private readonly SelectMorningProblemsUseCase $selectProblems,
        private readonly GenerateBugfixCardUseCase    $generateCard,
    ) {}

    public function show(MorningBugfixRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $isMorningMode = !$request->hasAny(['failureType', 'subCategoryId', 'touchedOrder', 'proficiency', 'limit']);

        if ($isMorningMode) {
            $date      = $request->filled('date')
                ? Carbon::parse($request->input('date'))
                : Carbon::today();
            $filter    = BugfixFilter::morningDefault($date);
            $sessionId = 'morning_bugfix_' . $date->format('Ymd');
        } else {
            $filter = new BugfixFilter(
                failureTypes:   (array) $request->input('failureTypes', []),
                subCategoryIds: (array) $request->input('subCategoryIds', []),
                touchedOrder:  $request->input('touchedOrder'),
                limit:         $request->integer('limit', BugfixFilter::DEFAULT_LIMIT),
                proficiencies: $request->input('proficiency', BugfixFilter::defaultProficiencies()),
                morningMode:   false,
                date:          null,
                subject:       $request->input('subject'),
                ranks:         (array) $request->input('ranks', []),
            );
            $sessionId = 'flash_bugfix_' . Carbon::today()->format('Ymd');
        }

        $problems = ($this->selectProblems)($user->id, $filter);

        if ($problems->isEmpty()) {
            return response()->json([
                'session_id' => $sessionId,
                'meta'       => ['total_questions' => 0, 'strategy' => 'bugfix_card'],
                'questions'  => [],
            ]);
        }

        $geminiToken = UserProfile::where('user_id', $user->id)->value('gemini_token');
        $geminiModel = AiUserProfile::where('user_id', $user->id)->value('gemini_model');

        $cardByProblemId = ($this->generateCard)($problems, $geminiToken ?: null, $geminiModel ?: null);

        $questions = $problems
            ->filter(fn ($p) => isset($cardByProblemId[$p->id]))
            ->map(fn ($p) => [
                'id'              => $p->id,
                'subject'         => $p->subject?->name ?? '',
                'sub_category'    => $p->subCategory?->name,
                'problem_context' => [
                    'original_ref'  => $p->question_ref,
                    'user_memo'     => $p->note,
                    'material_name' => $p->material?->name,
                ],
                'quiz'            => $cardByProblemId[$p->id],
                'last_touched_at' => $p->last_touched_at?->toDateString(),
            ])
            ->values()
            ->toArray();

        return response()->json([
            'session_id' => $sessionId,
            'meta'       => [
                'total_questions' => count($questions),
                'strategy'        => 'bugfix_card',
            ],
            'questions'  => $questions,
        ]);
    }
}
