<?php

namespace App\UseCases\MorningBugfix;

use App\Models\Problem;
use App\Services\GeminiService;
use App\Services\NoteParser;
use Illuminate\Support\Collection;

class GenerateBugfixCardUseCase
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * #Definition を持つ問題ごとにフラッシュカードを生成する。
     *
     * @param  Collection<Problem> $problems
     * @param  ?string             $apiKey
     * @param  ?string             $model
     * @return array  問題IDをキーとした ['question' => front, 'explanation' => back] 配列
     */
    public function __invoke(
        Collection $problems,
        ?string    $apiKey = null,
        ?string    $model = null
    ): array {
        // #Definition を持つ問題だけ対象
        $parsed = $problems->mapWithKeys(fn ($p) => [$p->id => NoteParser::parse($p->note)])
            ->filter(fn ($tags) => !empty($tags['definition']));

        if ($parsed->isEmpty()) {
            return [];
        }

        // Gemini で表面（キーワード質問）を一括生成
        $items = $parsed->map(fn ($tags, $id) => [
            'problem_id' => $id,
            'definition' => $tags['definition'],
        ])->values()->toArray();

        $fronts = $this->generateFronts($items, $apiKey, $model);

        // カード組み立て
        $result = [];
        foreach ($parsed as $problemId => $tags) {
            $front = $fronts[$problemId] ?? $this->fallbackFront($tags['definition']);
            $back  = $tags['definition'];
            if (!empty($tags['formula'])) {
                $back .= "\n\n[公式]\n" . $tags['formula'];
            }
            $result[$problemId] = [
                'question'    => $front,
                'explanation' => $back,
            ];
        }

        return $result;
    }

    // ── Gemini バッチ呼び出し ─────────────────────────────────────────────────

    private function generateFronts(array $items, ?string $apiKey, ?string $model): array
    {
        try {
            $raw = $this->gemini->generateJson(
                $this->systemInstruction(),
                $this->userPrompt($items),
                $this->responseSchema(),
                $apiKey,
                $model,
            );

            return collect($raw)
                ->filter(fn ($r) => isset($r['problem_id'], $r['front']) && is_string($r['front']))
                ->mapWithKeys(fn ($r) => [(int) $r['problem_id'] => trim($r['front'])])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function systemInstruction(): string
    {
        return <<<'TEXT'
あなたは資格試験の学習カード生成AIです。
定義テキストを受け取り、そのキーワードを問う質問文（カード表面）を生成します。

【ルール】
- 「〜とは何か？」「〜を定義せよ」「〜の意味を答えよ」などの形式で1文を作成する
- 定義の中心となる用語・概念を問うこと
- 15〜35文字程度で簡潔に日本語で出力する
- 余分な説明は一切不要
TEXT;
    }

    private function userPrompt(array $items): string
    {
        $count = count($items);
        $json  = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<TEXT
以下の {$count} 件の定義それぞれについて、キーワードを問う質問文を生成してください。

{$json}

各定義について problem_id と front（質問文）を返してください。
TEXT;
    }

    private function responseSchema(): array
    {
        return [
            'type'  => 'ARRAY',
            'items' => [
                'type'       => 'OBJECT',
                'properties' => [
                    'problem_id' => ['type' => 'INTEGER'],
                    'front'      => ['type' => 'STRING'],
                ],
                'required'   => ['problem_id', 'front'],
            ],
        ];
    }

    private function fallbackFront(string $definition): string
    {
        $keyword = mb_substr($definition, 0, 20);
        return rtrim($keyword, '。、') . 'とは？';
    }
}
