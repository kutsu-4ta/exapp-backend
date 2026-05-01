<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;
use Illuminate\Support\Collection;

class ListSubjectsUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId): Collection
    {
        return $this->repository->findAll($userId);
    }
}
