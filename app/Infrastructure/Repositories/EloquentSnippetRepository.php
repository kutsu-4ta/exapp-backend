<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Snippet\SnippetRepositoryInterface;
use App\Models\Snippet;
use Illuminate\Support\Collection;

class EloquentSnippetRepository implements SnippetRepositoryInterface
{
    public function findAllByUser(int $userId): Collection
    {
        return Snippet::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByIdAndUser(int $id, int $userId): ?Snippet
    {
        return Snippet::where('user_id', $userId)->find($id);
    }

    public function create(int $userId, array $data): Snippet
    {
        return Snippet::create(array_merge(['user_id' => $userId], $data));
    }

    public function update(Snippet $snippet, array $data): Snippet
    {
        $snippet->update($data);

        return $snippet->fresh();
    }

    public function delete(Snippet $snippet): void
    {
        $snippet->delete();
    }

    public function seedDefaults(int $userId): void
    {
        $defaults = [
            [
                'title'   => '解説要求',
                'content' => "ノートにまとめたいので、特定の選択肢に依存しないように解説してください。\n通常の解説の後に、末尾に以下のメタ文字列をつけてください。\n#Definition 定義, #Formula 公式, #Keyword 重要語, #Pitfall 間違えやすい点, #Example 具体例, #Relation 他概念との関係, #MemoryHook 覚え方",
            ],
        ];

        foreach ($defaults as $data) {
            Snippet::firstOrCreate(
                ['user_id' => $userId, 'title' => $data['title']],
                ['content' => $data['content']],
            );
        }
    }
}
