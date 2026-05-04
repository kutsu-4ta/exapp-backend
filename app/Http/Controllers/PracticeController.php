<?php

namespace App\Http\Controllers;

use App\Http\Requests\Practice\CreatePracticeSessionRequest;
use App\Http\Requests\Practice\SavePracticeSessionDraftRequest;
use App\UseCases\Practice\CreatePracticeSessionUseCase;
use App\UseCases\Practice\DeletePracticeSessionDraftUseCase;
use App\UseCases\Practice\GetPracticeSessionDraftUseCase;
use App\UseCases\Practice\SavePracticeSessionDraftUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PracticeController extends Controller
{
    public function __construct(
        private readonly CreatePracticeSessionUseCase    $createUseCase,
        private readonly SavePracticeSessionDraftUseCase $saveDraftUseCase,
        private readonly GetPracticeSessionDraftUseCase  $getDraftUseCase,
        private readonly DeletePracticeSessionDraftUseCase $deleteDraftUseCase,
    ) {}

    public function store(CreatePracticeSessionRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $session = ($this->createUseCase)($user->id, $request->validated());

        return response()->json([
            'id'           => $session->id,
            'subject'      => $session->subject->name,
            'date'         => $session->date->toDateString(),
            'totalMinutes' => $session->total_minutes,
        ], 201);
    }

    public function storeDraft(SavePracticeSessionDraftRequest $request): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ($this->saveDraftUseCase)($user->id, $request->validated());

        return response()->noContent();
    }

    public function showDraft(Request $request, string $subject): JsonResponse|Response
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $draft = ($this->getDraftUseCase)($user->id, urldecode($subject));

        if ($draft === null) {
            return response()->noContent(404);
        }

        return response()->json([
            'subject'      => $draft->subject->name,
            'currentIndex' => $draft->current_index,
            'log'          => $draft->log,
        ]);
    }

    public function destroyDraft(Request $request, string $subject): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        ($this->deleteDraftUseCase)($user->id, urldecode($subject));

        return response()->noContent();
    }
}
