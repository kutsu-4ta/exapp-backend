<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Subject;
use Illuminate\Support\Collection;

class EloquentSubjectRepository implements SubjectRepositoryInterface
{
    private const DEFAULT_SUBJECTS = [
        '経済学・経済政策',
        '財務・会計',
        '企業経営理論',
        '運営管理',
        '経営法務',
        '経営情報システム',
        '中小企業経営・政策',
    ];

    public function findAllByUser(int $userId): Collection
    {
        return Subject::where('user_id', $userId)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?Subject
    {
        return Subject::where('id', $id)->where('user_id', $userId)->first();
    }

    public function existsByNameAndUser(string $name, int $userId, ?int $excludeId = null): bool
    {
        $query = Subject::where('user_id', $userId)->where('name', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(int $userId, string $name, int $displayOrder): Subject
    {
        return Subject::create([
            'user_id' => $userId,
            'name' => $name,
            'display_order' => $displayOrder,
        ]);
    }

    public function update(Subject $subject, string $name, int $displayOrder): Subject
    {
        $subject->update(['name' => $name, 'display_order' => $displayOrder]);

        return $subject->fresh();
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }

    public function isInUse(Subject $subject): bool
    {
        return $subject->studySessions()->exists() || $subject->problems()->exists();
    }

    public function seedDefaults(int $userId): void
    {
        $now = now();
        $records = array_map(fn (string $name, int $index) => [
            'user_id' => $userId,
            'name' => $name,
            'display_order' => $index,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::DEFAULT_SUBJECTS, array_keys(self::DEFAULT_SUBJECTS));

        Subject::insert($records);
    }
}
