<?php

namespace App\UseCases\Problem;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Problem;

class UpdateProblemUseCase
{
    public function __construct(
        private readonly ProblemRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly MaterialRepositoryInterface $materialRepository,
        private readonly SubCategoryRepositoryInterface $subCategoryRepository,
    ) {}

    public function __invoke(int $userId, int $problemId, array $data): Problem
    {
        $problem = $this->repository->findByIdAndUser($problemId, $userId);

        if ($problem === null) {
            abort(404);
        }

        $subjectId  = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;
        $materialId = $this->resolveMaterialId($userId, $data);

        $subCategoryId = null;
        if (!empty($data['sub_category'])) {
            $subCategoryId = $this->subCategoryRepository->firstOrCreate($userId, $subjectId, $data['sub_category'])->id;
        }

        $exclude = array_flip(['subject', 'material_id', 'material_name', 'sub_category']);

        return $this->repository->update($problem, array_merge(
            array_diff_key($data, $exclude),
            [
                'subject_id'      => $subjectId,
                'material_id'     => $materialId,
                'sub_category_id' => $subCategoryId,
            ],
        ));
    }

    private function resolveMaterialId(int $userId, array $data): int
    {
        if (($data['material_id'] ?? null) !== null) {
            return (int) $data['material_id'];
        }

        return $this->materialRepository->firstOrCreate($userId, (string) $data['material_name'])->id;
    }
}
