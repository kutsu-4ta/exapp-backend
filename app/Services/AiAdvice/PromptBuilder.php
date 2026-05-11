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
                $base . "You are a data-driven study strategist for the Chusho Shindanshi exam ({$ctx->profile->goal}). " .
                "Using the comprehensive data provided, output a concrete action plan: (1) which subject to prioritize next and why, " .
                "(2) which mistake type to target, (3) specific time allocation. " .
                "Cross-reference monthly vs all-time subject hours, neglected subjects, and mistake patterns. Tone: Analytical and decisive.",

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
        return implode("\n", [
            "[Monthly Summary - {$ctx->year}/{$ctx->month}]",
            "Study days: {$ctx->studyDays} | Total: {$ctx->totalMonthMinutes}min",
            "This week: {$ctx->thisWeekMinutes}min / target {$ctx->profile->weeklyTargetMinutes}min (remain: {$ctx->weeklyRemainingMinutes()}min)",
            "",
            "[Subject Hours - This Month]",
            $this->formatSubjectMinutes($ctx->subjectMinutes),
            "",
            "[Subject Hours - All Time]",
            $this->formatSubjectMinutes($ctx->allTimeSubjectMinutes),
            "",
            "[Neglected Subjects (no study in 30+ days)]",
            empty($ctx->untouchedSubjects) ? '(none)' : implode(', ', $ctx->untouchedSubjects),
            "",
            "[Weak Problems by Subject (count)]",
            $this->formatWeakSubjects($ctx->weakSubjects),
            "",
            "[Mistake Patterns by Subject]",
            $this->formatFailureTypes($ctx->failureTypesBySubject),
            "",
            "[Available Materials]",
            $this->formatMaterials($ctx->materials),
        ]);
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

    private function formatFailureTypes(array $failureTypesBySubject): string
    {
        if (empty($failureTypesBySubject)) {
            return '(none)';
        }

        return implode(' | ', array_map(
            fn ($subject, $types) => "{$subject}[" . implode(', ', array_map(
                fn ($type, $count) => "{$type}:{$count}",
                array_keys($types),
                $types,
            )) . ']',
            array_keys($failureTypesBySubject),
            $failureTypesBySubject,
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
