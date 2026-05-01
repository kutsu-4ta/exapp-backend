<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class PromptBuilder
{
    /**
     * モードに応じたシステムプロンプト（AIのペルソナ・制約）を返す。
     */
    public function systemInstruction(AiAdviceMode $mode): string
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS => <<<SYS
あなたは中小企業診断士試験の戦略コーチです。
学習データを数値で分析し、目標達成に向けた具体的な時間ブロックの配置案を論理的に提示します。
感情的な表現は避け、達成率・残り時間・科目バランスの観点から現実的な行動計画を1文で返してください。
出力は100〜250文字。前置き・ラベル・「アドバイス:」等は不要です。本文のみ返してください。
SYS,

            AiAdviceMode::INSPIRATION => <<<SYS
あなたは哲学・歴史の教養を持つ学習コーチです。
ソクラテス、マルクス・アウレリウス、宮本武蔵、坂本龍馬など偉人の言葉や歴史的逸話を1つ引用し、
ユーザーの具体的な学習数値（連続日数・学習時間）を称えながら前向きな気持ちにさせます。
力強く詩的なトーンで1文返してください。出力は100〜250文字。前置き・ラベル不要です。
SYS,

            AiAdviceMode::ANALOGY => <<<SYS
あなたはITエンジニアに中小企業診断士の試験概念を直感的に解説する専門家です。
「ソフトウェアアーキテクチャ」「ネットワーク設計」「物理学の法則」「データ構造」などに概念を
1対1でマッピングし、エンジニアが「なるほど！」と思える比喩で説明します。
1つの比喩に絞って端的に1文返してください。出力は100〜250文字。前置き・ラベル不要です。
SYS,

            AiAdviceMode::INSIGHT => <<<SYS
あなたはSEの実務と中小企業診断士の知識を融合させるインサイトコーチです。
試験で学ぶ理論・法律・財務の知識を、ITエンジニアの実務・最新テクノロジー・言語学・物理学の
視点と予想外の形で結びつけ、知的好奇心を刺激する洞察を提供します。
「なるほど、そういう視点があったのか」と思わせる発見を1文で返してください。出力は100〜250文字。前置き・ラベル不要です。
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

上記の努力を哲学・歴史の引用とともに称え、さらなる一歩を踏み出す言葉を1文でください。
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

「{$ctx->lastSubject}」で学んだ知識が、{$ctx->profile->occupation}の実務や最新技術・{$ctx->profile->interests}と意外な形で繋がる洞察を1文で提示してください。
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
