<?php

namespace App\Http\Controllers;

use App\Http\Requests\Analysis\AnalyzeProblemRequest;
use App\Http\Resources\ProblemResource;
use App\UseCases\Analysis\AnalyzeProblemImageUseCase;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    public function __construct(
        private readonly AnalyzeProblemImageUseCase $useCase,
    ) {}

    public function analyzeProblem(AnalyzeProblemRequest $request): JsonResponse
    {
        $user    = $request->user() ?? auth('sanctum')->user();
        $analysisResult = ($this->useCase)($user->id, $request->file('image'));

        return response()->json($analysisResult, 200);
    }
}
