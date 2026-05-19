<?php

namespace App\Domain\Snippet;

use App\Models\Snippet;
use Illuminate\Support\Collection;

interface SnippetRepositoryInterface
{
    public function findAllByUser(int $userId): Collection;

    public function findByIdAndUser(int $id, int $userId): ?Snippet;

    public function create(int $userId, array $data): Snippet;

    public function update(Snippet $snippet, array $data): Snippet;

    public function delete(Snippet $snippet): void;

    public function seedDefaults(int $userId): void;
}
