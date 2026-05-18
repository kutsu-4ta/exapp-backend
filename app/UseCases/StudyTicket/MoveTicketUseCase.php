<?php

namespace App\UseCases\StudyTicket;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Models\StudyTicket;

class MoveTicketUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
        private readonly SprintRepositoryInterface $sprintRepository,
    ) {}

    public function __invoke(int $id, int $userId, int $sprintId): StudyTicket
    {
        $ticket = $this->repository->findByIdAndUser($id, $userId);

        if ($ticket === null) {
            abort(404);
        }

        $sprint = $this->sprintRepository->findByIdAndUser($sprintId, $userId);

        if ($sprint === null) {
            abort(422, '移動先のスプリントが見つかりません。');
        }

        return $this->repository->move($ticket, $sprintId);
    }
}
