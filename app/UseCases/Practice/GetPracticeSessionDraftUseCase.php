<?php

namespace App\UseCases\Practice;

use App\Domain\Practice\PracticeSessionDraftRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\PracticeSessionDraft;

class GetPracticeSessionDraftUseCase
{
    public function __construct(
        private readonly PracticeSessionDraftRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, string $subjectName): ?PracticeSessionDraft
    {
        $subject = $this->subjectRepository->findByName($userId, $subjectName);

        if ($subject === null) {
            return null;
        }

        return $this->repository->findBySubject($userId, $subject->id);
    }
}
