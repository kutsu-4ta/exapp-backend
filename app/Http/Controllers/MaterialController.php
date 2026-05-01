<?php

namespace App\Http\Controllers;

use App\UseCases\Material\ListMaterialUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MaterialController extends Controller
{
    public function __construct(
        private readonly ListMaterialUseCase $listUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $subjects = ($this->listUseCase)($user->id);

        return response()->json($subjects->pluck('name')->values());
    }
}
