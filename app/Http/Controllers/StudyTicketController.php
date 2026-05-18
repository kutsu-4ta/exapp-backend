<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudyTicket\CreateTicketRequest;
use App\Http\Requests\StudyTicket\ListTicketsRequest;
use App\Http\Requests\StudyTicket\MoveTicketRequest;
use App\Http\Requests\StudyTicket\UpdateTicketRequest;
use App\Http\Resources\StudyTicketResource;
use App\UseCases\StudyTicket\CompleteTicketUseCase;
use App\UseCases\StudyTicket\CreateTicketUseCase;
use App\UseCases\StudyTicket\DeleteTicketUseCase;
use App\UseCases\StudyTicket\GetTicketUseCase;
use App\UseCases\StudyTicket\ListTicketsUseCase;
use App\UseCases\StudyTicket\MoveTicketUseCase;
use App\UseCases\StudyTicket\ReopenTicketUseCase;
use App\UseCases\StudyTicket\UpdateTicketUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudyTicketController extends Controller
{
    public function __construct(
        private readonly ListTicketsUseCase $listUseCase,
        private readonly CreateTicketUseCase $createUseCase,
        private readonly GetTicketUseCase $getUseCase,
        private readonly UpdateTicketUseCase $updateUseCase,
        private readonly DeleteTicketUseCase $deleteUseCase,
        private readonly CompleteTicketUseCase $completeUseCase,
        private readonly ReopenTicketUseCase $reopenUseCase,
        private readonly MoveTicketUseCase $moveUseCase,
    ) {}

    public function index(ListTicketsRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $tickets = ($this->listUseCase)(
            $user->id,
            $request->integer('sprintId') ?: null,
            $request->validated('status'),
        );

        return response()->json(StudyTicketResource::collection($tickets));
    }

    public function store(CreateTicketRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $validated = $request->validated();
        $ticket = ($this->createUseCase)(
            $user->id,
            [
                'sprint_id'           => $validated['sprintId'] ?? null,
                'subject'             => $validated['subject'],
                'title'               => $validated['title'],
                'acceptance_criteria' => $validated['acceptanceCriteria'],
                'due_date'            => $validated['dueDate'],
                'priority'            => $validated['priority'],
                'ticket_type'         => $validated['ticketType'],
                'source'              => $validated['source'],
                'estimate_minutes'    => $validated['estimateMinutes'] ?? null,
            ],
            $validated['subCategoryIds'],
        );

        return response()->json(new StudyTicketResource($ticket), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $ticket = ($this->getUseCase)($id, $user->id);
        if ($ticket === null) {
            abort(404);
        }

        return response()->json(new StudyTicketResource($ticket));
    }

    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $validated = $request->validated();
        $data = [];
        if (array_key_exists('subject', $validated))            $data['subject']             = $validated['subject'];
        if (array_key_exists('title', $validated))              $data['title']               = $validated['title'];
        if (array_key_exists('acceptanceCriteria', $validated)) $data['acceptance_criteria'] = $validated['acceptanceCriteria'];
        if (array_key_exists('dueDate', $validated))            $data['due_date']            = $validated['dueDate'];
        if (array_key_exists('status', $validated))             $data['status']              = $validated['status'];
        if (array_key_exists('priority', $validated))           $data['priority']            = $validated['priority'];
        if (array_key_exists('ticketType', $validated))         $data['ticket_type']         = $validated['ticketType'];
        if (array_key_exists('source', $validated))             $data['source']              = $validated['source'];
        if (array_key_exists('estimateMinutes', $validated))    $data['estimate_minutes']    = $validated['estimateMinutes'];

        $subCategoryIds = $validated['subCategoryIds'] ?? null;

        $ticket = ($this->updateUseCase)($id, $user->id, $data, $subCategoryIds);

        return response()->json(new StudyTicketResource($ticket));
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        ($this->deleteUseCase)($id, $user->id);

        return response()->noContent();
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $ticket = ($this->completeUseCase)($id, $user->id);

        return response()->json(new StudyTicketResource($ticket));
    }

    public function reopen(Request $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $ticket = ($this->reopenUseCase)($id, $user->id);

        return response()->json(new StudyTicketResource($ticket));
    }

    public function move(MoveTicketRequest $request, int $id): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        if (!$user) {
            throw new AuthenticationException();
        }

        $ticket = ($this->moveUseCase)($id, $user->id, $request->validated('sprintId'));

        return response()->json(new StudyTicketResource($ticket));
    }
}
