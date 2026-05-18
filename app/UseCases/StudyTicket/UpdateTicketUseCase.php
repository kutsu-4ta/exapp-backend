<?php

namespace App\UseCases\StudyTicket;

use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\StudyTicket;

class UpdateTicketUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $id, int $userId, array $data, ?array $subCategoryIds = null): StudyTicket
    {
        $ticket = $this->repository->findByIdAndUser($id, $userId);

        if ($ticket === null) {
            abort(404);
        }

        if (isset($data['subject'])) {
            $subject           = $this->subjectRepository->firstOrCreate($userId, $data['subject']);
            $data['subject_id'] = $subject->id;
        }

        return $this->repository->update($ticket, $data, $subCategoryIds);
    }
}
