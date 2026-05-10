<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlashcardResource;
use App\UseCases\Problem\ListAllFlashcardsUseCase;
use App\UseCases\Problem\ListFlashcardsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashcardController extends Controller
{
    public function __construct(
        private readonly ListFlashcardsUseCase $useCase,
        private readonly ListAllFlashcardsUseCase $allUseCase,
    ) {}

    public function indexAll(Request $request): JsonResponse
    {
        $user        = $request->user() ?? auth('sanctum')->user();
        $subjectName = $request->query('subject') ? urldecode($request->query('subject')) : null;
        $count       = $request->integer('count', 10);

        $cards = ($this->allUseCase)($user->id, $subjectName, $count);

        return response()->json(FlashcardResource::collection($cards));
    }

    public function index(Request $request, string $subject): JsonResponse
    {
        $user    = $request->user() ?? auth('sanctum')->user();
        $limit   = $request->integer('limit') ?: null;
        $afterId = $request->integer('after') ?: null;

        $cards = ($this->useCase)($user->id, urldecode($subject), $limit, $afterId);

        return response()->json(FlashcardResource::collection($cards));
    }
}
