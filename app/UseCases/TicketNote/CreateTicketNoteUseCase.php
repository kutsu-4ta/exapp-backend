<?php

namespace App\UseCases\TicketNote;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Domain\TicketNote\TicketNoteRepositoryInterface;
use App\Models\TicketNote;

class CreateTicketNoteUseCase
{
    public function __construct(
        private readonly TicketNoteRepositoryInterface $noteRepository,
        private readonly StudyTicketRepositoryInterface $ticketRepository,
    ) {}

    public function __invoke(int $ticketId, int $userId, string $body): TicketNote
    {
        $ticket = $this->ticketRepository->findByIdAndUser($ticketId, $userId);

        if ($ticket === null) {
            abort(404);
        }

        return $this->noteRepository->create($ticketId, $userId, $body);
    }
}
