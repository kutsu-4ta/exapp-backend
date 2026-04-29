<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudySession\CreateStudySessionRequest;
use App\Http\Requests\StudySession\UpdateStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\UseCases\StudySession\CreateStudySessionUseCase;
use App\UseCases\StudySession\DeleteStudySessionUseCase;
use App\UseCases\StudySession\UpdateStudySessionUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudySessionController extends Controller
{
    public function __construct(
        private readonly CreateStudySessionUseCase $createUseCase,
        private readonly UpdateStudySessionUseCase $updateUseCase,
        private readonly DeleteStudySessionUseCase $deleteUseCase,
    ) {}

    public function store(CreateStudySessionRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $session = ($this->createUseCase)(
            $user->id,
            $validated['dailyLogDate'],
            [
                'subject' => $validated['subject'],
                'sub_category_id' => $validated['subCategoryId'] ?? null,
                'time_slot' => $validated['timeSlot'],
                'minutes' => $validated['minutes'],
                'material' => $validated['material'],
                'memo' => $validated['memo'] ?? null,
            ],
        );

        return response()->json(new StudySessionResource($session), 201);
    }

    public function update(UpdateStudySessionRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $session = ($this->updateUseCase)(
            $user->id,
            $id,
            [
                'subject' => $validated['subject'],
                'sub_category_id' => $validated['subCategoryId'] ?? null,
                'time_slot' => $validated['timeSlot'],
                'minutes' => $validated['minutes'],
                'material' => $validated['material'],
                'memo' => $validated['memo'] ?? null,
            ],
        );

        return response()->json(new StudySessionResource($session));
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
