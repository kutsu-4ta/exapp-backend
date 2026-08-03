<?php

namespace App\Http\Controllers;

use App\UseCases\Subject\DeleteSubjectUseCase;
use App\UseCases\Subject\ListAllSubjectsUseCase;
use App\UseCases\Subject\ListSubjectsUseCase;
use App\UseCases\Subject\RenameSubjectUseCase;
use App\UseCases\Subject\SetSubjectHiddenUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubjectController extends Controller
{
    public function __construct(
        private readonly ListSubjectsUseCase $listUseCase,
        private readonly ListAllSubjectsUseCase $listAllUseCase,
        private readonly RenameSubjectUseCase $renameUseCase,
        private readonly SetSubjectHiddenUseCase $setHiddenUseCase,
        private readonly DeleteSubjectUseCase $deleteUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $subjects = ($this->listUseCase)($user->id);

        return response()->json($subjects->pluck('name')->values());
    }

    public function indexAll(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $subjects = ($this->listAllUseCase)($user->id);

        return response()->json($subjects->map(fn ($s) => [
            'name'     => $s->name,
            'isHidden' => $s->is_hidden,
        ])->values());
    }

    public function updateHidden(Request $request, string $name): JsonResponse
    {
        $request->validate(['hidden' => ['required', 'boolean']]);

        $user = $request->user() ?? auth('sanctum')->user();
        $subject = ($this->setHiddenUseCase)($user->id, urldecode($name), $request->boolean('hidden'));

        return response()->json(['name' => $subject->name, 'isHidden' => $subject->is_hidden]);
    }

    public function update(Request $request, string $name): JsonResponse
    {
        $request->validate(['newName' => ['required', 'string', 'max:255']]);

        $user = $request->user() ?? auth('sanctum')->user();
        $subject = ($this->renameUseCase)($user->id, urldecode($name), $request->input('newName'));

        return response()->json(['name' => $subject->name]);
    }

    public function destroy(Request $request, string $name): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();
        ($this->deleteUseCase)($user->id, urldecode($name));

        return response()->noContent();
    }
}
