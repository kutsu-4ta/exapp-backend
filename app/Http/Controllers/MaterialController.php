<?php

namespace App\Http\Controllers;

use App\UseCases\Material\DeleteMaterialUseCase;
use App\UseCases\Material\ListMaterialUseCase;
use App\UseCases\Material\RenameMaterialUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MaterialController extends Controller
{
    public function __construct(
        private readonly ListMaterialUseCase $listUseCase,
        private readonly RenameMaterialUseCase $renameUseCase,
        private readonly DeleteMaterialUseCase $deleteUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $materials = ($this->listUseCase)($user->id);

        return response()->json($materials->pluck('name')->values());
    }

    public function update(Request $request, string $name): JsonResponse
    {
        $request->validate(['newName' => ['required', 'string', 'max:255']]);

        $user = $request->user();
        $material = ($this->renameUseCase)($user->id, urldecode($name), $request->input('newName'));

        return response()->json(['name' => $material->name]);
    }

    public function destroy(Request $request, string $name): Response
    {
        $user = $request->user();
        ($this->deleteUseCase)($user->id, urldecode($name));

        return response()->noContent();
    }
}
