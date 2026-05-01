<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $defaultApiKey;
    private string $defaultModel;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1/models';

    public function __construct()
    {
        $this->defaultApiKey = config('services.gemini.api_key');
        $this->defaultModel  = config('services.gemini.model', 'gemini-2.5-flash');

        $this->baseUrl = config('services.gemini.base_url')
            ?: 'https://generativelanguage.googleapis.com/v1/models';

        $this->timeout = config('services.gemini.request_timeout', 30);
    }

    /**
     * テキスト生成
     */
    public function generateAdvice(
        string  $systemInstruction,
        string  $userPrompt,
        ?string $apiKey = null
    ): string {
        $prompt = "System Instruction:\n{$systemInstruction}\n\nUser Request:\n{$userPrompt}";

        $json = $this->callGemini([
            [
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ], $apiKey);

        return $this->extractText($json);
    }

    /**
     * 画像解析（JSON強制）
     */
    public function analyzeImage(
        string  $imageBinary,
        string  $mimeType,
        string  $prompt,
        ?string $apiKey = null
    ): array {
        $prompt .= "\n\n以下を厳守：
- JSONで出力
- 説明文禁止
- ```禁止";

        $json = $this->callGemini([
            [
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => base64_encode($imageBinary),
                        ],
                    ],
                ],
            ],
        ], $apiKey);

        $text = $this->extractText($json);

        return $this->decodeJsonRobust($text);
    }

    /**
     * Gemini API 共通呼び出し
     */
    private function callGemini(array $contents, ?string $apiKey): array
    {
        $key = $apiKey ?: $this->defaultApiKey;
        $model = $this->defaultModel;

        $response = retry(3, function () use ($key, $model, $contents) {
            return Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/{$model}:generateContent?key={$key}",
                [
                    'contents' => $contents,
                ]
            );
        }, 1000);

        // ステータス別処理
        if ($response->status() === 503) {
            throw new \RuntimeException('Gemini is busy (503)');
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                "Gemini error: {$response->status()} {$response->body()}"
            );
        }

        return $response->json();
    }

    /**
     * テキスト抽出
     */
    private function extractText(array $json): string
    {
        return data_get($json, 'candidates.0.content.parts.0.text', '');
    }

    /**
     * JSONパース（耐久）
     */
    private function decodeJsonRobust(string $text): array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException(
            'Invalid JSON: ' . mb_substr($text, 0, 200)
        );
    }
}
