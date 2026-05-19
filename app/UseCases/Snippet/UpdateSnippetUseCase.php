<?php

namespace App\UseCases\Snippet;

use App\Domain\Snippet\SnippetRepositoryInterface;
use App\Models\Snippet;

class UpdateSnippetUseCase
{
    public function __construct(
        private readonly SnippetRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $id, array $data): Snippet
    {
        $snippet = $this->repository->findByIdAndUser($id, $userId);

        if ($snippet === null) {
            abort(404);
        }

        return $this->repository->update($snippet, $data);
    }
}
