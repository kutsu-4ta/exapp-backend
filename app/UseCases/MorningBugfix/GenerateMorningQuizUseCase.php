<?php

namespace App\UseCases\MorningBugfix;

use App\Models\Problem;
use App\Services\GeminiService;
use App\Services\NoteParser;
use App\UseCases\KnowledgeDigest\ExtractKnowledgeDigestUseCase;
use Illuminate\Support\Collection;

class GenerateMorningQuizUseCase
{
    private const EXAM_NAME = '中小企業診断士試験';
    private const FOCUS_THEME = '定義理解';
    private const OPTION_COUNT = 4;

    public function __construct(
        private readonly GeminiService                 $gemini,
        private readonly ExtractKnowledgeDigestUseCase $extractDigest,
    ) {
    }

    /**
     * @param Collection<Problem> $problems
     * @return array  問題IDをキーとした quiz データ配列
     */
    public function __invoke(
        Collection $problems,
        ?string    $apiKey = null,
        ?string    $model = null,
        ?string    $exam = null,
        ?string    $theme = null,
        ?int       $count = 0
    ): array
    {
        $exam = $exam ?? self::EXAM_NAME;
        $theme = $theme ?? self::FOCUS_THEME;
        $count = $count ?: self::OPTION_COUNT;

        // ─── 1. Knowledge Digest をオンデマンド取得 ───────────────────────
        $digests = ($this->extractDigest)($problems, $apiKey, $model);

        // ─── 2. 1回目: 生成 ──────────────────────────────────────────────
        $raw = $this->gemini->generateJson(
            $this->buildSystemInstruction($exam, $theme, $count),
            $this->buildUserPrompt($problems, $digests, $count),
            $this->buildResponseSchema($count),
            $apiKey,
            $model
        );

        // ─── 3. PHP 静的バリデーション ───────────────────────────────────
        [$staticPassing, $failing] = $this->applyStaticValidation($raw, $count);

        // ─── 4. Validator AI ─────────────────────────────────────────────
        $passing = [];
        if (!empty($staticPassing)) {
            $validationResults = $this->validateGeneratedQuizzes(
                $staticPassing, $problems, $digests, $apiKey, $model
            );
            foreach ($staticPassing as $id => $item) {
                $result = $validationResults[$id] ?? null;
                if ($result['passed'] ?? false) {
                    $passing[$id] = $this->toQuiz($item);

                } elseif (
                    in_array('correct_index mismatch', $result['fatal_errors'] ?? [], true)
                    && ($result['confidence'] ?? 0) >= 90
                    && isset($result['expected_correct_index'])
                ) {
                    // Validator の正解で補正
                    $item['correct_index'] = $result['expected_correct_index'];

                    $passing[$id] = $this->toQuiz($item);

                } else {
                    $failing[$id] = array_merge(
                        $item,
                        ['fatal_errors' => $result['fatal_errors'] ?? []]
                    );
                }
            }
        }

        // ─── 5. 失格問題を再生成（最大1回） ─────────────────────────────
        if (!empty($failing)) {
            $failingProblems = $problems->filter(fn($p) => isset($failing[$p->id]));

            $regenRaw = $this->regenerateRaw(
                $failingProblems, $digests, $failing, $apiKey, $model, $exam, $theme, $count
            );

            [$regenStaticPassing] = $this->applyStaticValidation($regenRaw, $count);

            if (!empty($regenStaticPassing)) {
                $regenValidation = $this->validateGeneratedQuizzes(
                    $regenStaticPassing, $problems, $digests, $apiKey, $model
                );
                foreach ($regenStaticPassing as $id => $item) {
                    if ($regenValidation[$id]['passed'] ?? false) {
                        $passing[$id] = $this->toQuiz($item);
                    }
                    // passed=false → 破棄
                }
            }
        }

        return $passing;
    }

    // ── 再生成（2回目コール）────────────────────────────────────────────────

    private function regenerateRaw(
        Collection $failingProblems,
        Collection $digests,
        array      $failingItems,
        ?string    $apiKey,
        ?string    $model,
        string     $exam,
        string     $theme,
        int        $count
    ): array
    {
        return $this->gemini->generateJson(
            $this->buildSystemInstruction($exam, $theme, $count),
            $this->buildRegeneratePrompt($failingProblems, $digests, $failingItems, $count),
            $this->buildResponseSchema($count),
            $apiKey,
            $model
        );
    }

    // ── Validator AI ─────────────────────────────────────────────────────────

    /**
     * @return array  [problem_id => ['passed' => bool, 'fatal_errors' => string[], 'confidence' => int]]
     */
    private function validateGeneratedQuizzes(
        array      $generated,
        Collection $problems,
        Collection $digests,
        ?string    $apiKey,
        ?string    $model,
): array {
        $raw = $this->gemini->generateJson(
            $this->buildValidationSystemInstruction(),
            $this->buildValidationPrompt($generated, $problems, $digests),
            $this->buildValidationResponseSchema(),
            $apiKey,
            $model
        );

        $results = [];

        foreach ($raw as $item) {
            $id = (int)($item['problem_id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $results[$id] = [
                'passed' => (bool)($item['passed'] ?? false),
                'expected_correct_index' =>
                    (int)($item['expected_correct_index'] ?? -1),
                'fatal_errors' => $item['fatal_errors'] ?? [],
                'confidence' => (int)($item['confidence'] ?? 0),
            ];
        }

        return $results;
    }

    // ── PHP 静的バリデーション ────────────────────────────────────────────────

    /**
     * @return array{0: array, 1: array}  [passing(id=>item), failing(id=>item+fatal_errors)]
     */
    private function applyStaticValidation(array $raw, int $count): array
    {
        $passing = [];
        $failing = [];

        foreach ($raw as $item) {
            $id = (int)($item['problem_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if ($this->passesStaticValidation($item, $count)) {
                $passing[$id] = $item;
            } else {
                $failing[$id] = array_merge($item, ['fatal_errors' => $this->staticErrors($item, $count)]);
            }
        }

        return [$passing, $failing];
    }

    private function passesStaticValidation(array $item, int $count): bool
    {
        return empty($this->staticErrors($item, $count));
    }

    private function staticErrors(array $item, int $count): array
    {
        $errors = [];
        $options = $item['options'] ?? [];
        $correctIndex = (int)($item['correct_index'] ?? -1);

        if (count($options) !== $count) {
            $errors[] = 'options count must be ' . $count . ', got ' . count($options);
        }

        if ($correctIndex < 0 || $correctIndex >= count($options)) {
            $errors[] = 'correct_index ' . $correctIndex . ' is out of range [0, ' . count($options) . ')';
        }

        if (count(array_unique($options)) !== count($options)) {
            $errors[] = 'duplicate options detected';
        }

        return $errors;
    }

    // ── プロンプト構築 ───────────────────────────────────────────────────────

    private function buildSystemInstruction(string $exam, string $theme, int $count): string
    {
        $zeroStartIndex = $count - 1;
        return <<<TEXT
あなたは{$exam}の問題生成AIです。
「{$theme}」を問う{$count}択選択問題を日本語で生成してください。

【必須】
- 定義理解を問う問題にする
- 正答は一意
- 選択肢は同程度の自然さ・長さにする
- 誤答は類似概念を用いる
- explanationに以下を含める
  - 正答が正しい理由
  - 他選択肢が誤りの理由
  - 提供された定義・キーワード・公式
- correct_indexは0始まりの整数（0〜{$zeroStartIndex}）
- explanation に選択肢番号（1,2,3...）を書かない
- 選択肢名そのもの（例: EOQ）で説明する

【禁止】
- 複数正答
- 選択肢の意味重複
- 明らかに不自然な誤答
- 問題文で正答を示唆
- 提供情報にない内容を解説へ追加
- 冗長な表現
TEXT;
    }

    private function buildValidationSystemInstruction(): string
    {
        return <<<TEXT
あなたは問題品質検証AIです。
生成された問題を knowledge と照合してください。

knowledge を唯一の正解根拠とします。
knowledge にない情報は推論で補わないでください。

以下を検証してください。

1. knowledgeから正答選択肢を1つ決め、その index を expected_correct_index に出力
2. 生成AIの explanation がその正答と一致するか
3. 誤答選択肢は knowledge 上で誤りか
4. 複数の選択肢が正答になっていないか

以下が1つでもあれば passed=false:
- correct_index mismatch
- explanation contradiction
- multiple possible answers
- ambiguous wording
- duplicate options
TEXT;
    }

    private function buildUserPrompt(Collection $problems, Collection $digests, int $count): string
    {
        $items = $problems->map(fn(Problem $p) => $this->buildProblemContext($p, $digests))
            ->values()->toArray();

        $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();

        return <<<TEXT
以下の {$itemCount} 件の知識に対して、それぞれ定義理解を問う{$count}択問題を生成してください。

{$json}

各問題について problem_id, question, options（{$count}要素の配列）, correct_index, explanation を返してください。
TEXT;
    }

    private function buildValidationPrompt(array $generated, Collection $problems, Collection $digests): string
    {
        $byId = $problems->keyBy('id');
        $items = [];

        foreach ($generated as $id => $quiz) {
            $problem = $byId->get($id);
            if (!$problem) {
                continue;
            }

            $digest = $digests->get($id);
            $knowledge = ($digest && !$digest->isEmpty())
                ? $digest->toPromptText()
                : NoteParser::toPromptText(NoteParser::parse($problem->note));

            $items[] = [
                'problem_id' => $id,
                'knowledge' => $knowledge,
                'question' => $quiz['question'] ?? '',
                'options' => $quiz['options'] ?? [],
                'correct_index' => $quiz['correct_index'] ?? 0,
                'explanation' => $quiz['explanation'] ?? '',
            ];
        }

        $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = count($items);

        return <<<TEXT
以下の {$itemCount} 件の生成問題を検証してください。
knowledge を唯一の正解根拠として照合してください。

{$json}

各問題について problem_id, passed, fatal_errors, confidence を返してください。
TEXT;
    }

    private function buildRegeneratePrompt(
        Collection $problems,
        Collection $digests,
        array      $failingItems,
        int        $count
    ): string
    {
        $items = $problems->map(function (Problem $p) use ($digests, $failingItems) {
            $context = $this->buildProblemContext($p, $digests);
            $errors = $failingItems[$p->id]['fatal_errors'] ?? [];
            if (!empty($errors)) {
                $context['previous_errors'] = $errors;
            }
            return $context;
        })->values()->toArray();

        $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();

        return <<<TEXT
以下の {$itemCount} 件の問題は品質基準を満たしませんでした。
previous_errors を参照して改善し、{$count}択問題を再生成してください。

{$json}

各問題について problem_id, question, options（{$count}要素の配列）, correct_index, explanation を返してください。
TEXT;
    }

    private function buildProblemContext(Problem $p, Collection $digests): array
    {
        $digest = $digests->get($p->id);
        $knowledge = ($digest && !$digest->isEmpty())
            ? $digest->toPromptText()
            : NoteParser::toPromptText(NoteParser::parse($p->note));

        return [
            'problem_id' => $p->id,
            'subject' => $p->subject?->name ?? '',
            'sub_category' => $p->subCategory?->name ?? null,
            'question_ref' => $p->question_ref,
            'knowledge' => $knowledge,
        ];
    }

    // ── スキーマ ─────────────────────────────────────────────────────────────

    private function buildResponseSchema(int $count): array
    {
        return [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'problem_id' => ['type' => 'INTEGER'],
                    'question' => ['type' => 'STRING'],
                    'options' => [
                        'type' => 'ARRAY',
                        'items' => ['type' => 'STRING'],
                    ],
                    'correct_index' => ['type' => 'INTEGER'],
                    'explanation' => ['type' => 'STRING'],
                ],
                'required' => ['problem_id', 'question', 'options', 'correct_index', 'explanation'],
            ],
        ];
    }

    private function buildValidationResponseSchema(): array
    {
        return [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'problem_id' => ['type' => 'INTEGER'],

                    'passed' => ['type' => 'BOOLEAN'],

                    // Validator が判断した正しい index
                    'expected_correct_index' => ['type' => 'INTEGER'],

                    'fatal_errors' => [
                        'type' => 'ARRAY',
                        'items' => ['type' => 'STRING'],
                    ],

                    'confidence' => ['type' => 'INTEGER'],
                ],
                'required' => [
                    'problem_id',
                    'passed',
                    'expected_correct_index',
                    'fatal_errors',
                    'confidence',
                ],
            ],
        ];
    }

    // ── ヘルパー ─────────────────────────────────────────────────────────────

    private function toQuiz(array $item): array
    {
        $quiz = [
            'question' => $item['question'] ?? '',
            'options' => $item['options'] ?? [],
            'correct_index' => (int)($item['correct_index'] ?? 0),
            'explanation' => $item['explanation'] ?? '',
        ];

        return $this->shuffleOptions($quiz);
    }

    private function shuffleOptions(array $quiz): array
    {
        $options = $quiz['options'];
        $correct = $quiz['correct_index'];

        $correctText = $options[$correct];

        shuffle($options);

        $quiz['options'] = $options;
        $quiz['correct_index'] = array_search($correctText, $options, true);

        return $quiz;
    }

    private function containsKnowledgeKeyword(
        string $explanation,
        string $knowledge
    ): bool
    {
        preg_match_all('/\[KeyWord\](.+)/u', $knowledge, $m);

        foreach ($m[1] ?? [] as $keyword) {
            $keyword = trim($keyword);
            if ($keyword !== '' && str_contains($explanation, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
