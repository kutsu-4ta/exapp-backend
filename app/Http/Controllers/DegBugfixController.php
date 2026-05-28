<?php

namespace App\Http\Controllers;

use App\Http\Requests\DegBugfix\DegBugfixRequest;
use App\Models\AiUserProfile;
use App\Models\Problem;
use App\Models\UserProfile;
use App\UseCases\MorningBugfix\GenerateBugfixCardUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DegBugfixController extends Controller
{
    public function __construct(
        private readonly GenerateBugfixCardUseCase $generateCard,
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

        $problems = Problem::where('user_id', $user->id)
            ->with(['subject', 'subCategory', 'material'])
            ->where('is_good_question', true)
            ->whereNotNull('note')
            ->where('note', 'like', '%#Definition%')
            ->when($subject, fn ($q) => $q->whereHas('subject', fn ($q) => $q->where('name', $subject)))
            ->when(
                !empty($ranks),
                fn ($q) => $q->whereHas('subCategory', fn ($q) => $q->whereIn('rank', $ranks))
            )
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $sessionId = 'deg_bugfix_' . Carbon::today()->format('Ymd');

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
