<?php

namespace App\UseCases\Practice;

use App\Domain\Practice\PracticeSessionDraftRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;

class DeletePracticeSessionDraftUseCase
{
    public function __construct(
        private readonly PracticeSessionDraftRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, string $subjectName): void
    {
        $subject = $this->subjectRepository->findByName($userId, $subjectName);

        if ($subject === null) {
            return; // 科目がなければドラフトも存在しない（冪等）
        }

        $this->repository->deleteBySubject($userId, $subject->id);
    }
}
