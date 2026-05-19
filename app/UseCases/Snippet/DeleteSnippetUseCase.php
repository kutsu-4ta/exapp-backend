<?php

namespace App\UseCases\Snippet;

use App\Domain\Snippet\SnippetRepositoryInterface;

class DeleteSnippetUseCase
{
    public function __construct(
        private readonly SnippetRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, int $id): void
    {
        $snippet = $this->repository->findByIdAndUser($id, $userId);

        if ($snippet === null) {
            abort(404);
        }

        $this->repository->delete($snippet);
    }
}
