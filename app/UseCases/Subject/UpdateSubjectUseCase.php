<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Subject;

class UpdateSubjectUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $subjectId, string $name, int $displayOrder): Subject
    {
        $subject = $this->repository->findByIdAndUser($subjectId, $userId);

        if ($subject === null) {
            abort(404);
        }

        if ($this->repository->existsByNameAndUser($name, $userId, $subjectId)) {
            abort(422, '同じ名前の科目が既に存在します。');
        }

        return $this->repository->update($subject, $name, $displayOrder);
    }
}
