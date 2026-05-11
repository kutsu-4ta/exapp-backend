<?php

namespace App\Http\Controllers;

use App\UseCases\GeminiContext\GetGeminiContextUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeminiContextController extends Controller
{
    public function __construct(
        private readonly GetGeminiContextUseCase $useCase,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $year  = $request->integer('year')  ?: null;
        $month = $request->integer('month') ?: null;

        $context = ($this->useCase)($user->id, $year, $month);

        return response()->json($context);
    }
}
