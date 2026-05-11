<?php

namespace App\UseCases\MorningBugfix;

use App\Models\Problem;
use App\Services\GeminiService;
use Illuminate\Support\Collection;

class GenerateMorningQuizUseCase
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * @param  Collection<Problem>  $problems
     * @return array  問題IDをキーとした quiz データ配列
     */
    public function __invoke(Collection $problems, ?string $apiKey = null): array
    {
        $systemInstruction = $this->buildSystemInstruction();
        $userPrompt        = $this->buildUserPrompt($problems);
        $responseSchema    = $this->buildResponseSchema();

        $raw = $this->gemini->generateJson($systemInstruction, $userPrompt, $responseSchema, $apiKey);

        // problem_id をキーに変換して返す
        $quizByProblemId = [];
        foreach ($raw as $item) {
            $id = (int) ($item['problem_id'] ?? 0);
            if ($id > 0) {
                $quizByProblemId[$id] = [
                    'question'      => $item['question']      ?? '',
                    'options'       => $item['options']        ?? [],
                    'correct_index' => (int) ($item['correct_index'] ?? 0),
                    'explanation'   => $item['explanation']    ?? '',
                ];
            }
        }

        return $quizByProblemId;
    }

    // ----------------------------------------------------------------

    private function buildSystemInstruction(): string
    {
        return <<<'TEXT'
あなたは中小企業診断士試験の「定義理解」特化型問題生成AIです。
ユーザーが実際に間違えた苦手問題データを基に、定義の本質を問う4択選択問題を生成します。

【必須ルール】
1. 選択肢は、単なる正誤ではなく、「使用貸借」「賃貸借」など混同しやすい類似定義をあえて混ぜて、ユーザーの論理的解釈力をテストしてください。消去法ではなく「定義の理解」を強制する良問にしてください。
2. explanationには、user_memoにある定義・キーワードを必ず組み込み、正しい定義を明示してください（ハルシネーション防止）。
3. すべての文章は日本語で生成してください。
4. correct_indexは0〜3の整数で返してください。
TEXT;
    }

    private function buildUserPrompt(Collection $problems): string
    {
        $items = $problems->map(fn (Problem $p) => [
            'problem_id'   => $p->id,
            'subject'      => $p->subject?->name ?? '',
            'sub_category' => $p->subCategory?->name ?? null,
            'question_ref' => $p->question_ref,
            'user_memo'    => $p->note,
        ])->values()->toArray();

        $json  = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $count = $problems->count();

        return <<<TEXT
以下の {$count} 件の苦手問題（定義ミス）に対して、それぞれ定義理解を問う4択問題を生成してください。

{$json}

各問題について problem_id, question, options（4要素の配列）, correct_index（0〜3）, explanation を返してください。
TEXT;
    }

    private function buildResponseSchema(): array
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
                ],
                'required' => ['problem_id', 'question', 'options', 'correct_index', 'explanation'],
            ],
        ];
    }
}
