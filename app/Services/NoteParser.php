<?php

namespace App\Services;

/**
 * Problem.note のマークダウン構造セクションをパースする。
 *
 * 対応ヘッダー：
 *   #Definition #Formula #Keyword #Pitfall #Example #Relation #MemoryHook
 */
class NoteParser
{
    private const SECTION_HEADERS = [
        'definition'  => '#Definition',
        'formula'     => '#Formula',
        'keyword'     => '#Keyword',
        'pitfall'     => '#Pitfall',
        'example'     => '#Example',
        'relation'    => '#Relation',
        'memory_hook' => '#MemoryHook',
    ];

    /**
     * note テキストを構造化データにパースして返す。
     * ヘッダーが一つも含まれない場合は ['raw' => $note] を返す。
     *
     * @return array<string, string>
     */
    public static function parse(?string $note): array
    {
        if (empty($note)) {
            return [];
        }

        if (!self::hasStructuredContent($note)) {
            return ['raw' => $note];
        }

        $result         = [];
        $currentKey     = null;
        $currentLines   = [];

        foreach (explode("\n", $note) as $line) {
            $trimmed = trim($line);
            $matched = false;

            foreach (self::SECTION_HEADERS as $key => $header) {
                if (str_starts_with($trimmed, $header)) {
                    if ($currentKey !== null) {
                        $content = trim(implode("\n", $currentLines));
                        if ($content !== '') {
                            $result[$currentKey] = $content;
                        }
                    }
                    $currentKey   = $key;
                    $inline       = trim(substr($trimmed, strlen($header)));
                    $currentLines = $inline !== '' ? [$inline] : [];
                    $matched      = true;
                    break;
                }
            }

            if (!$matched && $currentKey !== null) {
                $currentLines[] = $line;
            }
        }

        if ($currentKey !== null) {
            $content = trim(implode("\n", $currentLines));
            if ($content !== '') {
                $result[$currentKey] = $content;
            }
        }

        return $result;
    }

    /**
     * note が構造化セクションを1つ以上含むかどうかを返す。
     */
    public static function hasStructuredContent(?string $note): bool
    {
        if (empty($note)) {
            return false;
        }
        foreach (self::SECTION_HEADERS as $header) {
            if (str_contains($note, $header)) {
                return true;
            }
        }
        return false;
    }

    /**
     * パース済み配列をプロンプト用のテキストに整形する。
     */
    public static function toPromptText(array $parsed): string
    {
        if (empty($parsed)) {
            return '';
        }

        if (isset($parsed['raw'])) {
            return $parsed['raw'];
        }

        $labelMap = [
            'definition'  => '定義',
            'formula'     => '公式',
            'keyword'     => '重要語',
            'pitfall'     => '間違えやすい点',
            'example'     => '具体例',
            'relation'    => '他概念との関係',
            'memory_hook' => '覚え方',
        ];

        $lines = [];
        foreach ($labelMap as $key => $label) {
            if (!empty($parsed[$key])) {
                $lines[] = "[{$label}] " . $parsed[$key];
            }
        }

        return implode("\n", $lines);
    }
}
