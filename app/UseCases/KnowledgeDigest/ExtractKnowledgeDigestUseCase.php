<?php

namespace App\UseCases\KnowledgeDigest;

use App\Models\KnowledgeDigest;
use App\Models\Problem;
use App\Services\GeminiService;
use App\Services\NoteParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Problem の note から構造化知識を抽出し、knowledge_digests に保存する。
 *
 * ・note が構造化済み（#Definition 等）の場合はAI不要でパースのみ実施。
 * ・未構造の場合は Gemini に1コール依頼してデータ抽出。
 * ・既に抽出済みの問題はスキップ。
 *
 * このAPIコールは「クイズ生成2コール制限」とは別枠の一時コストとして扱う。
 * 一度抽出されれば以降のリクエストではコールが発生しない。
 */
class ExtractKnowledgeDigestUseCase
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * @param  Collection<Problem> $problems
     * @param  string|null         $apiKey
     * @param  string|null         $model
     * @return Collection<KnowledgeDigest>  problem_id をキーとした Collection
     */
    public function __invoke(Collection $problems, ?string $apiKey = null, ?string $model = null): Collection
    {
        if ($problems->isEmpty()) {
            return collect();
        }

        $problemIds     = $problems->pluck('id');
        $existingDigests = KnowledgeDigest::whereIn('problem_id', $problemIds)
            ->get()
            ->keyBy('problem_id');

        $toExtract = $problems->filter(fn ($p) => !$existingDigests->has($p->id));

        // 構造化済みノートはAI不要でパース保存
        [$structured, $unstructured] = $toExtract->partition(
            fn ($p) => NoteParser::hasStructuredContent($p->note)
        );

        foreach ($structured as $problem) {
            $this->saveFromParsed($problem, NoteParser::parse($problem->note));
        }

        // 未構造ノートは Gemini で一括抽出
        if ($unstructured->isNotEmpty()) {
            $extracted = $this->extractWithAi($unstructured, $apiKey, $model);
            foreach ($unstructured as $problem) {
                $data = $extracted[$problem->id] ?? [];
                $this->saveFromAiResult($problem, $data);
            }
        }

        // 最新の全ダイジェストを返す
        return KnowledgeDigest::whereIn('problem_id', $problemIds)
            ->get()
            ->keyBy('problem_id');
    }

    private function saveFromParsed(Problem $problem, array $parsed): KnowledgeDigest
    {
        return KnowledgeDigest::updateOrCreate(
            ['problem_id' => $problem->id],
            [
                'definitions'  => isset($parsed['definition'])  ? [$parsed['definition']]  : null,
                'formulas'     => isset($parsed['formula'])      ? [$parsed['formula']]      : null,
                'keywords'     => isset($parsed['keyword'])      ? [$parsed['keyword']]      : null,
                'pitfalls'     => isset($parsed['pitfall'])      ? [$parsed['pitfall']]      : null,
                'examples'     => isset($parsed['example'])      ? [$parsed['example']]      : null,
                'relations'    => isset($parsed['relation'])     ? [$parsed['relation']]     : null,
                'memory_hooks' => isset($parsed['memory_hook'])  ? [$parsed['memory_hook']]  : null,
                'extracted_at' => Carbon::now(),
            ],
        );
    }

    /**
     * @return array<int, array>  problem_id => 抽出データ
     */
    private function extractWithAi(Collection $problems, ?string $apiKey, ?string $model): array
    {
        $systemInstruction = <<<TEXT
あなたは学習支援AIです。問題メモから構造化された学習知識を抽出してください。

各問題について以下の項目を抽出し、それぞれ文字列の配列として返してください。
- definitions: 重要な定義（「〜とは〜」形式）
- formulas: 公式・計算式
- keywords: 重要キーワード・用語
- pitfalls: 間違えやすいポイント・落とし穴
- examples: 具体例・数値例
- relations: 他の概念・用語との関係
- memory_hooks: 覚え方・語呂合わせ

情報がない項目は空配列 [] を返してください。創作・ハルシネーション厳禁。
TEXT;

        $items = $problems->map(fn (Problem $p) => [
            'problem_id'   => $p->id,
            'subject'      => $p->subject?->name ?? '',
            'sub_category' => $p->subCategory?->name ?? null,
            'question_ref' => $p->question_ref,
            'note'         => $p->note,
        ])->values()->toArray();

        $userPrompt = '以下の問題メモから知識を抽出してください。' . "\n\n"
            . json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $schema = [
            'type'  => 'ARRAY',
            'items' => [
                'type'       => 'OBJECT',
                'properties' => [
                    'problem_id'   => ['type' => 'INTEGER'],
                    'definitions'  => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'formulas'     => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'keywords'     => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'pitfalls'     => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'examples'     => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'relations'    => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'memory_hooks' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                ],
                'required' => ['problem_id', 'definitions', 'formulas', 'keywords', 'pitfalls'],
            ],
        ];

        $raw = $this->gemini->generateJson($systemInstruction, $userPrompt, $schema, $apiKey, $model);

        $result = [];
        foreach ($raw as $item) {
            $id = (int) ($item['problem_id'] ?? 0);
            if ($id > 0) {
                $result[$id] = $item;
            }
        }
        return $result;
    }

    private function saveFromAiResult(Problem $problem, array $data): KnowledgeDigest
    {
        return KnowledgeDigest::updateOrCreate(
            ['problem_id' => $problem->id],
            [
                'definitions'  => $data['definitions']  ?? null,
                'formulas'     => $data['formulas']      ?? null,
                'keywords'     => $data['keywords']      ?? null,
                'pitfalls'     => $data['pitfalls']      ?? null,
                'examples'     => $data['examples']      ?? null,
                'relations'    => $data['relations']     ?? null,
                'memory_hooks' => $data['memory_hooks']  ?? null,
                'extracted_at' => Carbon::now(),
            ],
        );
    }
}
