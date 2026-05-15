<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamSession\CompleteExamSessionRequest;
use App\Http\Requests\ExamSession\CreateExamSessionRequest;
use App\Http\Requests\ExamSession\CreateQuickScoreRequest;
use App\Http\Requests\ExamSession\ListExamSessionsRequest;
use App\Http\Requests\ExamSession\UpdateExamQuestionRequest;
use App\Http\Requests\ExamSession\UpdateExamSessionRequest;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\ExamSessionResource;
use App\Http\Resources\ExamSessionSummaryResource;
use App\UseCases\ExamSession\CompleteExamSessionUseCase;
use App\UseCases\ExamSession\CreateExamSessionUseCase;
use App\UseCases\ExamSession\CreateQuickScoreExamSessionUseCase;
use App\UseCases\ExamSession\DeleteExamSessionUseCase;
use App\UseCases\ExamSession\GetExamSessionUseCase;
use App\UseCases\ExamSession\GetExamSubjectStatsUseCase;
use App\UseCases\ExamSession\ListExamSessionsUseCase;
use App\UseCases\ExamSession\UpdateExamQuestionTimestampUseCase;
use App\UseCases\ExamSession\UpdateExamSessionUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExamSessionController extends Controller
{
    public function __construct(
        private readonly ListExamSessionsUseCase $listUseCase,
        private readonly CreateExamSessionUseCase $createUseCase,
        private readonly CreateQuickScoreExamSessionUseCase $quickScoreUseCase,
        private readonly GetExamSessionUseCase $getUseCase,
        private readonly UpdateExamSessionUseCase $updateUseCase,
        private readonly DeleteExamSessionUseCase $deleteUseCase,
        private readonly CompleteExamSessionUseCase $completeUseCase,
        private readonly GetExamSubjectStatsUseCase $subjectStatsUseCase,
        private readonly UpdateExamQuestionTimestampUseCase $updateQuestionUseCase,
    ) {}

    public function index(ListExamSessionsRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $sessions = ($this->listUseCase)(
            $user->id,
            $request->validated('status'),
            $request->validated('subject'),
        );

        return response()->json(ExamSessionSummaryResource::collection($sessions));
    }

    public function store(CreateExamSessionRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $session = ($this->createUseCase)(
            $user->id,
            $validated['subject'],
            $validated['examYear'],
        );

        return response()->json(new ExamSessionResource($session), 201);
    }

    public function quickScore(CreateQuickScoreRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $session = ($this->quickScoreUseCase)(
            $user->id,
            $validated['subject'],
            $validated['examYear'],
            $validated['totalScore'],
            $validated['pureScore'] ?? null,
        );

        return response()->json(new ExamSessionSummaryResource($session), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $session = ($this->getUseCase)($id, $user->id);

        if ($session === null) {
            abort(404);
        }

        return response()->json(new ExamSessionResource($session));
    }

    public function update(UpdateExamSessionRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $data = [];

        if (isset($validated['subject'])) {
            $data['subject'] = $validated['subject'];
        }
        if (isset($validated['examYear'])) {
            $data['exam_year'] = $validated['examYear'];
        }
        if (isset($validated['status'])) {
            $data['status'] = $validated['status'];
        }

        $session = ($this->updateUseCase)($id, $user->id, $data);

        return response()->json(new ExamSessionResource($session));
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ($this->deleteUseCase)($id, $user->id);

        return response()->noContent();
    }

    public function complete(CompleteExamSessionRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $session = ($this->completeUseCase)(
            $id,
            $user->id,
            $validated['subject'],
            $validated['examYear'],
            $validated['questions'],
        );

        return response()->json(new ExamSessionResource($session));
    }

    public function updateQuestion(UpdateExamQuestionRequest $request, int $id, int $sortOrder): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $question = ($this->updateQuestionUseCase)(
            $id,
            $user->id,
            $sortOrder,
            $request->validated(),
        );

        return response()->json(new ExamQuestionResource($question));
    }

    public function subjectStats(Request $request, string $subject): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $year  = $request->integer('year') ?: null;
        $month = $request->integer('month') ?: null;
        $stats = ($this->subjectStatsUseCase)($user->id, urldecode($subject), $year, $month);

        return response()->json($stats);
    }
}
