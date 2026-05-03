<?php

namespace App\Http\Controllers;

use App\Http\Requests\Practice\CreatePracticeSessionRequest;
use App\UseCases\Practice\CreatePracticeSessionUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class PracticeController extends Controller
{
    public function __construct(
        private readonly CreatePracticeSessionUseCase $createUseCase,
    ) {}

    public function store(CreatePracticeSessionRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $session = ($this->createUseCase)($user->id, $request->validated());

        return response()->json([
            'id'           => $session->id,
            'subject'      => $session->subject->name,
            'date'         => $session->date->toDateString(),
            'totalMinutes' => $session->total_minutes,
        ], 201);
    }
}
