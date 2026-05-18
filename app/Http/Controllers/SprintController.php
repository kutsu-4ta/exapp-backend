<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sprint\CompleteSprintRequest;
use App\Http\Requests\Sprint\CreateSprintRequest;
use App\Http\Requests\Sprint\UpdateSprintRequest;
use App\Http\Resources\SprintResource;
use App\UseCases\Sprint\CompleteSprintUseCase;
use App\UseCases\Sprint\CreateSprintUseCase;
use App\UseCases\Sprint\DeleteSprintUseCase;
use App\UseCases\Sprint\GetSprintStatsUseCase;
use App\UseCases\Sprint\GetSprintUseCase;
use App\UseCases\Sprint\ListSprintsUseCase;
use App\UseCases\Sprint\UpdateSprintUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SprintController extends Controller
{
    public function __construct(
        private readonly ListSprintsUseCase $listUseCase,
        private readonly CreateSprintUseCase $createUseCase,
        private readonly GetSprintUseCase $getUseCase,
        private readonly UpdateSprintUseCase $updateUseCase,
        private readonly DeleteSprintUseCase $deleteUseCase,
        private readonly CompleteSprintUseCase $completeUseCase,
        private readonly GetSprintStatsUseCase $statsUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $sprints = ($this->listUseCase)($user->id);

        return response()->json(SprintResource::collection($sprints));
    }

    public function store(CreateSprintRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $validated = $request->validated();
        $sprint = ($this->createUseCase)($user->id, [
            'name'       => $validated['name'],
            'goal'       => $validated['goal'] ?? null,
            'start_date' => $validated['startDate'],
            'end_date'   => $validated['endDate'],
        ]);

        return response()->json(new SprintResource($sprint), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $sprint = ($this->getUseCase)($id, $user->id);
        if ($sprint === null) {
            abort(404);
        }

        return response()->json(new SprintResource($sprint));
    }

    public function update(UpdateSprintRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $validated = $request->validated();
        $data = [];
        if (isset($validated['name']))      $data['name']       = $validated['name'];
        if (array_key_exists('goal', $validated)) $data['goal'] = $validated['goal']; // null クリアも許容
        if (isset($validated['startDate'])) $data['start_date'] = $validated['startDate'];
        if (isset($validated['endDate']))   $data['end_date']   = $validated['endDate'];

        $sprint = ($this->updateUseCase)($id, $user->id, $data);

        return response()->json(new SprintResource($sprint));
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        ($this->deleteUseCase)($id, $user->id);

        return response()->noContent();
    }

    public function complete(CompleteSprintRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $sprint = ($this->completeUseCase)($id, $user->id, $request->validated('retrospective'));

        return response()->json(new SprintResource($sprint));
    }

    public function stats(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $stats = ($this->statsUseCase)($id, $user->id);

        return response()->json($stats);
    }
}
