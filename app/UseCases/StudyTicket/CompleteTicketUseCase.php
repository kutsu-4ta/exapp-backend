<?php

namespace App\UseCases\StudyTicket;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Enums\TicketStatus;
use App\Models\StudyTicket;

class CompleteTicketUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): StudyTicket
    {
        $ticket = $this->repository->findByIdAndUser($id, $userId);

        if ($ticket === null) {
            abort(404);
        }

        if ($ticket->status === TicketStatus::Done) {
            abort(422, 'すでに完了しているチケットです。');
        }

        return $this->repository->complete($ticket);
    }
}
