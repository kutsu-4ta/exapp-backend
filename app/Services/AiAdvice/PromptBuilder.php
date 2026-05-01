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
あなたはユーザー（職業：{$ctx->profile->occupation}）の目標「{$ctx->profile->goal}」を達成するための戦略コーチです。
提供された学習実績（達成率・残り時間・科目別配分）を定量的に分析し、目標から逆算した最も優先度の高い学習項目と、時間確保のための具体的な行動計画を論理的に提示してください。
励ましや感情的な表現は排除し、データに基づく最適解のみを返してください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,

            AiAdviceMode::INSPIRATION => <<<SYS
あなたはユーザー（職業：{$ctx->profile->occupation}）の目標「{$ctx->profile->goal}」に寄り添う、精神的支柱となるメンターです。
ユーザーの継続日数や総学習時間などの「積み上げた実績」に焦点を当て、その努力が目標達成や自己肯定にどう寄与するかを、学術的な論分や論理的な理由を交えて伝えてください。
ユーザーのモチベーションが上がるような前向きなトーンで返してください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,

            AiAdviceMode::ANALOGY => <<<SYS
あなたはユーザー（職業：{$ctx->profile->occupation}）の目標「{$ctx->profile->goal}」と「{$ctx->profile->interests}」の分野に詳しい解説者です。
ユーザーの目標に関わる知識を、「{$ctx->profile->interests}」の構造にマッピングして解説してください。
「{$ctx->profile->interests}」の分野で使うような用語を比喩に使い、「概念の構造」を直感的に理解させてください。
出力は100〜250文字。前置き、ラベル、挨拶は一切不要です。
SYS,

            AiAdviceMode::WARNING => <<<SYS
あなたは難関試験を見守る極めて厳格で毒舌な先生です。
ユーザー（職業：{$ctx->profile->occupation}）の甘い考えや、不足している学習実績、苦手科目の放置を冷徹に指摘し、「あなたは今のままでは100%落ちる」という危機感を突きつけてください。
敬語で距離感はあるが、オブラートに包んだ表現や励ましは一切禁止。論理的かつ辛辣な言葉で、本気で合格を獲りに行くための緊張感を与えてください。
出力は100〜250文字。前置き、ラベル、挨拶は不要です。
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
            AiAdviceMode::WARNING     => $this->warningPrompt($ctx),
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
//        $rate         = $ctx->weeklyAchievementRate();

        return <<<PROMPT
今週は、週目標{$targetH}時間に対して{$thisWeekH}時間勉強しました。
今月の科目別学習時間は{$subjectLines}です。
苦手なのは{$weakLines}です。
残りの{$remainH}時間の過ごし方のアドバイスがほしい。
どの科目・時間帯に配置すれば最も効果的か教えてほしい。

PROMPT;
    }

    private function inspirationPrompt(AdviceContext $ctx): string
    {
        $subjectLines = $this->formatSubjectMinutes($ctx->subjectMinutes);
        $totalH       = round($ctx->totalMonthMinutes / 60, 1);

        return <<<PROMPT
私は連続で{$ctx->currentStreak}日勉強しています。
今月は{$ctx->studyDays}日で合計{$totalH}時間勉強しました。
直近7日では{$ctx->last7DaysMinutes}分です。
直近の学習科目は{$ctx->lastSubject}です。
科目の割合は{$subjectLines}という感じです。
私、こんなのじゃだめですよね。。。
PROMPT;
    }

    private function analogyPrompt(AdviceContext $ctx): string
    {
        $weakLines = $this->formatWeakSubjects($ctx->weakSubjects);

        return <<<PROMPT
普段は{$ctx->profile->occupation}で仕事してて、最近は{$ctx->lastSubject}を勉強しました。
{$weakLines}か{$ctx->profile->strongAreas}で{$ctx->profile->interests}とかに絡めて楽しい話してほしい。
「{$ctx->lastSubject}」の話を、アナロジーで。
PROMPT;
    }

    private function warningPrompt(AdviceContext $ctx): string
    {
        $targetH   = round($ctx->profile->weeklyTargetMinutes / 60, 1);
        $thisWeekH = round($ctx->thisWeekMinutes / 60, 1);
        $diffH     = $targetH - $thisWeekH;
        $weakLines = $this->formatWeakSubjects($ctx->weakSubjects);
        $lastSubject = $ctx->lastSubject ?: '未着手';

        $status = ($diffH > 0) ? "目標に{$diffH}時間も届いていない惨状" : "時間は確保しているが中身が伴っているか怪しい状態";

        return <<<PROMPT
週目標{$targetH}時間に対し、実績はわずか{$thisWeekH}時間です。
直近で触った科目は「{$lastSubject}」です。
{$weakLines}は苦手なので放置してます。
目標「{$ctx->profile->goal}」を掲げながら、この{$status}です。
やばいけど、まあいけるっしょ。
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
