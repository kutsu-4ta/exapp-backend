<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class GoogleTranslateService
{
    private string $apiKey;
    private string $endpoint = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct()
    {
        $this->apiKey = config('services.google_translate.api_key', '');
    }

    /**
     * 複数テキストをまとめて英語に翻訳する。
     * 各テキストは個別にキャッシュされ、キャッシュ済みのものは API を呼ばない。
     * API 障害・キー未設定時は元のテキストをそのまま返す（フォールバック）。
     *
     * @param  string[] $texts
     * @return string[]  入力と同じ順序の翻訳結果
     */
    public function translateBatchToEnglish(array $texts): array
    {
        $results  = [];
        $uncached = [];

        foreach ($texts as $i => $text) {
            $cached = Cache::get($this->cacheKey($text));
            if ($cached !== null) {
                $results[$i] = $this->sanitize($cached);
            } else {
                $uncached[$i] = $text;
            }
        }

        if (!empty($uncached) && $this->apiKey !== '') {
            try {
                $response = Http::withQueryParameters(['key' => $this->apiKey])
                    ->post($this->endpoint, [
                        'q'      => array_values($uncached),
                        'target' => 'en',
                        'format' => 'text',
                    ]);

                $translations = data_get($response->json(), 'data.translations', []);
                $indices      = array_keys($uncached);

                foreach ($translations as $j => $translation) {
                    $original       = $uncached[$indices[$j]];
                    $translatedText = $this->sanitize($translation['translatedText'] ?? $original);
                    Cache::put($this->cacheKey($original), $translatedText, now()->addWeek());
                    $results[$indices[$j]] = $translatedText;
                }
            } catch (Throwable) {
                // 例外時は後続のフォールバックで補填
            }
        }

        // API 未設定・エラー・件数不足いずれの場合も原文で埋める
        foreach ($uncached as $i => $text) {
            $results[$i] ??= $text;
        }

        ksort($results);

        return array_values($results);
    }

    private function cacheKey(string $text): string
    {
        return 'gtranslate:en:' . md5($text);
    }

    private function sanitize(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        return $text;
    }
}
