<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudySession\CreateStudySessionRequest;
use App\Http\Requests\StudySession\UpdateStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\UseCases\StudySession\CreateStudySessionUseCase;
use App\UseCases\StudySession\DeleteStudySessionUseCase;
use App\UseCases\StudySession\UpdateStudySessionUseCase;
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
        $validated = $request->validated();
        $session = ($this->createUseCase)(
            $request->user()->id,
            $validated['dailyLogDate'],
            [
                'subject_id' => $validated['subjectId'],
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
        $validated = $request->validated();
        $session = ($this->updateUseCase)(
            $request->user()->id,
            $id,
            [
                'subject_id' => $validated['subjectId'],
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
        ($this->deleteUseCase)($request->user()->id, $id);

        return response()->noContent();
    }
}
