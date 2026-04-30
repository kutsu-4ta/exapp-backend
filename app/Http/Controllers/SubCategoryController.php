<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubCategory\CreateSubCategoryRequest;
use App\Http\Requests\SubCategory\ListSubCategoriesRequest;
use App\Http\Requests\SubCategory\UpdateSubCategoryRequest;
use App\Http\Resources\SubCategoryResource;
use App\UseCases\SubCategory\CreateSubCategoryUseCase;
use App\UseCases\SubCategory\DeleteSubCategoryUseCase;
use App\UseCases\SubCategory\ListSubCategoriesUseCase;
use App\UseCases\SubCategory\UpdateSubCategoryUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubCategoryController extends Controller
{
    public function __construct(
        private readonly ListSubCategoriesUseCase $listUseCase,
        private readonly CreateSubCategoryUseCase $createUseCase,
        private readonly UpdateSubCategoryUseCase $updateUseCase,
        private readonly DeleteSubCategoryUseCase $deleteUseCase,
    ) {}

    public function index(ListSubCategoriesRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $subCategories = ($this->listUseCase)($user->id, $request->validated('subject'));

        return response()->json(SubCategoryResource::collection($subCategories));
    }

    public function store(CreateSubCategoryRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $subCategory = ($this->createUseCase)($user->id, [
            'subject' => $validated['subject'],
            'name' => $validated['name'],
        ]);

        return response()->json(new SubCategoryResource($subCategory), 201);
    }

    public function update(UpdateSubCategoryRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $validated = $request->validated();
        $subCategory = ($this->updateUseCase)($user->id, $id, [
            'subject' => $validated['subject'],
            'name' => $validated['name'],
        ]);

        return response()->json(new SubCategoryResource($subCategory));
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
