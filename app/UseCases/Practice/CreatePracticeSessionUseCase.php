<?php

namespace App\UseCases\Practice;

use App\Domain\Practice\PracticeSessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\PracticeSession;

class CreatePracticeSessionUseCase
{
    public function __construct(
        private readonly PracticeSessionRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, array $data): PracticeSession
    {
        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        $totalElapsedMs = (int) $data['totalElapsedMs'];
        $totalMinutes   = $totalElapsedMs > 0
            ? max(1, (int) ceil($totalElapsedMs / 60000))
            : 0;

        return $this->repository->create(
            $userId,
            $subjectId,
            $data['date'],
            $totalElapsedMs,
            $totalMinutes,
        );
    }
}
