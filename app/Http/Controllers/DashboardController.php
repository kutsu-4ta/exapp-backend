<?php

namespace App\Http\Controllers;

use App\UseCases\Dashboard\GetDashboardStatsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GetDashboardStatsUseCase $statsUseCase,
    ) {}

    public function stats(Request $request): JsonResponse
    {
        $stats = ($this->statsUseCase)($request->user()->id);

        return response()->json($stats);
    }
}
