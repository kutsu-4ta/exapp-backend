<?php

namespace App\Http\Controllers;

use App\UseCases\Stopwatch\GetStopwatchUseCase;
use App\UseCases\Stopwatch\StartStopwatchUseCase;
use App\UseCases\Stopwatch\StopStopwatchUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StopwatchController extends Controller
{
    public function __construct(
        private readonly GetStopwatchUseCase   $getUseCase,
        private readonly StartStopwatchUseCase $startUseCase,
        private readonly StopStopwatchUseCase  $stopUseCase,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user   = $request->user() ?? auth('sanctum')->user();
        $result = ($this->getUseCase)($user->id);

        return response()->json($result);
    }

    public function start(Request $request): JsonResponse
    {
        $user   = $request->user() ?? auth('sanctum')->user();
        $result = ($this->startUseCase)($user->id);

        return response()->json($result);
    }

    public function stop(Request $request): JsonResponse
    {
        $user   = $request->user() ?? auth('sanctum')->user();
        $result = ($this->stopUseCase)($user->id);

        return response()->json($result);
    }
}
