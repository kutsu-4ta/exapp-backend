<?php

namespace App\UseCases\Snippet;

use App\Domain\Snippet\SnippetRepositoryInterface;
use App\Models\Snippet;

class CreateSnippetUseCase
{
    public function __construct(
        private readonly SnippetRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, array $data): Snippet
    {
        return $this->repository->create($userId, $data);
    }
}
