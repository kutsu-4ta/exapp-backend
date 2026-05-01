<?php

namespace App\UseCases\SubCategory;

use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\SubCategory;

class CreateSubCategoryUseCase
{
    public function __construct(
        private readonly SubCategoryRepositoryInterface $repository,
        private readonly SubjectRepositoryInterface $subjectRepository,
    ) {}

    public function __invoke(int $userId, array $data): SubCategory
    {
        $subjectId = $this->subjectRepository->firstOrCreate($userId, $data['subject'])->id;

        return $this->repository->create($userId, array_merge(
            array_diff_key($data, ['subject' => null]),
            ['subject_id' => $subjectId],
        ));
    }
}
