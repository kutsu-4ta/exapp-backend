<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class PromptBuilder
{
    /**
     * System instruction (English). Profile fields are pre-translated by DeepLService.
     */
    public function systemInstruction(AiAdviceMode $mode, AdviceContext $ctx): string
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS => <<<SYS
You are a study strategy coach for a user (occupation: {$ctx->profile->occupation}, goal: {$ctx->profile->goal}).
Analyze the provided study data quantitatively. Output a concrete allocation plan that specifies which time slot, subject, material, and duration to use for the remaining study time.
Select materials only from the user's registered list provided in the user message. Do not suggest materials outside that list.
No emotional language or encouragement. Data-driven reasoning only.
All JSON field values must be written in Japanese.
SYS,

            AiAdviceMode::INSPIRATION => <<<SYS
You are a mentor for a user (occupation: {$ctx->profile->occupation}, goal: {$ctx->profile->goal}).
Affirm the significance of their study streak and accumulated results using academic and logical backing.
Output each JSON field with a positive, forward-looking tone.
All JSON field values must be written in Japanese.
SYS,

            AiAdviceMode::ANALOGY => <<<SYS
You are a study coach for a user (occupation: {$ctx->profile->occupation}).
Map the user's recent study content to concepts from "{$ctx->profile->interests}" and output an intuitive analogy that promotes understanding.
Use terminology and structures from "{$ctx->profile->interests}" as the vehicle for explanation.
All JSON field values must be written in Japanese.
SYS,

            AiAdviceMode::WARNING => <<<SYS
You are a strict, blunt exam prep coach.
Coldly analyze the user's (occupation: {$ctx->profile->occupation}, goal: {$ctx->profile->goal}) study deficits and neglected weak subjects.
Output an urgent allocation plan specifying which time slot, subject, material, and duration to act on immediately.
Select materials only from the user's registered list provided in the user message. Do not suggest materials outside that list.
No encouragement or softening. Be direct and unsparing.
All JSON field values must be written in Japanese.
SYS,
        };
    }

    /**
     * User prompt (data-embedded). Labels are in English; subject/material names stay in Japanese.
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

    private function analysisPrompt(AdviceContext $ctx): string
    {
        $targetH  = round($ctx->profile->weeklyTargetMinutes / 60, 1);
        $thisWeekH = round($ctx->thisWeekMinutes / 60, 1);
        $remainH  = round($ctx->weeklyRemainingMinutes() / 60, 1);

        return <<<PROMPT
Weekly target: {$targetH}h | Actual: {$thisWeekH}h | Remaining: {$remainH}h
Monthly by subject: {$this->formatSubjectMinutes($ctx->subjectMinutes)}
Weak subjects: {$this->formatWeakSubjects($ctx->weakSubjects)}
Available materials: {$this->formatMaterials($ctx->materials)}
PROMPT;
    }

    private function inspirationPrompt(AdviceContext $ctx): string
    {
        $totalH = round($ctx->totalMonthMinutes / 60, 1);

        return <<<PROMPT
Streak: {$ctx->currentStreak} days | This month: {$ctx->studyDays} days / {$totalH}h | Last 7 days: {$ctx->last7DaysMinutes} min
Last subject studied: {$ctx->lastSubject}
Monthly breakdown: {$this->formatSubjectMinutes($ctx->subjectMinutes)}
PROMPT;
    }

    private function analogyPrompt(AdviceContext $ctx): string
    {
        return <<<PROMPT
Occupation: {$ctx->profile->occupation} | Interests: {$ctx->profile->interests}
Last subject studied: {$ctx->lastSubject}
Strong areas: {$ctx->profile->strongAreas} | Weak areas: {$this->formatWeakSubjects($ctx->weakSubjects)}
PROMPT;
    }

    private function warningPrompt(AdviceContext $ctx): string
    {
        $targetH   = round($ctx->profile->weeklyTargetMinutes / 60, 1);
        $thisWeekH = round($ctx->thisWeekMinutes / 60, 1);
        $lastSubject = $ctx->lastSubject ?: 'none';

        return <<<PROMPT
Weekly target: {$targetH}h | Actual: {$thisWeekH}h
Last subject studied: {$lastSubject}
Neglected weak subjects: {$this->formatWeakSubjects($ctx->weakSubjects)}
Goal: {$ctx->profile->goal}
Available materials: {$this->formatMaterials($ctx->materials)}
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
