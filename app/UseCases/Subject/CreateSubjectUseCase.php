<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Subject;

class CreateSubjectUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $name, int $displayOrder): Subject
    {
        if ($this->repository->existsByNameAndUser($name, $userId)) {
            abort(422, '同じ名前の科目が既に存在します。');
        }

        return $this->repository->create($userId, $name, $displayOrder);
    }
}
