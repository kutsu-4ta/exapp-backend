<?php

namespace App\Services\AiAdvice;

use App\Enums\AiAdviceMode;

final class AdviceResponseSchema
{
    public static function forMode(AiAdviceMode $mode): array
    {
        return match ($mode) {
            AiAdviceMode::ANALYSIS    => self::analysis(),
            AiAdviceMode::INSPIRATION => self::inspiration(),
            AiAdviceMode::ANALOGY     => self::analogy(),
            AiAdviceMode::WARNING     => self::warning(),
        };
    }

    private static function analysis(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'schedule' => [
                    'type'        => 'array',
                    'description' => '残り時間の配分案（2〜4件）。時間帯・科目・教材・分数・取り組み内容を具体的に示す。',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'slot'     => ['type' => 'string', 'description' => '時間帯: morning / lunch / commute / evening / night / weekend'],
                            'subject'  => ['type' => 'string', 'description' => '科目名（例: 財務・会計）'],
                            'material' => ['type' => 'string', 'description' => '教材名（例: スピードテキスト、過去問マスター、問題集）'],
                            'minutes'  => ['type' => 'integer', 'description' => '学習分数'],
                            'focus'    => ['type' => 'string', 'description' => '取り組む単元・演習内容、25字以内'],
                        ],
                        'required' => ['slot', 'subject', 'material', 'minutes', 'focus'],
                    ],
                ],
                'reason' => ['type' => 'string', 'description' => 'この配分にしたデータ根拠・優先理由、50字以内'],
            ],
            'required' => ['schedule', 'reason'],
        ];
    }

    private static function inspiration(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'achievement'   => ['type' => 'string', 'description' => '称えるべき具体的な実績（数値や継続日数を含む）、30字以内'],
                'reason'        => ['type' => 'string', 'description' => '継続が重要な学術的・論理的根拠、50字以内'],
                'encouragement' => ['type' => 'string', 'description' => '前向きな締めの一言、30字以内'],
            ],
            'required' => ['achievement', 'reason', 'encouragement'],
        ];
    }

    private static function analogy(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'concept'        => ['type' => 'string', 'description' => '比喩で説明する学習概念（科目名・単元名）、20字以内'],
                'analogy_domain' => ['type' => 'string', 'description' => '比喩に使う分野（ユーザーの趣味・関心領域）、15字以内'],
                'mapping'        => ['type' => 'string', 'description' => '概念と比喩分野の対応関係、60字以内'],
                'insight'        => ['type' => 'string', 'description' => 'この比喩で得られる直感的な理解・気づき、40字以内'],
            ],
            'required' => ['concept', 'analogy_domain', 'mapping', 'insight'],
        ];
    }

    private static function warning(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deficit'          => ['type' => 'string', 'description' => '学習量・質の具体的な不足点（数値を含む）、40字以内'],
                'consequence'      => ['type' => 'string', 'description' => 'このまま続けた場合の試験結果、30字以内'],
                'urgent_schedule'  => [
                    'type'        => 'array',
                    'description' => '今すぐ実行すべき緊急配分（1〜3件）。時間帯・科目・教材・分数を明示する。',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'slot'     => ['type' => 'string', 'description' => '時間帯: morning / lunch / commute / evening / night / weekend'],
                            'subject'  => ['type' => 'string', 'description' => '科目名'],
                            'material' => ['type' => 'string', 'description' => '教材名'],
                            'minutes'  => ['type' => 'integer', 'description' => '学習分数'],
                            'focus'    => ['type' => 'string', 'description' => '取り組む内容、25字以内'],
                        ],
                        'required' => ['slot', 'subject', 'material', 'minutes', 'focus'],
                    ],
                ],
            ],
            'required' => ['deficit', 'consequence', 'urgent_schedule'],
        ];
    }
}
