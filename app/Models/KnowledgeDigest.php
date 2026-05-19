<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class KnowledgeDigest extends Model
{
    protected $fillable = [
        'problem_id',
        'definitions',
        'formulas',
        'keywords',
        'pitfalls',
        'examples',
        'relations',
        'memory_hooks',
        'extracted_at',
    ];

    protected function casts(): array
    {
        return [
            'definitions'  => 'array',
            'formulas'     => 'array',
            'keywords'     => 'array',
            'pitfalls'     => 'array',
            'examples'     => 'array',
            'relations'    => 'array',
            'memory_hooks' => 'array',
            'extracted_at' => 'datetime',
        ];
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }

    /**
     * プロンプト注入用に構造データをテキスト化して返す。
     */
    public function toPromptText(): string
    {
        $parts = [];

        $map = [
            'definitions'  => '定義',
            'formulas'     => '公式',
            'keywords'     => '重要語',
            'pitfalls'     => '間違えやすい点',
            'examples'     => '具体例',
            'relations'    => '他概念との関係',
            'memory_hooks' => '覚え方',
        ];

        foreach ($map as $field => $label) {
            $items = $this->{$field};
            if (!empty($items)) {
                $parts[] = "[{$label}] " . implode(' / ', (array) $items);
            }
        }

        return implode("\n", $parts);
    }

    /**
     * 全フィールドが空かどうか（抽出失敗の検出に使用）
     */
    public function isEmpty(): bool
    {
        foreach (['definitions', 'formulas', 'keywords', 'pitfalls', 'examples', 'relations', 'memory_hooks'] as $field) {
            if (!empty($this->{$field})) {
                return false;
            }
        }
        return true;
    }
}
