<?php

namespace App\UseCases\Snippet;

use App\Domain\Snippet\SnippetRepositoryInterface;
use Illuminate\Support\Collection;

class ListSnippetsUseCase
{
    public function __construct(
        private readonly SnippetRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId): Collection
    {
        return $this->repository->findAllByUser($userId);
    }
}
