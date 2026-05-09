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
        $user = $request->user() ?? auth('sanctum')->user();

        try {
            $analysisResult = ($this->useCase)(
                $user->id,
                $request->file('image'),
                $request->input('memo'),
            );
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'RESOURCE_EXHAUSTED')) {
                return response()->json(['message' => 'APIの利用上限に達しました。しばらく待ってから再試行してください。'], 429);
            }
            throw $e;
        }

        return response()->json($analysisResult, 200);
    }
}
