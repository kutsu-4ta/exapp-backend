<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Subject;

class SetSubjectHiddenUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $name, bool $hidden): Subject
    {
        $subject = $this->repository->findByName($userId, $name);

        if ($subject === null) {
            abort(404);
        }

        return $this->repository->setHidden($subject, $hidden);
    }
}
