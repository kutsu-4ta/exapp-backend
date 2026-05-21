<?php

namespace App\Http\Controllers;

use App\UseCases\Subject\GetSubjectCountsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectCountsController extends Controller
{
    public function __construct(
        private readonly GetSubjectCountsUseCase $useCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        return response()->json(($this->useCase)($user->id)->values());
    }
}
