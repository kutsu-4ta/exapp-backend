<?php

namespace App\Http\Controllers;

use App\Http\Requests\Problem\CreateProblemRequest;
use App\Http\Requests\Problem\UpdateProblemRequest;
use App\Http\Resources\ProblemResource;
use App\Http\Resources\FlashcardResource;
use App\UseCases\Problem\CreateProblemUseCase;
use App\UseCases\Problem\DeleteProblemUseCase;
use App\UseCases\Problem\GetProblemUseCase;
use App\UseCases\Problem\ListProblemsUseCase;
use App\UseCases\Problem\TouchProblemUseCase;
use App\UseCases\Problem\UpdateProblemUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProblemController extends Controller
{
    public function __construct(
        private readonly ListProblemsUseCase   $listUseCase,
        private readonly GetProblemUseCase     $getUseCase,
        private readonly CreateProblemUseCase  $createUseCase,
        private readonly UpdateProblemUseCase  $updateUseCase,
        private readonly DeleteProblemUseCase  $deleteUseCase,
        private readonly TouchProblemUseCase   $touchUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $limit    = $request->integer('limit') ?: null;
        $q        = $request->string('q')->trim()->toString() ?: null;
        $subjects = $request->filled('subjects')
            ? array_filter(array_map('trim', explode(',', $request->input('subjects'))))
            : [];

        $problems = ($this->listUseCase)($user->id, $limit, $q, $subjects);

        return response()->json(ProblemResource::collection($problems));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $problem = ($this->getUseCase)($user->id, $id);

        return response()->json(new ProblemResource($problem));
    }

    public function store(CreateProblemRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $problem = ($this->createUseCase)(
            $user->id,
            [
                'subject'          => $validated['subject'],
                'sub_category'     => $validated['subCategory']  ?? null,
                'material_id'      => $validated['materialId']   ?? null,
                'material_name'    => $validated['materialName'] ?? null,
                'question_ref'     => $validated['questionRef'],
                'note'             => $validated['note']          ?? null,
                'proficiency'      => $validated['proficiency'],
                'failure_types'    => $validated['failureTypes'],
                'is_good_question' => $validated['isGoodQuestion'],
                'solved_at'        => $validated['solvedAt'],
            ],
        );

        return response()->json(new ProblemResource($problem), 201);
    }

    public function update(UpdateProblemRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $problem = ($this->updateUseCase)(
            $user->id,
            $id,
            [
                'subject'          => $validated['subject'],
                'sub_category'     => $validated['subCategory']  ?? null,
                'material_id'      => $validated['materialId']   ?? null,
                'material_name'    => $validated['materialName'] ?? null,
                'question_ref'     => $validated['questionRef'],
                'note'             => $validated['note']          ?? null,
                'proficiency'      => $validated['proficiency'],
                'failure_types'    => $validated['failureTypes'],
                'is_good_question' => $validated['isGoodQuestion'],
                'solved_at'        => $validated['solvedAt'],
            ],
        );

        return response()->json(new ProblemResource($problem));
    }

    public function touch(Request $request, int $id): JsonResponse
    {
        $user    = $request->user() ?? auth('sanctum')->user();
        $problem = ($this->touchUseCase)($user->id, $id);

        return response()->json(new FlashcardResource($problem));
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ($this->deleteUseCase)($user->id, $id);

        return response()->noContent();
    }
}
