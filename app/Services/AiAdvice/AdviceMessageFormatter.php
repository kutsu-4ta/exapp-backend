<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class AdviceMessageFormatter
{
    public function format(AiAdviceMode $mode, array $json): string
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS    => $this->formatAnalysis($json),
            AiAdviceMode::INSPIRATION => $this->formatInspiration($json),
            AiAdviceMode::ANALOGY     => $this->formatAnalogy($json),
            AiAdviceMode::WARNING     => $this->formatWarning($json),
        };
    }

    private function formatAnalysis(array $j): string
    {
        $schedule = $j['schedule'] ?? [];
        $reason   = $j['reason']   ?? null;

        $parts = [];

        foreach ($schedule as $item) {
            $slot     = $this->slotLabel($item['slot']    ?? '');
            $subject  = $item['subject']  ?? '';
            $material = $item['material'] ?? '';
            $minutes  = isset($item['minutes']) ? (int) $item['minutes'] : null;
            $focus    = $item['focus']    ?? '';

            if (!$subject) {
                continue;
            }

            $minStr      = $minutes !== null ? "{$minutes}分" : '';
            $materialStr = $material ? "『{$material}』を使い" : '';
            $focusStr    = $focus ? "（{$focus}）" : '';

            $parts[] = "{$slot}は{$materialStr}{$subject}を{$minStr}{$focusStr}。";
        }

        if ($reason) {
            $parts[] = $reason;
        }

        return implode('', $parts) ?: 'データを元に学習計画を見直してください。';
    }

    private function formatInspiration(array $j): string
    {
        $achievement   = $j['achievement']   ?? null;
        $reason        = $j['reason']        ?? null;
        $encouragement = $j['encouragement'] ?? null;

        $parts = [];

        if ($achievement) {
            $parts[] = "{$achievement}という実績は本物です。";
        }
        if ($reason) {
            $parts[] = $reason;
        }
        if ($encouragement) {
            $parts[] = $encouragement;
        }

        return implode('', $parts) ?: 'あなたの努力は確実に積み上がっています。';
    }

    private function formatAnalogy(array $j): string
    {
        $concept = $j['concept']        ?? null;
        $domain  = $j['analogy_domain'] ?? null;
        $mapping = $j['mapping']        ?? null;
        $insight = $j['insight']        ?? null;

        $parts = [];

        if ($concept && $domain) {
            $parts[] = "{$concept}を{$domain}で例えると——";
        }
        if ($mapping) {
            $parts[] = $mapping;
        }
        if ($insight) {
            $parts[] = "つまり、{$insight}";
        }

        return implode('', $parts) ?: '学習内容の類推を取得できませんでした。';
    }

    private function formatWarning(array $j): string
    {
        $deficit         = $j['deficit']         ?? null;
        $consequence     = $j['consequence']     ?? null;
        $urgentSchedule  = $j['urgent_schedule'] ?? [];

        $parts = [];

        if ($deficit) {
            $parts[] = $deficit;
        }
        if ($consequence) {
            $parts[] = $consequence;
        }

        if (!empty($urgentSchedule)) {
            $parts[] = '今すぐ以下を実行してください。';
            foreach ($urgentSchedule as $item) {
                $slot     = $this->slotLabel($item['slot']    ?? '');
                $subject  = $item['subject']  ?? '';
                $material = $item['material'] ?? '';
                $minutes  = isset($item['minutes']) ? (int) $item['minutes'] : null;
                $focus    = $item['focus']    ?? '';

                if (!$subject) {
                    continue;
                }

                $minStr      = $minutes !== null ? "{$minutes}分" : '';
                $materialStr = $material ? "『{$material}』で" : '';
                $focusStr    = $focus ? "（{$focus}）" : '';

                $parts[] = "{$slot}に{$materialStr}{$subject}を{$minStr}{$focusStr}。";
            }
        }

        return implode('', $parts) ?: 'このままでは合格は困難です。今すぐ計画を立て直してください。';
    }

    private function slotLabel(string $slot): string
    {
        return match ($slot) {
            'morning'  => '朝',
            'lunch'    => '昼休み',
            'commute'  => '通勤・移動時間',
            'evening'  => '夕方',
            'night'    => '夜',
            'weekend'  => '週末',
            default    => $slot,
        };
    }
}
