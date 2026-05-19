<?php

namespace App\UseCases\MorningBugfix;

use App\Models\KnowledgeDigest;
use App\Models\Problem;
use App\Services\GeminiService;
use App\Services\NoteParser;
use App\UseCases\KnowledgeDigest\ExtractKnowledgeDigestUseCase;
use Illuminate\Support\Collection;

class GenerateMorningQuizUseCase
{
    private const EXAM_NAME   = '中小企業診断士試験';
    private const FOCUS_THEME = '定義理解';
    private const OPTION_COUNT = 4;

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
     * @param  Collection<Problem>  $problems
     * @return array  問題IDをキーとした quiz データ配列
     */
    public function __invoke(
        Collection $problems,
        ?string    $apiKey = null,
        ?string    $model = null,
        ?string    $exam = null,
        ?string    $theme = null,
        ?int       $count = 0
    ): array {
        $exam  = $exam  ?? self::EXAM_NAME;
        $theme = $theme ?? self::FOCUS_THEME;
        $count = $count ?: self::OPTION_COUNT;

        // ─── 1. Knowledge Digest をオンデマンド取得 ───────────────────────
        $digests = ($this->extractDigest)($problems, $apiKey, $model);

        // ─── 2. 1回目: 生成 + 自己採点 ───────────────────────────────────
        $systemInstruction = $this->buildSystemInstruction($exam, $theme, $count);
        $userPrompt        = $this->buildUserPrompt($problems, $digests, $count);
        $responseSchema    = $this->buildResponseSchema($count);

        $raw = $this->gemini->generateJson($systemInstruction, $userPrompt, $responseSchema, $apiKey, $model);

        [$passing, $failing] = $this->partitionByScore($raw, $count);

        // ─── 3. 2回目: 失格問題のみ再生成（最大1回） ─────────────────────
        if (!empty($failing)) {
            $failingProblems = $problems->filter(
                fn ($p) => isset($failing[$p->id])
            );

            $regeneratedRaw = $this->regenerateRaw(
                $failingProblems, $digests, $failing, $apiKey, $model, $exam, $theme, $count
            );

            // 再採点: 合格したものだけ追加、2回目でも失格なら破棄
            [$regenPassing] = $this->partitionByScore($regeneratedRaw, $count);
            foreach ($regenPassing as $id => $quiz) {
                $passing[$id] = $quiz;
            }
        }

        return $passing;
    }

    // ── 再生成（2回目コール）: raw items を返す ────────────────────────────

    private function regenerateRaw(
        Collection $failingProblems,
        Collection $digests,
        array      $failingRaw,
        ?string    $apiKey,
        ?string    $model,
        string     $exam,
        string     $theme,
        int        $count
    ): array {
        $systemInstruction = $this->buildSystemInstruction($exam, $theme, $count);
        $userPrompt        = $this->buildRegeneratePrompt($failingProblems, $digests, $failingRaw, $count);
        $responseSchema    = $this->buildResponseSchema($count);

        return $this->gemini->generateJson($systemInstruction, $userPrompt, $responseSchema, $apiKey, $model);
    }

    // ── スコア判定 ──────────────────────────────────────────────────────────

    /**
     * @return array{0: array, 1: array}  [passing(id=>quiz), failing(id=>rawItem)]
     */
    private function partitionByScore(array $raw, int $count): array
    {
        $passing = [];
        $failing = [];

        foreach ($raw as $item) {
            $id = (int) ($item['problem_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $quiz = [
                'question'      => $item['question']      ?? '',
                'options'       => $item['options']        ?? [],
                'correct_index' => (int) ($item['correct_index'] ?? 0),
                'explanation'   => $item['explanation']    ?? '',
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
            return true;
        }

        return ($scores['validity']       ?? 100) >= self::THRESHOLD_VALIDITY
            && ($scores['clarity']        ?? 100) >= self::THRESHOLD_CLARITY
            && ($scores['uniqueness']     ?? 100) >= self::THRESHOLD_UNIQUENESS
            && ($scores['brevity']        ?? 100) >= self::THRESHOLD_BREVITY
            && ($scores['learning_value'] ?? 100) >= self::THRESHOLD_LEARNING_VALUE;
    }

    // ── プロンプト構築 ───────────────────────────────────────────────────────

    private function buildSystemInstruction(string $exam, string $theme, int $count): string
    {
        $baseRules = <<<TEXT
【品質自己採点ルール】
最後に各問題について以下の5軸でスコアリング（0〜100）してください。
- validity: 正答が一意で、他の選択肢が明確に誤りとなっているか
- clarity: 問題文が曖昧でないか
- uniqueness: 各選択肢が互いに区別可能で混同しにくい表現か
- brevity: 解説が簡潔か（理想100字以内）
- learning_value: {$theme}の理解に直結する学習価値があるか

また issues に問題点を端的に列挙してください（問題なければ空配列）。
TEXT;

        return <<<TEXT
あなたは{$exam}の問題生成AIです。
ユーザーが実際に間違えた苦手問題データを基に、「{$theme}」を問う{$count}択選択問題を生成します。

【必須ルール】
1. 選択肢は、単なる正誤ではなく、混同しやすい類似定義をあえて混ぜて、ユーザーの論理的解釈力をテストしてください。消去法ではなく「定義の理解」を強制する良問にしてください。
2. explanationには、提供された定義・キーワード・公式を必ず組み込み、正しい定義を明示してください（ハルシネーション防止）。
3. すべての文章は日本語で生成し、「user_memoにあるように」のような余計な言葉は不要です。
4. correct_indexは0〜{$count}の範囲内の整数で返してください（0始まり）。

{$baseRules}
TEXT;
    }

    private function buildUserPrompt(Collection $problems, Collection $digests, int $count): string
    {
        $items = $problems->map(fn (Problem $p) => $this->buildProblemContext($p, $digests))
            ->values()->toArray();

        $json      = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();

        return <<<TEXT
以下の {$itemCount} 件の苦手問題に対して、それぞれ定義理解を問う{$count}択問題を生成してください。

{$json}

各問題について problem_id, question, options（{$count}要素の配列）, correct_index, explanation, scores, issues を返してください。
TEXT;
    }

    private function buildRegeneratePrompt(
        Collection $problems,
        Collection $digests,
        array      $failingRaw,
        int        $count
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

        return <<<TEXT
以下の {$itemCount} 件の問題は品質基準を満たしませんでした。
previous_issues を参照して改善し、{$count}択問題を再生成してください。

{$json}

各問題について problem_id, question, options（{$count}要素の配列）, correct_index, explanation, scores, issues を返してください。
TEXT;
    }

    private function buildProblemContext(Problem $p, Collection $digests): array
    {
        $digest        = $digests->get($p->id);
        $knowledgeText = $digest && !$digest->isEmpty()
            ? $digest->toPromptText()
            : NoteParser::toPromptText(NoteParser::parse($p->note));

        return [
            'problem_id'   => $p->id,
            'subject'      => $p->subject?->name ?? '',
            'sub_category' => $p->subCategory?->name ?? null,
            'question_ref' => $p->question_ref,
            'knowledge'    => $knowledgeText,
        ];
    }

    private function buildResponseSchema(int $count): array
    {
        return [
            'type'  => 'ARRAY',
            'items' => [
                'type'       => 'OBJECT',
                'properties' => [
                    'problem_id'    => ['type' => 'INTEGER'],
                    'question'      => ['type' => 'STRING'],
                    'options'       => [
                        'type'  => 'ARRAY',
                        'items' => ['type' => 'STRING'],
                    ],
                    'correct_index' => ['type' => 'INTEGER'],
                    'explanation'   => ['type' => 'STRING'],
                    'scores'        => [
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
                'required' => ['problem_id', 'question', 'options', 'correct_index', 'explanation', 'scores', 'issues'],
            ],
        ];
    }
}
