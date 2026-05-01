<?php

namespace App\UseCases\SubCategory;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\SubCategory;

class UpdateSubCategoryUseCase
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, int $id, array $data): SubCategory
    {
        $subCategory = $this->repository->findByIdAndUser($id, $userId);

        if ($subCategory === null) {
            abort(404);
        }

        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        return $this->repository->update($subCategory, array_merge(
            array_diff_key($data, ['subject' => null]),
            ['subject_id' => $subjectId],
        ));
    }
}
