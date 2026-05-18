<?php

namespace App\UseCases\StudyTicket;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Models\StudyTicket;

class GetTicketUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): ?StudyTicket
    {
        return $this->repository->findByIdAndUser($id, $userId);
    }
}
