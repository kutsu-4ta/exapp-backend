<?php

namespace App\UseCases\Practice;

use App\Domain\Practice\PracticeSessionDraftRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\PracticeSessionDraft;

class SavePracticeSessionDraftUseCase
{
    public function __construct(
        private readonly PracticeSessionDraftRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, array $data): PracticeSessionDraft
    {
        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        return $this->repository->upsert(
            $userId,
            $subjectId,
            (int) $data['currentIndex'],
            $data['log'],
        );
    }
}
