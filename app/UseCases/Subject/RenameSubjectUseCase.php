<?php

namespace App\UseCases\Subject;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Subject;
use Illuminate\Http\Exceptions\HttpResponseException;

class RenameSubjectUseCase
{
    public function __construct(
        private readonly SubjectRepositoryInterface $repository,
    ) {}

    public function __invoke(int $userId, string $currentName, string $newName): Subject
    {
        $subject = $this->repository->findByName($userId, $currentName);

        if ($subject === null) {
            abort(404);
        }

        if ($this->repository->findByName($userId, $newName) !== null) {
            throw new HttpResponseException(
                response()->json(['message' => '同名の科目が既に存在します。'], 409)
            );
        }

        return $this->repository->rename($subject, $newName);
    }
}
