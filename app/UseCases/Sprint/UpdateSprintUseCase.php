<?php

namespace App\UseCases\Sprint;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Enums\SprintType;
use App\Models\Sprint;
use Illuminate\Validation\ValidationException;

class UpdateSprintUseCase
{
    public function __construct(
        private readonly SprintRepositoryInterface $repository,
    ) {}

    public function __invoke(int $id, int $userId, array $data): Sprint
    {
        $sprint = $this->repository->findByIdAndUser($id, $userId);

        if ($sprint === null) {
            abort(404);
        }

        // バックログは name / goal のみ変更可
        if ($sprint->type === SprintType::Backlog) {
            $data = array_intersect_key($data, array_flip(['name', 'goal']));
        }

        return $this->repository->update($sprint, $data);
    }
}
