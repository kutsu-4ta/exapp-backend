<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyLog\CreateDailyLogRequest;
use App\Http\Requests\DailyLog\ListDailyLogsRequest;
use App\Http\Requests\DailyLog\UpdateReflectionRequest;
use App\Http\Resources\DailyLogResource;
use App\Http\Resources\DailyLogSummaryResource;
use App\UseCases\DailyLog\CompleteDailyLogUseCase;
use App\UseCases\DailyLog\CreateDailyLogUseCase;
use App\UseCases\DailyLog\GetDailyLogUseCase;
use App\UseCases\DailyLog\ListDailyLogsUseCase;
use App\UseCases\DailyLog\UncompleteDailyLogUseCase;
use App\UseCases\DailyLog\UpdateReflectionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function __construct(
        private readonly ListDailyLogsUseCase $listUseCase,
        private readonly CreateDailyLogUseCase $createUseCase,
        private readonly GetDailyLogUseCase $getUseCase,
        private readonly UpdateReflectionUseCase $updateReflectionUseCase,
        private readonly CompleteDailyLogUseCase $completeUseCase,
        private readonly UncompleteDailyLogUseCase $uncompleteUseCase,
    ) {}

    public function index(ListDailyLogsRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $logs = ($this->listUseCase)(
            $user->id,
            (int) $request->validated('year'),
            (int) $request->validated('month'),
        );

        return response()->json(DailyLogSummaryResource::collection($logs));
    }

    public function store(CreateDailyLogRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $log = ($this->createUseCase)($user->id, $request->validated('date'));

        return response()->json(new DailyLogResource($log), 201);
    }

    public function show(Request $request, string $date): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $log = ($this->getUseCase)($user->id, $date);

        if ($log === null) {
            abort(404);
        }

        return response()->json(new DailyLogResource($log));
    }

    public function update(UpdateReflectionRequest $request, string $date): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $log = ($this->updateReflectionUseCase)(
            $user->id,
            $date,
            $request->validated('reflection'),
        );

        return response()->json(new DailyLogResource($log));
    }

    public function complete(Request $request, string $date): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $log = ($this->completeUseCase)($user->id, $date);

        return response()->json(new DailyLogResource($log));
    }

    public function uncomplete(Request $request, string $date): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $log = ($this->uncompleteUseCase)($user->id, $date);

        return response()->json(new DailyLogResource($log));
    }
}
