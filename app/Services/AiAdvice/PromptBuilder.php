<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class PromptBuilder
{
    public function systemInstruction(AiAdviceMode $mode, AdviceContext $ctx): string
    {
        // 共通の制約：直接メッセージのみを返す、日本語、簡潔さ
        $base = "Respond directly with a concise Japanese message for the user. Do not use JSON or code blocks. ";

        return match ($mode) {
            AiAdviceMode::ANALYSIS =>
                $base . "You are a data-driven study coach. Analyze the stats and provide a specific action plan (time, subject, material) for the remaining time. Tone: Professional and objective.",

            AiAdviceMode::INSPIRATION =>
                $base . "You are a supportive mentor. Validate their effort using academic/logical backing. Tone: Warm and encouraging.",

            AiAdviceMode::ANALOGY =>
                $base . "You are a creative tutor. Explain the last studied subject using an analogy related to the user's interests ({$ctx->profile->interests}).",

            AiAdviceMode::WARNING =>
                $base . "You are a strict exam prep coach. Coldly point out deficits and give an immediate, mandatory study task. Tone: Direct and unsparing.",
        };
    }

    public function userPrompt(AiAdviceMode $mode, AdviceContext $ctx): string
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS    => $this->analysisPrompt($ctx),
            AiAdviceMode::INSPIRATION => $this->inspirationPrompt($ctx),
            AiAdviceMode::ANALOGY     => $this->analogyPrompt($ctx),
            AiAdviceMode::WARNING     => $this->warningPrompt($ctx),
        };
    }

    private function analysisPrompt(AdviceContext $ctx): string
    {
        return "Goal: {$ctx->profile->weeklyTargetMinutes}m | Actual: {$ctx->thisWeekMinutes}m | Remain: {$ctx->weeklyRemainingMinutes()}m\n" .
            "Stats: {$this->formatSubjectMinutes($ctx->subjectMinutes)}\n" .
            "Weak: {$this->formatWeakSubjects($ctx->weakSubjects)}\n" .
            "Materials: {$this->formatMaterials($ctx->materials)}";
    }

    private function inspirationPrompt(AdviceContext $ctx): string
    {
        return "Streak: {$ctx->currentStreak}d | Month: {$ctx->studyDays}d ({$ctx->totalMonthMinutes}m)\n" .
            "Recent: {$ctx->lastSubject}\n" .
            "Stats: {$this->formatSubjectMinutes($ctx->subjectMinutes)}";
    }

    private function analogyPrompt(AdviceContext $ctx): string
    {
        return "Interest: {$ctx->profile->interests} | Occupation: {$ctx->profile->occupation}\n" .
            "Last studied: {$ctx->lastSubject}\n" .
            "Weakness: {$this->formatWeakSubjects($ctx->weakSubjects)}";
    }

    private function warningPrompt(AdviceContext $ctx): string
    {
        return "Deficit: " . ($ctx->profile->weeklyTargetMinutes - $ctx->thisWeekMinutes) . "m\n" .
            "Last: {$ctx->lastSubject} | Goal: {$ctx->profile->goal}\n" .
            "Neglected Weakness: {$this->formatWeakSubjects($ctx->weakSubjects)}\n" .
            "Materials: {$this->formatMaterials($ctx->materials)}";
    }

    // ----------------------------------------------------------------

    private function formatSubjectMinutes(array $subjectMinutes): string
    {
        if (empty($subjectMinutes)) {
            return '(none)';
        }

        return implode(', ', array_map(
            fn ($name, $min) => "{$name}:{$min}min",
            array_keys($subjectMinutes),
            $subjectMinutes,
        ));
    }

    private function formatWeakSubjects(array $weakSubjects): string
    {
        if (empty($weakSubjects)) {
            return '(none)';
        }

        return implode(', ', array_map(
            fn ($name, $cnt) => "{$name}:{$cnt}",
            array_keys($weakSubjects),
            $weakSubjects,
        ));
    }

    private function formatMaterials(array $materials): string
    {
        if (empty($materials)) {
            return '(none registered)';
        }

        return implode(', ', $materials);
    }
}
