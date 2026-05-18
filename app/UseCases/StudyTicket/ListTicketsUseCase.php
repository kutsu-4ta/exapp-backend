<?php

namespace App\UseCases\StudyTicket;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use Illuminate\Support\Collection;

class ListTicketsUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, ?int $sprintId = null, ?string $status = null): Collection
    {
        return $this->repository->findAllByUser($userId, $sprintId, $status);
    }
}
