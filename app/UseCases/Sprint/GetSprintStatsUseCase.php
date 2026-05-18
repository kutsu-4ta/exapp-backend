<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Enums\TicketStatus;

class GetSprintStatsUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $sprintRepository,
        private readonly StudyTicketRepositoryInterface $ticketRepository,
    ) {}

    public function __invoke(int $id, int $userId): array
    {
        $sprint = $this->sprintRepository->findByIdAndUser($id, $userId);

        if ($sprint === null) {
            abort(404);
        }

        $tickets = $this->ticketRepository->findAllByUser($userId, $id);
        $total   = $tickets->count();
        $done    = $tickets->where('status', TicketStatus::Done)->count();
        $doing   = $tickets->where('status', TicketStatus::Doing)->count();
        $todo    = $tickets->where('status', TicketStatus::Todo)->count();

        $completedTickets = $tickets->filter(fn ($t) => $t->completed_at !== null && $t->created_at !== null);
        $avgDays = $completedTickets->isNotEmpty()
            ? round($completedTickets->avg(fn ($t) => $t->created_at->diffInDays($t->completed_at)), 1)
            : null;

        $subCategoryStats = $this->ticketRepository->statsBySubCategory($userId, $id);

        return [
            'sprintId'        => $sprint->id,
            'sprintName'      => $sprint->name,
            'total'           => $total,
            'done'            => $done,
            'doing'           => $doing,
            'todo'            => $todo,
            'completionRate'  => $total > 0 ? round($done / $total * 100, 1) : 0.0,
            'avgCompleteDays' => $avgDays,
            'bySubCategory'   => $subCategoryStats,
        ];
    }
}
