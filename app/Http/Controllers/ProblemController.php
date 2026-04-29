<?php

namespace App\Http\Controllers;

use App\Http\Requests\Problem\CreateProblemRequest;
use App\Http\Requests\Problem\UpdateProblemRequest;
use App\Http\Resources\ProblemResource;
use App\UseCases\Problem\CreateProblemUseCase;
use App\UseCases\Problem\DeleteProblemUseCase;
use App\UseCases\Problem\ListProblemsUseCase;
use App\UseCases\Problem\UpdateProblemUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProblemController extends Controller
{
    public function __construct(
        private readonly ListProblemsUseCase $listUseCase,
        private readonly CreateProblemUseCase $createUseCase,
        private readonly UpdateProblemUseCase $updateUseCase,
        private readonly DeleteProblemUseCase $deleteUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Requestインスタンス、または明示的なsanctumガードからユーザーを取得
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $problems = ($this->listUseCase)($user->id);

        return response()->json(ProblemResource::collection($problems));
    }

    public function store(CreateProblemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $problem = ($this->createUseCase)(
            $request->user()->id,
            [
                'subject_id' => $validated['subjectId'],
                'material' => $validated['material'],
                'question_ref' => $validated['questionRef'],
                'note' => $validated['note'] ?? null,
                'proficiency' => $validated['proficiency'],
                'failure_types' => $validated['failureTypes'],
                'is_good_question' => $validated['isGoodQuestion'],
                'solved_at' => $validated['solvedAt'],
            ],
        );

        return response()->json(new ProblemResource($problem), 201);
    }

    public function update(UpdateProblemRequest $request, int $id): JsonResponse
    {
        // Requestインスタンス、または明示的なsanctumガードからユーザーを取得
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $problem = ($this->updateUseCase)(
            $user->id,
            $id,
            [
                'subject_id' => $validated['subjectId'],
                'material' => $validated['material'],
                'question_ref' => $validated['questionRef'],
                'note' => $validated['note'] ?? null,
                'proficiency' => $validated['proficiency'],
                'failure_types' => $validated['failureTypes'],
                'is_good_question' => $validated['isGoodQuestion'],
                'solved_at' => $validated['solvedAt'],
            ],
        );

        return response()->json(new ProblemResource($problem));
    }

    public function destroy(Request $request, int $id): Response
    {
        // Requestインスタンス、または明示的なsanctumガードからユーザーを取得
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ($this->deleteUseCase)($user->id, $id);

        return response()->noContent();
    }
}
