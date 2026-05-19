<?php

namespace App\Http\Controllers;

use App\Http\Requests\Snippet\CreateSnippetRequest;
use App\Http\Requests\Snippet\UpdateSnippetRequest;
use App\Http\Resources\SnippetResource;
use App\UseCases\Snippet\CreateSnippetUseCase;
use App\UseCases\Snippet\DeleteSnippetUseCase;
use App\UseCases\Snippet\ListSnippetsUseCase;
use App\UseCases\Snippet\UpdateSnippetUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SnippetController extends Controller
{
    public function __construct(
        private readonly ListSnippetsUseCase $listUseCase,
        private readonly CreateSnippetUseCase $createUseCase,
        private readonly UpdateSnippetUseCase $updateUseCase,
        private readonly DeleteSnippetUseCase $deleteUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $snippets = ($this->listUseCase)($user->id);

        return response()->json(SnippetResource::collection($snippets));
    }

    public function store(CreateSnippetRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $snippet = ($this->createUseCase)($user->id, $request->validated());

        return response()->json(new SnippetResource($snippet), 201);
    }

    public function update(UpdateSnippetRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $snippet = ($this->updateUseCase)($user->id, $id, $request->validated());

        return response()->json(new SnippetResource($snippet));
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ($this->deleteUseCase)($user->id, $id);

        return response()->noContent();
    }
}
