<?php

namespace App\Http\Controllers;

use App\UseCases\Subject\GetAllSubjectSettingsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectSettingsController extends Controller
{
    public function __construct(
        private readonly GetAllSubjectSettingsUseCase $useCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        return response()->json(($this->useCase)($user->id)->values());
    }
}
