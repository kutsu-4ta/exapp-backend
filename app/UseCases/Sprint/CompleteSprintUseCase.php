<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Enums\SprintStatus;
use App\Enums\SprintType;
use App\Models\Sprint;

class CompleteSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId, string $retrospective): Sprint
    {
        $sprint = $this->repository->findByIdAndUser($id, $userId);

        if ($sprint === null) {
            abort(404);
        }

        if ($sprint->type === SprintType::Backlog) {
            abort(422, 'バックログスプリントは完了できません。');
        }

        if ($sprint->status === SprintStatus::Completed) {
            abort(422, 'すでに完了しているスプリントです。');
        }

        return $this->repository->complete($sprint, $retrospective);
    }
}
