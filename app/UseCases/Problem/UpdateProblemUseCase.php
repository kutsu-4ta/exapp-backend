<?php

namespace App\UseCases\Problem;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Problem;

class UpdateProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly MaterialRepositoryInterface $materialRepository,
    ) {}

    public function __invoke(int $userId, int $problemId, array $data): Problem
    {
        $problem = $this->repository->findByIdAndUser($problemId, $userId);

        if ($problem === null) {
            abort(404);
        }

        $subjectId  = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;
        $materialId = $this->materialRepository->firstOrCreate($userId, $data['material'])->id;

        return $this->repository->update($problem, array_merge(
            array_diff_key($data, ['subject' => null, 'material' => null]),
            ['subject_id' => $subjectId, 'material_id' => $materialId],
        ));
    }
}
