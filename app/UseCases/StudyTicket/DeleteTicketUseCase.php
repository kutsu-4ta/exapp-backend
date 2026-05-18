<?php

namespace App\UseCases\StudyTicket;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;

class DeleteTicketUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): void
    {
        $ticket = $this->repository->findByIdAndUser($id, $userId);

        if ($ticket === null) {
            abort(404);
        }

        $this->repository->delete($ticket);
    }
}
