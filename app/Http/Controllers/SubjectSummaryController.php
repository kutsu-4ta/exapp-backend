<?php

namespace App\Http\Controllers;

use App\UseCases\Subject\GetSubjectsSummaryUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectSummaryController extends Controller
{
    public function __construct(
        private readonly GetSubjectsSummaryUseCase $useCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $year  = $request->integer('year')  ?: null;
        $month = $request->integer('month') ?: null;

        return response()->json(($this->useCase)($user->id, $year, $month));
    }
}
