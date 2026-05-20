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
    private const HASHTAG_PATTERN = '/#(Definition|Formula|Keyword|Pitfall|Example|Relation|MemoryHook)(?=\s|$)/';

    private const TAG_KEY_MAP = [
        'Definition' => 'definition',
        'Formula'    => 'formula',
        'Keyword'    => 'keyword',
        'Pitfall'    => 'pitfall',
        'Example'    => 'example',
        'Relation'   => 'relation',
        'MemoryHook' => 'memory_hook',
    ];

    /**
     * note テキストを構造化データにパースして返す。
     *
     * フリーテキスト中のハッシュタグを検出し、各セクションの内容を抽出する。
     * - ハッシュタグ直後の空白 or 改行1つはスキップ
     * - \n\n またはセクション終端でブロック終了
     * - 同じハッシュタグが複数回出現した場合は最後の値を採用
     *
     * @return array<string, string>
     */
    public static function parse(?string $note): array
    {
        if (empty($note)) {
            return [];
        }

        if (!preg_match_all(self::HASHTAG_PATTERN, $note, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $result       = [];
        $total        = count($matches[0]);
        $noteLen      = strlen($note);

        for ($i = 0; $i < $total; $i++) {
            $tagName = $matches[1][$i][0];
            $tagEnd  = $matches[0][$i][1] + strlen($matches[0][$i][0]);

            // ハッシュタグ直後の空白または改行1文字をスキップ
            $contentStart = $tagEnd;
            if ($contentStart < $noteLen
                && ($note[$contentStart] === ' ' || $note[$contentStart] === "\n")
            ) {
                $contentStart++;
            }

            // ブロック終端: \n\n (tagEnd 以降で最初に見つかる位置)、次タグ、文字列末尾のうち最も手前
            $nextTagPos       = ($i + 1 < $total) ? $matches[0][$i + 1][1] : $noteLen;
            $doubleNewlinePos = strpos($note, "\n\n", $tagEnd);

            $contentEnd = ($doubleNewlinePos !== false && $doubleNewlinePos < $nextTagPos)
                ? $doubleNewlinePos
                : $nextTagPos;

            $content = trim(substr($note, $contentStart, max(0, $contentEnd - $contentStart)));
            $key     = self::TAG_KEY_MAP[$tagName];

            if ($content !== '') {
                $result[$key] = $content;
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
        return (bool) preg_match(self::HASHTAG_PATTERN, $note);
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
