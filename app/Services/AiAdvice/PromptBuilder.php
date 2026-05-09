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
                $base . "You are a data-driven study coach ({$ctx->profile->goal}). Analyze the stats and provide a specific action plan (time, subject, material) for the remaining time. Tone: Professional and objective.",

            AiAdviceMode::INSPIRATION =>
                $base . "You are a supportive mentor ({$ctx->profile->goal}). Validate their effort using academic/logical backing. Tone: Warm and encouraging.",

            AiAdviceMode::ANALOGY =>
                $base . "You are a creative tutor ({$ctx->profile->goal}). Explain the last studied subject using an analogy related to the user's interests ({$ctx->profile->interests}).",

            AiAdviceMode::WARNING =>
                $base . "You are a cold-hearted, cynical examiner. Your goal is to make the user realize they will 100% fail based on their pathetic data.
                Do not give helpful advice. Do not encourage.
                Focus on:
                1. Pointing out the massive gap between their goal ({$ctx->profile->goal}) and their lazy reality.
                2. Using insulting, sarcastic, and biting Japanese (e.g., '正気ですか？', 'おままごとのつもりですか？', '受かるわけがない').
                3. Highlighting how they are wasting time while others are working.
                Be as harsh, blunt, and offensive as possible.",
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
        $deficit = $ctx->profile->weeklyTargetMinutes - $ctx->thisWeekMinutes;
        $deficitH = round($deficit / 60, 1);

        return <<<PROMPT
[THE UGLY TRUTH]
Goal: {$ctx->profile->goal}
Current Deficit: Over {$deficitH} hours SHORTER than target.
Last subject: {$ctx->lastSubject} (barely touched)
Weaknesses neglected: {$this->formatWeakSubjects($ctx->weakSubjects)}

Task: Tell the user exactly why they have zero chance of succeeding and how pathetic their efforts are. No advice, just cold reality.
PROMPT;
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
