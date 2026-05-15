<?php

namespace App\Http\Controllers;

use App\UseCases\Subject\GetSubjectAlertStatusUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectAlertStatusController extends Controller
{
    public function __construct(
        private readonly GetSubjectAlertStatusUseCase $useCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        return response()->json(($this->useCase)($user->id));
    }
}
