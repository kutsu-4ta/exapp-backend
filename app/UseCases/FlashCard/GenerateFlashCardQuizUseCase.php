<?php

namespace App\UseCases\FlashCard;

use App\Models\Problem;
use App\Services\GeminiService;
use Illuminate\Support\Collection;

class GenerateFlashCardQuizUseCase
{
    private const EXAM_NAME = '中小企業診断士試験';

    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * @param  Collection<Problem> $problems
     * @return array  問題IDをキーとした quiz データ配列（options は常に空配列）
     */
    public function __invoke(Collection $problems, ?string $apiKey = null, ?string $model = null, bool $formulaOnly = false): array
    {
        $systemInstruction = $this->buildSystemInstruction($formulaOnly);
        $userPrompt        = $this->buildUserPrompt($problems, $formulaOnly);
        $responseSchema    = $this->buildResponseSchema();

        $raw = $this->gemini->generateJson($systemInstruction, $userPrompt, $responseSchema, $apiKey, $model);

        $quizByProblemId = [];
        foreach ($raw as $item) {
            $id = (int) ($item['problem_id'] ?? 0);
            if ($id > 0) {
                $quizByProblemId[$id] = [
                    'question'      => $item['question']     ?? '',
                    'options'       => [],
                    'correct_index' => 0,
                    'explanation'   => $item['explanation']  ?? '',
                ];
            }
        }

        return $quizByProblemId;
    }

    private function buildSystemInstruction(bool $formulaOnly): string
    {
        $exam = self::EXAM_NAME;

        if ($formulaOnly) {
            return <<<TEXT
あなたは{$exam}の問題生成AIです。
ユーザーが苦手とする公式・定義問題から、単語カード形式の問答を生成します。

【必須ルール】
1. question（表面）: 「〜の計算式を答えよ」「〜の公式を述べよ」「〜を定義せよ」など、公式・定義を問う質問文を1文で作成する
2. explanation（裏面）: 正確な公式・定義を数式や箇条書きで簡潔に記述する。user_memoにある情報を最優先で使用し、ハルシネーションを防ぐこと
3. すべての文章は日本語で生成する
4. problem_idは必ず元の整数値を使用する
TEXT;
        }

        return <<<TEXT
あなたは{$exam}の問題生成AIです。
ユーザーが苦手とする問題データから、単語カード形式の問答を生成します。

【必須ルール】
1. question（表面）: 「〜とは何か？」「〜を説明せよ」「〜の特徴を答えよ」など、定義・概念を問う質問文を1文で作成する
2. explanation（裏面）: user_memoにある定義・キーワードを最優先で使用し、正確な説明を2〜3文で簡潔に記述する。ハルシネーション厳禁
3. すべての文章は日本語で生成する
4. problem_idは必ず元の整数値を使用する
TEXT;
    }

    private function buildUserPrompt(Collection $problems, bool $formulaOnly): string
    {
        $items = $problems->map(fn (Problem $p) => [
            'problem_id'   => $p->id,
            'subject'      => $p->subject?->name ?? '',
            'sub_category' => $p->subCategory?->name ?? null,
            'question_ref' => $p->question_ref,
            'user_memo'    => $p->note,
        ])->values()->toArray();

        $json      = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemCount = $problems->count();
        $mode      = $formulaOnly ? '公式チェックカード' : '単語カード';

        return <<<TEXT
以下の {$itemCount} 件の苦手問題に対して、それぞれ{$mode}形式の問答を生成してください。

{$json}

各問題について problem_id, question（カード表面）, explanation（カード裏面）を返してください。
TEXT;
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
                ],
                'required' => ['problem_id', 'question', 'explanation'],
            ],
        ];
    }
}
