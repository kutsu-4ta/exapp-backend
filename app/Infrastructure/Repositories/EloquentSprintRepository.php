<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Sprint\SprintRepositoryInterface;
use App\Enums\SprintStatus;
use App\Enums\SprintType;
use App\Models\Sprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentSprintRepository implements SprintRepositoryInterface
{
    public function findAllByUser(int $userId): Collection
    {
        return Sprint::where('user_id', $userId)
            ->orderByRaw("CASE WHEN type = 'backlog' THEN 1 ELSE 0 END")
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?Sprint
    {
        return Sprint::where('user_id', $userId)->find($id);
    }

    public function findOrCreateBacklog(int $userId): Sprint
    {
        return Sprint::firstOrCreate(
            ['user_id' => $userId, 'type' => SprintType::Backlog->value],
            [
                'name'   => 'バックログ',
                'status' => SprintStatus::Active->value,
            ],
        );
    }

    public function create(int $userId, array $data): Sprint
    {
        return Sprint::create([
            'user_id'    => $userId,
            'name'       => $data['name'],
            'goal'       => $data['goal'] ?? null,
            'type'       => SprintType::Active->value,
            'status'     => SprintStatus::Active->value,
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
        ]);
    }

    public function update(Sprint $sprint, array $data): Sprint
    {
        $sprint->update($data);

        return $sprint->fresh();
    }

    public function complete(Sprint $sprint, string $retrospective): Sprint
    {
        $sprint->update([
            'status'        => SprintStatus::Completed->value,
            'completed_at'  => Carbon::now(),
            'retrospective' => $retrospective,
        ]);

        return $sprint->fresh();
    }

    public function delete(Sprint $sprint): void
    {
        $sprint->delete();
    }
}
