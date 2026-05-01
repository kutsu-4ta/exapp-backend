<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;

class DeleteSubjectUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $name): void
    {
        $subject = $this->repository->findByName($userId, $name);

        if ($subject === null) {
            abort(404);
        }

        $this->repository->delete($subject);
    }
}
