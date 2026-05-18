<?php

namespace App\UseCases\TicketNote;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Domain\TicketNote\TicketNoteRepositoryInterface;
use Illuminate\Support\Collection;

class ListTicketNotesUseCase
{
    public function __construct(
        private readonly TicketNoteRepositoryInterface $noteRepository,
        private readonly StudyTicketRepositoryInterface $ticketRepository,
    ) {}

    public function __invoke(int $ticketId, int $userId): Collection
    {
        $ticket = $this->ticketRepository->findByIdAndUser($ticketId, $userId);

        if ($ticket === null) {
            abort(404);
        }

        return $this->noteRepository->findAllByTicket($ticketId);
    }
}
