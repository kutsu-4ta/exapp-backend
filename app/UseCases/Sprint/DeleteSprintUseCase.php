<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Enums\SprintType;

class DeleteSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId): void
    {
        $sprint = $this->repository->findByIdAndUser($id, $userId);

        if ($sprint === null) {
            abort(404);
        }

        if ($sprint->type === SprintType::Backlog) {
            abort(422, 'バックログスプリントは削除できません。');
        }

        $this->repository->delete($sprint);
    }
}
