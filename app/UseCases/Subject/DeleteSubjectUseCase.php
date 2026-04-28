<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;

class DeleteSubjectUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $subjectId): void
    {
        $subject = $this->repository->findByIdAndUser($subjectId, $userId);

        if ($subject === null) {
            abort(404);
        }

        if ($this->repository->isInUse($subject)) {
            abort(422, '学習記録または苦手問題で使用中の科目は削除できません。');
        }

        $this->repository->delete($subject);
    }
}
