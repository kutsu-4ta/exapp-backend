<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class PromptBuilder
{
    /**
     * モードに応じたシステムプロンプト（AIのペルソナ・制約）を返す。
     */
    public function systemInstruction(AiAdviceMode $mode, AdviceContext $ctx): string
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS => <<<SYS
あなたはユーザーの目標「{$ctx->profile->goal}」を達成するための戦略コーチです。
提供された学習実績（達成率・残り時間・科目別配分）を定量的に分析し、目標から逆算した最も優先度の高い学習項目と、時間確保のための具体的な行動計画を論理的に提示してください。
励ましや感情的な表現は排除し、データに基づく最適解のみを返してください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,

            AiAdviceMode::INSPIRATION => <<<SYS
あなたはユーザーの目標「{$ctx->profile->goal}」に寄り添う、精神的支柱となるメンターです。
ユーザーの継続日数や総学習時間などの「積み上げた実績」に焦点を当て、その努力が目標達成や自己肯定にどう寄与するかを、学術的な論分や論理的な理由を交えて伝えてください。
ユーザーのモチベーションが上がるような前向きなトーンで返してください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,

            AiAdviceMode::ANALOGY => <<<SYS
あなたはユーザーの目標「{$ctx->profile->goal}」と「{$ctx->profile->interests}」の分野に詳しい解説者です。
ユーザーの目標に関わる知識を、「{$ctx->profile->interests}」の構造にマッピングして解説してください。
「{$ctx->profile->interests}」の分野で使うような用語を比喩に使い、「概念の構造」を直感的に理解させてください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,

            AiAdviceMode::INSIGHT => <<<SYS
あなたはユーザーの目標「{$ctx->profile->goal}」を実践する現場の人です。
ユーザーが学習している学術的な知識が、実際にどのような相乗効果を生むか、実務的な洞察を提示してください。
単なる解説に留まらず、学習内容が「現場の武器」に変わる視点を1文で提供してください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,
        };
    }

    /**
     * モードに応じたユーザーパートのプロンプト（データ埋め込み済み）を返す。
     */
    public function userPrompt(AiAdviceMode $mode, AdviceContext $ctx): string
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS    => $this->analysisPrompt($ctx),
            AiAdviceMode::INSPIRATION => $this->inspirationPrompt($ctx),
            AiAdviceMode::ANALOGY     => $this->analogyPrompt($ctx),
            AiAdviceMode::INSIGHT     => $this->insightPrompt($ctx),
        };
    }

    // ----------------------------------------------------------------
    // 各モードのユーザープロンプト
    // ----------------------------------------------------------------

    private function analysisPrompt(AdviceContext $ctx): string
    {
        $subjectLines = $this->formatSubjectMinutes($ctx->subjectMinutes);
        $weakLines    = $this->formatWeakSubjects($ctx->weakSubjects);
        $targetH      = round($ctx->profile->weeklyTargetMinutes / 60, 1);
        $thisWeekH    = round($ctx->thisWeekMinutes / 60, 1);
        $remainH      = round($ctx->weeklyRemainingMinutes() / 60, 1);
        $rate         = $ctx->weeklyAchievementRate();

        return <<<PROMPT
【ユーザープロフィール】
目標: {$ctx->profile->goal}
職業: {$ctx->profile->occupation}

【今週の学習実績（週目標: {$targetH}時間）】
実績: {$thisWeekH}時間（達成率: {$rate}%）
残り: {$remainH}時間

【今月の科目別学習時間（actual_logs）】
{$subjectLines}

【苦手科目（上位）】
{$weakLines}

上記をもとに、残り時間{$remainH}時間をどの科目・時間帯に配置すれば最も効果的かを1文で提案してください。
PROMPT;
    }

    private function inspirationPrompt(AdviceContext $ctx): string
    {
        $subjectLines = $this->formatSubjectMinutes($ctx->subjectMinutes);
        $totalH       = round($ctx->totalMonthMinutes / 60, 1);

        return <<<PROMPT
【ユーザーの学習実績】
連続学習日数（current_streak）: {$ctx->currentStreak}日
今月の総学習時間: {$totalH}時間（{$ctx->studyDays}日）
直近7日: {$ctx->last7DaysMinutes}分
直近の学習科目（subject）: {$ctx->lastSubject}

【今月の科目別時間（actual_logs）】
{$subjectLines}

上記の努力を実績ベースで称え、さらなる一歩を踏み出す言葉をください。
PROMPT;
    }

    private function analogyPrompt(AdviceContext $ctx): string
    {
        $weakLines = $this->formatWeakSubjects($ctx->weakSubjects);

        return <<<PROMPT
【ユーザープロフィール】
職業: {$ctx->profile->occupation}
得意領域: {$ctx->profile->strongAreas}
趣味・興味: {$ctx->profile->interests}

【直近の学習科目（subject）】
{$ctx->lastSubject}

【苦手科目（参考）】
{$weakLines}

「{$ctx->lastSubject}」の重要概念を、{$ctx->profile->occupation}が日常で扱う技術（または物理学・言語学）に1対1でマッピングして説明してください。
PROMPT;
    }

    private function insightPrompt(AdviceContext $ctx): string
    {
        $subjectLines = $this->formatSubjectMinutes($ctx->subjectMinutes);

        return <<<PROMPT
【ユーザープロフィール】
職業: {$ctx->profile->occupation}
興味・嗜好: {$ctx->profile->interests}
得意: {$ctx->profile->strongAreas}

【直近の学習科目（subject）】
{$ctx->lastSubject}

【今月の学習範囲（actual_logs）】
{$subjectLines}

「{$ctx->lastSubject}」で学んだ知識が、{$ctx->profile->occupation}の実務や{$ctx->profile->interests}と意外な形で繋がる洞察を1文で提示してください。
PROMPT;
    }

    // ----------------------------------------------------------------
    // フォーマットヘルパー
    // ----------------------------------------------------------------

    private function formatSubjectMinutes(array $subjectMinutes): string
    {
        if (empty($subjectMinutes)) {
            return '  （学習記録なし）';
        }

        return implode("\n", array_map(
            fn ($name, $min) => sprintf('  %s: %d分（%.1f時間）', $name, $min, $min / 60),
            array_keys($subjectMinutes),
            $subjectMinutes,
        ));
    }

    private function formatWeakSubjects(array $weakSubjects): string
    {
        if (empty($weakSubjects)) {
            return '  （登録なし）';
        }

        return implode("\n", array_map(
            fn ($name, $cnt) => "  {$name}: {$cnt}件",
            array_keys($weakSubjects),
            $weakSubjects,
        ));
    }
}
