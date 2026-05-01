<?php

namespace App\UseCases\Material;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Models\Material;
use Illuminate\Http\Exceptions\HttpResponseException;

class RenameMaterialUseCase
{
    public function __construct(
        private readonly MaterialRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $currentName, string $newName): Material
    {
        $material = $this->repository->findByName($userId, $currentName);

        if ($material === null) {
            abort(404);
        }

        if ($this->repository->findByName($userId, $newName) !== null) {
            throw new HttpResponseException(
                response()->json(['message' => '同名の教材が既に存在します。'], 409)
            );
        }

        return $this->repository->rename($material, $newName);
    }
}
