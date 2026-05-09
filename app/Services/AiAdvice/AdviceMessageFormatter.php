<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class AdviceMessageFormatter
{
    /**
     * AIからのレスポンス（生テキスト）をそのまま返す。
     * $response は string であることを想定。
     */
    public function format(AiAdviceMode $mode, string $response): string
    {
        if (empty(trim($response))) {
            return $this->fallbackMessage($mode);
        }

        return $response;
    }

    private function fallbackMessage(AiAdviceMode $mode): string
    {
        return match ($mode) {
            AiAdviceMode::WARNING  => 'このままでは目標達成が困難です。今すぐ学習を開始しましょう。',
            AiAdviceMode::ANALOGY  => '学習内容を深掘りして、理解を定着させましょう。',
            default                => 'あなたの努力は着実に積み上がっています。この調子で続けましょう。',
        };
    }
}
