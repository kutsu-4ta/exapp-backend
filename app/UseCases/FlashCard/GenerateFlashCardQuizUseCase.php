<?php

namespace App\UseCases\FlashCard;

use App\Models\KnowledgeDigest;
use App\Models\Problem;
use App\Services\GeminiService;
use App\Services\NoteParser;
use App\UseCases\KnowledgeDigest\ExtractKnowledgeDigestUseCase;
use Illuminate\Support\Collection;

class GenerateFlashCardQuizUseCase
{
    private const EXAM_NAME = '中小企業診断士試験';

    // 品質スコア閾値（この値未満を「失格」と判定）
    private const THRESHOLD_VALIDITY       = 80;
    private const THRESHOLD_CLARITY        = 80;
    private const THRESHOLD_UNIQUENESS     = 80;
    private const THRESHOLD_BREVITY        = 70;
    private const THRESHOLD_LEARNING_VALUE = 80;

    public function __construct(
        private readonly GeminiService                $gemini,
        private readonly ExtractKnowledgeDigestUseCase $extractDigest,
    ) {}

    /**
     * @param  Collection<Problem> $problems
     * @return array  問題IDをキーとした quiz データ配列
     */
    public function __invoke(
        Collection $problems,
        ?string    $apiKey = null,
        ?string    $model = null,
        bool       $formulaOnly = false
    ): array {
        // ─── 1. Knowledge Digest をオンデマンド取得 ───────────────────────
        $digests = ($this->extractDigest)($problems, $apiKey, $model);

        // ─── 2. 1回目: 生成 + 自己採点 ───────────────────────────────────
        $systemInstruction = $this->buildSystemInstruction($formulaOnly);
        $userPrompt        = $this->buildUserPrompt($problems, $digests, $formulaOnly);
        $responseSchema    = $this->buildResponseSchema();

        $raw = $this->gemini->generateJson($systemInstruction, $userPrompt, $responseSchema, $apiKey, $model);

        [$passing, $failing] = $this->partitionByScore($raw);

        // ─── 3. 2回目: 失格問題のみ再生成（最大1回） ─────────────────────
        if (!empty($failing)) {
            $failingProblems = $problems->filter(
                fn ($p) => isset($failing[$p->id])
            );

            $regeneratedRaw = $this->regenerateRaw(
                $failingProblems, $digests, $failing, $apiKey, $model, $formulaOnly
            );

            // 再採点: 合格したものだけ追加、2回目でも失格なら破棄（controllers は quiz=null で返す）
            [$regenPassing] = $this->partitionByScore($regeneratedRaw);
            foreach ($regenPassing as $id => $quiz) {
                $passing[$id] = $quiz;
            }
        }

        return $passing;
    }

    // ── 再生成（2回目コール）: raw items を返す ────────────────────────────

    private function regenerateRaw(
        Collection  $failingProblems,
        Collection  $digests,
        array       $failingRaw,
        ?string     $apiKey,
        ?string     $model,
        bool        $formulaOnly
    ): array {
        $systemInstruction = $this->buildSystemInstruction($formulaOnly);
        $userPrompt        = $this->buildRegeneratePrompt($failingProblems, $digests, $failingRaw, $formulaOnly);
        $responseSchema    = $this->buildResponseSchema();

        return $this->gemini->generateJson($systemInstruction, $userPrompt, $responseSchema, $apiKey, $model);
    }

    // ── スコア判定 ──────────────────────────────────────────────────────────

    /**
     * @return array{0: array, 1: array}  [passing(id=>quiz), failing(id=>rawItem)]
     */
    private function partitionByScore(array $raw): array
    {
        $passing = [];
        $failing = [];

        foreach ($raw as $item) {
            $id = (int) ($item['problem_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $quiz = [
                'question'      => $item['question']    ?? '',
                'options'       => [],
                'correct_index' => 0,
                'explanation'   => $item['explanation'] ?? '',
            ];

            if ($this->passesThreshold($item['scores'] ?? [])) {
                $passing[$id] = $quiz;
            } else {
                $failing[$id] = $item;
            }
        }

        return [$passing, $failing];
    }

    private function passesThreshold(array $scores): bool
    {
        if (empty($scores)) {
            return true; // スコアなしは通過扱い
        }

        return ($scores['validity']       ?? 100) >= self::THRESHOLD_VALIDITY
            && ($scores['clarity']        ?? 100) >= self::THRESHOLD_CLARITY
            && ($scores['uniqueness']     ?? 100) >= self::THRESHOLD_UNIQUENESS
            && ($scores['brevity']        ?? 100) >= self::THRESHOLD_BREVITY
            && ($scores['learning_value'] ?? 100) >= self::THRESHOLD_LEARNING_VALUE;
    }

    // ── プロンプト構築 ───────────────────────────────────────────────────────

    private function buildSystemInstruction(bool $formulaOnly): string
    {
        $exam = self::EXAM_NAME;

        $baseRules = <<<TEXT
【品質自己採点ルール】
最後に各問題について以下の5軸でスコアリング（0〜100）してください。
- validity: 正答が一意で問題として成立しているか
- clarity: 問題文が曖昧でないか
- uniqueness: 誤答選択肢がなく（単語カードのため）正解が明確か
- brevity: 解説が簡潔か（理想100字以内）
- learning_value: 学習価値があるか（定義・公式の理解に直結するか）

また issues に問題点を端的に列挙してください（問題なければ空配列）。
TEXT;

        if ($formulaOnly) {
            return <<<TEXT
あなたは{$exam}の問題生成AIです。
ユーザーが苦手とする公式・定義問題から、単語カード形式の問答を生成します。

【必須ルール】
1. question（表面）: 「〜の計算式を答えよ」「〜の公式を述べよ」「〜を定義せよ」など、公式・定義を問う質問文を1文で作成する
2. explanation（裏面）: 正確な公式・定義を数式や箇条書きで簡潔に記述する。提供された知識情報を最優先で使用し、ハルシネーションを防ぐこと
3. すべての文章は日本語で生成する
4. problem_idは必ず元の整数値を使用する

{$baseRules}
TEXT;
        }

        return <<<TEXT
あなたは{$exam}の問題生成AIです。
ユーザーが苦手とする問題データから、単語カード形式の問答を生成します。

【必須ルール】
1. question（表面）: 「〜とは何か？」「〜を説明せよ」「〜の特徴を答えよ」など、定義・概念を問う質問文を1文で作成する
2. explanation（裏面）: 提供された定義・キーワードを最優先で使用し、正確な説明を2〜3文で簡潔に記述する。ハルシネーション厳禁
3. すべての文章は日本語で生成する
4. problem_idは必ず元の整数値を使用する

{$baseRules}
TEXT;
    }

    private function buildUserPrompt(Collection $problems, Collection $digests, bool $formulaOnly): string
    {
        $items = $problems->map(fn (Problem $p) => $this->buildProblemContext($p, $digests))
            ->values()->toArray();

        $json      = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();
        $mode      = $formulaOnly ? '公式チェックカード' : '単語カード';

        return <<<TEXT
以下の {$itemCount} 件の苦手問題に対して、それぞれ{$mode}形式の問答を生成してください。

{$json}

各問題について problem_id, question（カード表面）, explanation（カード裏面）, scores, issues を返してください。
TEXT;
    }

    private function buildRegeneratePrompt(
        Collection $problems,
        Collection $digests,
        array      $failingRaw,
        bool       $formulaOnly
    ): string {
        $items = $problems->map(function (Problem $p) use ($digests, $failingRaw) {
            $context = $this->buildProblemContext($p, $digests);
            $issues  = $failingRaw[$p->id]['issues'] ?? [];
            if (!empty($issues)) {
                $context['previous_issues'] = $issues;
            }
            return $context;
        })->values()->toArray();

        $json      = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();
        $mode      = $formulaOnly ? '公式チェックカード' : '単語カード';

        return <<<TEXT
以下の {$itemCount} 件の問題は品質基準を満たしませんでした。
previous_issues を参照して改善し、{$mode}形式の問答を再生成してください。

{$json}

各問題について problem_id, question, explanation, scores, issues を返してください。
TEXT;
    }

    private function buildProblemContext(Problem $p, Collection $digests): array
    {
        $digest       = $digests->get($p->id);
        $knowledgeText = $digest && !$digest->isEmpty()
            ? $digest->toPromptText()
            : NoteParser::toPromptText(NoteParser::parse($p->note));

        return [
            'problem_id'    => $p->id,
            'subject'       => $p->subject?->name ?? '',
            'sub_category'  => $p->subCategory?->name ?? null,
            'question_ref'  => $p->question_ref,
            'knowledge'     => $knowledgeText,
        ];
    }

    private function buildResponseSchema(): array
    {
        return [
            'type'  => 'ARRAY',
            'items' => [
                'type'       => 'OBJECT',
                'properties' => [
                    'problem_id'  => ['type' => 'INTEGER'],
                    'question'    => ['type' => 'STRING'],
                    'explanation' => ['type' => 'STRING'],
                    'scores'      => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'validity'       => ['type' => 'INTEGER'],
                            'clarity'        => ['type' => 'INTEGER'],
                            'uniqueness'     => ['type' => 'INTEGER'],
                            'brevity'        => ['type' => 'INTEGER'],
                            'learning_value' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['validity', 'clarity', 'uniqueness', 'brevity', 'learning_value'],
                    ],
                    'issues' => [
                        'type'  => 'ARRAY',
                        'items' => ['type' => 'STRING'],
                    ],
                ],
                'required' => ['problem_id', 'question', 'explanation', 'scores', 'issues'],
            ],
        ];
    }
}
