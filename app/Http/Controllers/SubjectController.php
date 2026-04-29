<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subject\CreateSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\UseCases\Subject\CreateSubjectUseCase;
use App\UseCases\Subject\DeleteSubjectUseCase;
use App\UseCases\Subject\ListSubjectsUseCase;
use App\UseCases\Subject\UpdateSubjectUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectController extends Controller
{
    public function __construct(
        private readonly ListSubjectsUseCase $listUseCase,
        private readonly CreateSubjectUseCase $createUseCase,
        private readonly UpdateSubjectUseCase $updateUseCase,
        private readonly DeleteSubjectUseCase $deleteUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }


        $subjects = ($this->listUseCase)($user->id);

        return response()->json(SubjectResource::collection($subjects));
    }

    public function store(CreateSubjectRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $subject = ($this->createUseCase)(
            $request->user()->id,
            $validated['name'],
            $validated['displayOrder'] ?? 0,
        );

        return response()->json(new SubjectResource($subject), 201);
    }

    public function update(UpdateSubjectRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $subject = ($this->updateUseCase)(
            $user->id,
            $id,
            $validated['name'],
            $validated['displayOrder'] ?? 0,
        );

        return response()->json(new SubjectResource($subject));
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
