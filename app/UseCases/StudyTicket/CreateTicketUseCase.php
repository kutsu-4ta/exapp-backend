<?php

namespace App\UseCases\StudyTicket;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\StudyTicket;

class CreateTicketUseCase
{
    public function __construct(
        private readonly StudyTicketRepositoryInterface $repository,
        private readonly SprintRepositoryInterface $sprintRepository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, array $data, array $subCategoryIds): StudyTicket
    {
        // sprint_id が未指定の場合はバックログに入れる
        if (!isset($data['sprint_id'])) {
            $backlog           = $this->sprintRepository->findOrCreateBacklog($userId);
            $data['sprint_id'] = $backlog->id;
        }

        $sprint = $this->sprintRepository->findByIdAndUser($data['sprint_id'], $userId);
        if ($sprint === null) {
            abort(422, '指定されたスプリントが見つかりません。');
        }

        if (isset($data['subject'])) {
            $subject           = $this->subjectRepository->firstOrCreate($userId, $data['subject']);
            $data['subject_id'] = $subject->id;
        }

        return $this->repository->create($userId, $data, $subCategoryIds);
    }
}
