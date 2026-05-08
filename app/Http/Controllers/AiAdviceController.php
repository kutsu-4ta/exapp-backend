<?php

namespace App\Http\Controllers;

use App\Enums\AiAdviceMode;
use App\UseCases\AiAdvice\GetAiAdviceUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiAdviceController extends Controller
{
    public function __construct(
        private readonly GetAiAdviceUseCase $useCase,
    ) {}

    public function advice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', Rule::enum(AiAdviceMode::class)],
        ]);

        $user = $request->user() ?? auth('sanctum')->user();
        $mode = AiAdviceMode::from($validated['mode']);

        try {
            $advice = ($this->useCase)($user->id, $mode);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'FAILED_PRECONDITION')) {
                return response()->json(['message' => 'AI機能はこのサーバーからご利用いただけません。'], 503);
            }
            throw $e;
        }

        return response()->json(['advice' => $advice]);
    }
}
