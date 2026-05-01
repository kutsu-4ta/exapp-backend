<?php

namespace App\UseCases\Problem;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Problem;

class CreateProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, array $data): Problem
    {
        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        return $this->repository->create($userId, array_merge(
            array_diff_key($data, ['subject' => null]),
            ['subject_id' => $subjectId],
        ));
    }
}
