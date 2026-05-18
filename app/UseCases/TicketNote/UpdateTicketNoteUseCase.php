<?php

namespace App\UseCases\TicketNote;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Domain\TicketNote\TicketNoteRepositoryInterface;
use App\Models\TicketNote;

class UpdateTicketNoteUseCase
{
    public function __construct(
        private readonly TicketNoteRepositoryInterface $noteRepository,
        private readonly StudyTicketRepositoryInterface $ticketRepository,
    ) {}

    public function __invoke(int $ticketId, int $noteId, int $userId, string $body): TicketNote
    {
        $ticket = $this->ticketRepository->findByIdAndUser($ticketId, $userId);

        if ($ticket === null) {
            abort(404);
        }

        $note = $this->noteRepository->findByIdAndTicket($noteId, $ticketId);

        if ($note === null) {
            abort(404);
        }

        return $this->noteRepository->update($note, $body);
    }
}
