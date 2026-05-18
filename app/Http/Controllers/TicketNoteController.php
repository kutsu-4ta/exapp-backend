<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketNote\TicketNoteRequest;
use App\Http\Resources\TicketNoteResource;
use App\UseCases\TicketNote\CreateTicketNoteUseCase;
use App\UseCases\TicketNote\DeleteTicketNoteUseCase;
use App\UseCases\TicketNote\ListTicketNotesUseCase;
use App\UseCases\TicketNote\UpdateTicketNoteUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketNoteController extends Controller
{
    public function __construct(
        private readonly ListTicketNotesUseCase $listUseCase,
        private readonly CreateTicketNoteUseCase $createUseCase,
        private readonly UpdateTicketNoteUseCase $updateUseCase,
        private readonly DeleteTicketNoteUseCase $deleteUseCase,
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $notes = ($this->listUseCase)($id, $user->id);

        return response()->json(TicketNoteResource::collection($notes));
    }

    public function store(TicketNoteRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $note = ($this->createUseCase)($id, $user->id, $request->validated('body'));

        return response()->json(new TicketNoteResource($note), 201);
    }

    public function update(TicketNoteRequest $request, int $id, int $noteId): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $note = ($this->updateUseCase)($id, $noteId, $user->id, $request->validated('body'));

        return response()->json(new TicketNoteResource($note));
    }

    public function destroy(Request $request, int $id, int $noteId): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        ($this->deleteUseCase)($id, $noteId, $user->id);

        return response()->noContent();
    }
}
