<?php

namespace App\UseCases\MorningBugfix;

use App\Models\KnowledgeDigest;
use App\Models\Problem;
use App\Models\ProblemQuiz;
use App\Services\GeminiService;
use App\Services\NoteParser;
use Illuminate\Support\Collection;

class GenerateMorningQuizUseCase
{
    private const EXAM_NAME = '中小企業診断士試験';
    private const FOCUS_THEME = '定義理解';
    private const OPTION_COUNT = 4;
    private const SCORE_THRESHOLD = 20; // これ未満を Gemini 検閲対象とする

    public function __construct(
        private readonly GeminiService $gemini,
    ) {
    }

    /**
     * @param Collection<Problem> $problems SelectMorningProblemsUseCase の結果
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

        $allProblems = $problems;

        // ─── 1. knowledge_digests を取得し、不足分はオンデマンドでパース ──
        $digests = KnowledgeDigest::whereIn('problem_id', $allProblems->pluck('id'))
            ->get()
            ->keyBy('problem_id');

        // notes に構造化ハッシュタグがあるが DB に digest がない問題を一時補完
        foreach ($allProblems as $p) {
            if ($digests->has($p->id) && !$digests->get($p->id)->isEmpty()) {
                continue;
            }
            if (!NoteParser::hasStructuredContent($p->note)) {
                continue;
            }
            $parsed = NoteParser::parse($p->note);
            if (empty($parsed)) {
                continue;
            }
            $digest = new KnowledgeDigest([
                'problem_id'   => $p->id,
                'definitions'  => isset($parsed['definition'])  ? [$parsed['definition']]  : null,
                'formulas'     => isset($parsed['formula'])      ? [$parsed['formula']]      : null,
                'keywords'     => isset($parsed['keyword'])      ? [$parsed['keyword']]      : null,
                'pitfalls'     => isset($parsed['pitfall'])      ? [$parsed['pitfall']]      : null,
                'examples'     => isset($parsed['example'])      ? [$parsed['example']]      : null,
                'relations'    => isset($parsed['relation'])     ? [$parsed['relation']]     : null,
                'memory_hooks' => isset($parsed['memory_hook'])  ? [$parsed['memory_hook']]  : null,
            ]);
            if (!$digest->isEmpty()) {
                $digests->put($p->id, $digest);
            }
        }

        $problems = $allProblems->filter(
            fn($p) => $digests->has($p->id) && !$digests->get($p->id)->isEmpty()
        );

        $result = [];

        if ($problems->isNotEmpty()) {
            // ─── 2. PHP 側で quiz コアを組み立て ────────────────────────
            $quizCores = $problems->mapWithKeys(
                fn($p) => [$p->id => $this->buildQuizCore($digests->get($p->id))]
            );

            // ─── 3. Gemini で quiz 生成 ──────────────────────────────────
            $raw = $this->gemini->generateJson(
                $this->buildSystemInstruction($exam, $theme, $count),
                $this->buildUserPrompt($problems, $digests, $quizCores, $count),
                $this->buildResponseSchema($count),
                $apiKey,
                $model
            );

            // ─── 4. PHP 静的バリデーション ＋ スコアリング ──────────────
            [$staticPassing, $failing] = $this->applyStaticValidation($raw, $count);

            $highScore = [];
            $lowScore  = [];

            foreach ($staticPassing as $id => $item) {
                $score = $this->scoreQuiz($item, $digests->get($id));
                if ($score >= self::SCORE_THRESHOLD) {
                    $highScore[$id] = $item;
                } else {
                    $lowScore[$id] = $item;
                }
            }

            $result = array_map(fn($item) => $this->toQuiz($item), $highScore);

            // ─── 5. スコア不足 + 静的失格 → Gemini で検閲＋修正（同一リクエスト）
            $toReview = $lowScore + $failing;

            if (!empty($toReview)) {
                $reviewProblems = $problems->filter(fn($p) => isset($toReview[$p->id]));

                $reviewRaw = $this->validateAndRegenerateRaw(
                    $toReview, $reviewProblems, $digests, $quizCores, $apiKey, $model, $count
                );

                [$reviewPassing] = $this->applyStaticValidation($reviewRaw, $count);

                foreach ($reviewPassing as $id => $item) {
                    if ($this->scoreQuiz($item, $digests->get($id)) >= self::SCORE_THRESHOLD) {
                        $result[$id] = $this->toQuiz($item);
                    }
                }
            }
        }

        // ─── 6. まだカバーされていない全問題を problem_quizzes でフォールバック
        $uncoveredIds = $allProblems->pluck('id')
            ->diff(array_keys($result))
            ->values()
            ->all();

        if (!empty($uncoveredIds)) {
            $saved = ProblemQuiz::whereIn('problem_id', $uncoveredIds)
                ->where('quiz_type', 'multiple_choice')
                ->orderByDesc('created_at')
                ->get()
                ->keyBy('problem_id');

            foreach ($uncoveredIds as $id) {
                $quiz = $saved->get($id);
                if ($quiz === null || $quiz->correct_index === null) {
                    continue;
                }
                $result[$id] = $this->fromProblemQuiz($quiz);
            }
        }

        return $result;
    }

    // ── quiz コア組み立て（PHP 側）────────────────────────────────────────────

    private function buildQuizCore(KnowledgeDigest $digest): array
    {
        $definitions = array_values(array_filter($digest->definitions ?? []));
        $keywords = array_values(array_filter($digest->keywords ?? []));
        $pitfalls = array_values(array_filter($digest->pitfalls ?? []));
        $relations = array_values(array_filter($digest->relations ?? []));

        // 正答ヒント：最初の定義文を使用
        $answerHint = $definitions[0] ?? null;

        // 誤答候補：混同しやすい誤解（pitfalls）を優先し、関連概念・重要語を補完
        $distractorCandidates = array_values(array_filter(
            array_unique(array_merge(
                array_slice($pitfalls, 0, 2),
                array_slice($relations, 0, 2),
                array_slice($keywords, 0, 2),
            )),
            fn($x) => !str_contains($answerHint ?? '', $x)
        ));

        // 混同ポイント：間違えやすい点から
        $commonMistakes = array_slice($pitfalls, 0, 2);

        return array_filter([
            'answer_hint' => $answerHint,
            'distractor_candidates' => $distractorCandidates ?: null,
            'common_mistakes' => $commonMistakes ?: null,
        ], fn($v) => $v !== null);
    }

    // ── スコアリング ───────────────────────────────────────────────────────────

    private function scoreQuiz(array $item, ?KnowledgeDigest $digest): int
    {
        $score = 100;

        $question = $item['question'] ?? '';
        $explanation = $item['explanation'] ?? '';

        // 問題文が定義問題の形式でない
        if (!preg_match('/定義として.{0,20}適切|に関する記述として.{0,20}適切/u', $question)) {
            $score -= 20;
        }

        // 説明文が短すぎる
        if (mb_strlen($explanation) < 30) {
            $score -= 25;
        }

        // 説明文に知識の用語が含まれていない
        if ($digest !== null) {
            $terms = array_values(array_filter(array_merge(
                $digest->definitions ?? [],
                $digest->keywords ?? [],
                $digest->pitfalls ?? [],
                $digest->relations ?? [],
            )));

            $matched = false;
            foreach ($terms as $term) {
                $len = mb_strlen($term);
                $snippet = mb_substr($term, 0, max(3, (int)floor($len * 0.4)));
                if ($snippet !== '' && str_contains($explanation, $snippet)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $score -= 20;
            }
        }

        return max(0, $score);
    }

    // ── 検閲＋修正（2回目コール、同一リクエスト）──────────────────────────────

    private function validateAndRegenerateRaw(
        array      $items,
        Collection $problems,
        Collection $digests,
        Collection $quizCores,
        ?string    $apiKey,
        ?string    $model,
        int        $count
    ): array
    {
        return $this->gemini->generateJson(
            $this->buildReviewSystemInstruction($count),
            $this->buildReviewPrompt($items, $problems, $digests, $quizCores, $count),
            $this->buildReviewResponseSchema($count),
            $apiKey,
            $model
        );
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

            $errors = $this->staticErrors($item, $count);

            if (empty($errors)) {
                $passing[$id] = $item;
            } else {
                $failing[$id] = array_merge($item, ['fatal_errors' => $errors]);
            }
        }

        return [$passing, $failing];
    }

    private function staticErrors(array $item, int $count): array
    {
        $errors = [];
        $options = $item['options'] ?? [];
        $correctIndex = (int)($item['correct_index'] ?? -1);

        if (count($options) !== $count) {
            $errors[] = 'options count mismatch';
        }

        if ($correctIndex < 0 || $correctIndex >= count($options)) {
            $errors[] = 'correct_index out of range';
        }

        $normalized = array_map(
            fn($o) => preg_replace('/\s+/u', '', trim($o)),
            $options
        );
        if (count(array_unique($normalized)) !== count($normalized)) {
            $errors[] = 'duplicate options';
        }

        if (!empty($options)) {
            $lengths = array_map(fn($o) => mb_strlen($o), $options);
            if (max($lengths) - min($lengths) > 25) {
                $errors[] = 'option length imbalance';
            }
        }

        return $errors;
    }

    // ── プロンプト構築 ────────────────────────────────────────────────────────

    private function buildSystemInstruction(string $exam, string $theme, int $count): string
    {
        $maxIndex = $count - 1;

        return <<<TEXT
        あなたは{$exam}の問題生成AIです。
        「{$theme}」を問う{$count}択選択問題を日本語で生成してください。

        【最重要ルール】
        knowledge 内の [定義] セクションを唯一の真実として扱うこと。
        [定義] がない場合は [公式] を代わりに使用すること。
        問題は「用語の定義そのもの」を問うこと。

        問題文は必ず次の形式に近づけること：
        「◯◯に関する記述として最も適切なものはどれか」
        または
        「◯◯の定義として最も適切なものはどれか」

        【quiz_core の使い方】
        - answer_hint：正答選択肢の根拠として使用する
        - distractor_candidates：誤答選択肢の材料として参照する
        - common_mistakes：誤答が「定義と混同しやすい概念」になるよう誘導する

        【correct_index の決定手順（必ず守ること）】
        1. knowledge の [定義] と quiz_core.answer_hint を確認する
        2. options の中から [定義] の言い換えとなる選択肢を1つ特定する
        3. その選択肢のインデックス（0始まり、最大{$maxIndex}）を correct_index にセットする
        4. 自己検証：options[correct_index] が [定義] と意味的に一致しているか確認する
        5. 一致していない場合は correct_index を修正してから出力する

        【選択肢ルール】
        - 正答は [定義] の本文を言い換えたもの
        - 誤答は [定義] と紛らわしい別概念・別制度・別要件にする
        - 属性・効果・具体例・制度要件を正答にしてはいけない
        - 全選択肢を同じ粒度・同じ文体にする
        - 選択肢の長さを揃える（文字数差25字以内）
        - 選択肢同士で意味が重複しない

        【explanation ルール】
        - 正答が正しい理由を knowledge の定義内容を引用しながら説明する
        - 各誤答がなぜ誤りか、何と混同しやすいかを説明する
        - knowledge 内の情報のみを使い、外部知識を追加しない
        - 選択肢を指す際は必ず「正答」「誤答の選択肢」と表現すること
        - 「選択肢1」「①」「A」などの番号・記号は一切使わないこと

        【禁止】
        - [定義] 以外を正答にする
        - 複数の正答が存在する問題を作る
        - 曖昧な表現を使う
        - knowledge にない情報を追加する
        TEXT;
    }

    private function buildReviewSystemInstruction(int $count): string
    {
        $maxIndex = $count - 1;

        return <<<TEXT
        あなたは問題品質検証・修正AIです。
        knowledge の [定義] を唯一の真実として、渡された各選択問題を検証してください。

        【検証基準（以下のいずれかに該当すれば passed=false）】
        - 問題文が [定義] を問うていない
        - 正答選択肢が [定義] と一致しない（correct_index mismatch）
        - 誤答選択肢が [定義] と一致してしまっている（複数正答）
        - explanation が正答と矛盾している
        - 選択肢に重複がある

        【出力ルール】
        - passed=true：元の quiz 内容をそのまま返してよい
        - passed=false：previous_errors と knowledge を参考に {$count} 択問題を修正して返す
        - quiz_core.answer_hint を正答の根拠として使用する
        - quiz_core.distractor_candidates を誤答の材料として使用する

        【correct_index の決定手順（必ず守ること）】
        1. [定義] と quiz_core.answer_hint を確認する
        2. options の中から [定義] の言い換えとなる選択肢を1つ特定する
        3. その選択肢のインデックス（0始まり、最大{$maxIndex}）を correct_index にセットする
        4. 自己検証：options[correct_index] が [定義] と意味的に一致しているか確認する
        5. 一致していない場合は correct_index を修正してから出力する
        TEXT;
    }

    private function buildUserPrompt(
        Collection $problems,
        Collection $digests,
        Collection $quizCores,
        int        $count
    ): string
    {
        $items = $problems->map(function (Problem $p) use ($digests, $quizCores) {
            $context = $this->buildProblemContext($p, $digests);
            $context['quiz_core'] = $quizCores->get($p->id, []);
            return $context;
        })->values()->toArray();

        $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();

        return <<<TEXT
        以下の {$itemCount} 件の knowledge に対して、それぞれ [定義] を問う {$count} 択問題を生成してください。

        quiz_core.answer_hint を正答の根拠として使用し、
        quiz_core.distractor_candidates を誤答の材料として参照し、
        quiz_core.common_mistakes が存在する場合は誤答が混同しやすいポイントになるよう誘導してください。

        {$json}

        各問題について problem_id, question, options（{$count} 要素の配列）, correct_index, explanation を返してください。
        TEXT;
    }

    private function buildReviewPrompt(
        array      $items,
        Collection $problems,
        Collection $digests,
        Collection $quizCores,
        int        $count
    ): string
    {
        $byId = $problems->keyBy('id');
        $rows = [];

        foreach ($items as $id => $quiz) {
            $problem = $byId->get($id);
            if (!$problem) {
                continue;
            }

            $row = [
                'problem_id' => $id,
                'subject' => $problem->subject?->name ?? '',
                'sub_category' => $problem->subCategory?->name ?? null,
                'question_ref' => $problem->question_ref,
                'knowledge' => $digests->get($id)?->toPromptText() ?? '',
                'quiz_core' => $quizCores->get($id, []),
                'original_quiz' => [
                    'question' => $quiz['question'] ?? '',
                    'options' => $quiz['options'] ?? [],
                    'correct_index' => $quiz['correct_index'] ?? 0,
                    'explanation' => $quiz['explanation'] ?? '',
                ],
            ];

            $errors = $quiz['fatal_errors'] ?? [];
            if (!empty($errors)) {
                $row['previous_errors'] = $errors;
            }

            $rows[] = $row;
        }

        $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = count($rows);

        return <<<TEXT
        以下の {$itemCount} 件の問題を検証し、問題があれば修正した問題を返してください。
        問題がない場合は元の内容をそのまま返してください。
        knowledge の [定義] を唯一の正解根拠として照合してください。

        {$json}

        各問題について problem_id, passed, question, options（{$count} 要素の配列）, correct_index, explanation を返してください。
        TEXT;
    }

    private function buildProblemContext(Problem $p, Collection $digests): array
    {
        return [
            'problem_id' => $p->id,
            'subject' => $p->subject?->name ?? '',
            'sub_category' => $p->subCategory?->name ?? null,
            'question_ref' => $p->question_ref,
            'instruction' => [
                'use_definition_as_truth',
                'definition_only',
                'ignore_examples',
                'ignore_memory_hooks',
                'ignore_pitfalls_as_correct_answer',
            ],
            'knowledge' => $digests->get($p->id)?->toPromptText() ?? '',
        ];
    }

    // ── スキーマ ──────────────────────────────────────────────────────────────

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
                        'minItems' => $count,
                        'maxItems' => $count,
                    ],
                    'correct_index' => ['type' => 'INTEGER'],
                    'explanation' => ['type' => 'STRING'],
                ],
                'required' => ['problem_id', 'question', 'options', 'correct_index', 'explanation'],
            ],
        ];
    }

    private function buildReviewResponseSchema(int $count): array
    {
        return [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'problem_id' => ['type' => 'INTEGER'],
                    'passed' => ['type' => 'BOOLEAN'],
                    'question' => ['type' => 'STRING'],
                    'options' => [
                        'type' => 'ARRAY',
                        'items' => ['type' => 'STRING'],
                        'minItems' => $count,
                        'maxItems' => $count,
                    ],
                    'correct_index' => ['type' => 'INTEGER'],
                    'explanation' => ['type' => 'STRING'],
                ],
                'required' => ['problem_id', 'passed', 'question', 'options', 'correct_index', 'explanation'],
            ],
        ];
    }

    // ── ヘルパー ──────────────────────────────────────────────────────────────

    private function toQuiz(array $item): array
    {
        $quiz = [
            'question' => $item['question'] ?? '',
            'options' => $item['options'] ?? [],
            'correct_index' => (int)($item['correct_index'] ?? 0),
            'explanation' => $item['explanation'] ?? '',
        ];

        $quiz['explanation'] = $this->sanitizeExplanation($quiz['explanation'], $quiz['correct_index']);

        return $this->shuffleOptions($quiz);
    }

    private function fromProblemQuiz(ProblemQuiz $quiz): array
    {
        $correctIndex = (int)$quiz->correct_index;

        return $this->shuffleOptions([
            'question' => $quiz->question,
            'options' => $quiz->options ?? [],
            'correct_index' => $correctIndex,
            'explanation' => $this->sanitizeExplanation($quiz->explanation, $correctIndex),
        ]);
    }

    private function sanitizeExplanation(string $text, int $correctIndex): string
    {
        $correctNumber = $correctIndex + 1; // Gemini は 1-based で番号を使う

        // ①②③④ → 正答 or 誤答の選択肢
        $circleMap = ['①' => 1, '②' => 2, '③' => 3, '④' => 4];
        $text = preg_replace_callback('/[①②③④]/u', function (array $m) use ($correctNumber, $circleMap) {
            return ($circleMap[$m[0]] ?? 0) === $correctNumber ? '正答' : '誤答の選択肢';
        }, $text);

        // 選択肢X / 第X選択肢（数字・アルファベット、全角半角）→ 正答 or 誤答の選択肢
        $text = preg_replace_callback(
            '/(?:選択肢|第)([0-9０-９]+|[A-DＡ-Ｄ])(?:選択肢)?/u',
            function (array $m) use ($correctNumber) {
                $raw = mb_convert_kana($m[1], 'na'); // 全角→半角
                $num = is_numeric($raw)
                    ? (int)$raw
                    : (ord(strtoupper($raw)) - ord('A') + 1); // A=1, B=2...
                return $num === $correctNumber ? '正答' : '誤答の選択肢';
            },
            $text
        );

        // [定義] リテラルが explanation に漏れてくる場合を後処理で除去
        return str_replace('[定義]', '定義', $text);
    }

    private function shuffleOptions(array $quiz): array
    {
        $indexed = [];

        foreach ($quiz['options'] as $i => $text) {
            $indexed[] = [
                'text' => $text,
                'is_correct' => $i === $quiz['correct_index'],
            ];
        }

        shuffle($indexed);

        $quiz['options'] = array_column($indexed, 'text');

        foreach ($indexed as $i => $row) {
            if ($row['is_correct']) {
                $quiz['correct_index'] = $i;
                break;
            }
        }

        return $quiz;
    }
}
