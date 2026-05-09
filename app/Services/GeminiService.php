<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $defaultApiKey;
    private string $defaultModel;
    private string $baseUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->defaultApiKey = config('services.gemini.api_key');
        $this->defaultModel  = config('services.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl       = config('services.gemini.base_url')
            ?: 'https://generativelanguage.googleapis.com/v1beta/models';
        $this->timeout       = config('services.gemini.request_timeout', 30);
    }

    /**
     * プレーンテキスト生成。スキーマなし・自由形式のテキストを返す。
     */
    public function generateText(
        string  $systemInstruction,
        string  $userPrompt,
        ?string $apiKey = null
    ): string {
        $json = $this->callGemini(
            contents: [
                ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
            ],
            apiKey: $apiKey,
            systemInstruction: $systemInstruction,
        );

        return $this->extractText($json);
    }

    /**
     * JSON 強制出力でテキスト生成。responseMimeType=application/json + responseSchema を使用。
     * レスポンスは decode 済み配列で返す。
     */
    public function generateJson(
        string  $systemInstruction,
        string  $userPrompt,
        array   $responseSchema,
        ?string $apiKey = null
    ): array {
        $json = $this->callGemini(
            contents: [
                ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
            ],
            apiKey: $apiKey,
            systemInstruction: $systemInstruction,
            generationConfig: [
                'responseMimeType' => 'application/json',
                'responseSchema'   => $responseSchema,
            ],
        );

        $text = $this->extractText($json);

        return $this->decodeJson($text);
    }

    /**
     * 画像解析（JSON 強制）。responseMimeType=application/json を使用。
     */
    public function analyzeImage(
        string  $imageBinary,
        string  $mimeType,
        string  $prompt,
        array   $responseSchema = [],
        ?string $apiKey = null
    ): array {
        $generationConfig = ['responseMimeType' => 'application/json'];
        if (!empty($responseSchema)) {
            $generationConfig['responseSchema'] = $responseSchema;
        }

        $json = $this->callGemini(
            contents: [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data'      => base64_encode($imageBinary),
                            ],
                        ],
                    ],
                ],
            ],
            apiKey: $apiKey,
            generationConfig: $generationConfig,
        );

        $text = $this->extractText($json);

        return $this->decodeJson($text);
    }

    // ----------------------------------------------------------------

    /**
     * Gemini API 共通呼び出し。
     *
     * @param  array       $contents          Gemini contents 配列
     * @param  string|null $apiKey            上書きキー
     * @param  string|null $systemInstruction systemInstruction テキスト
     * @param  array       $generationConfig  generationConfig オブジェクト
     */
    private function callGemini(
        array   $contents,
        ?string $apiKey = null,
        ?string $systemInstruction = null,
        array   $generationConfig = [],
    ): array {
        $key   = $apiKey ?: $this->defaultApiKey;
        $model = $this->defaultModel;

        $body = ['contents' => $contents];

        if ($systemInstruction !== null) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }
        if (!empty($generationConfig)) {
            $body['generationConfig'] = $generationConfig;
        }

        $response = retry(3, function () use ($key, $model, $body) {
            return Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/{$model}:generateContent?key={$key}",
                $body,
            );
        }, 1000);

        if ($response->status() === 503) {
            throw new \RuntimeException('Gemini is busy (503)');
        }

        if ($response->failed()) {
            $status = data_get($response->json(), 'error.status');
            if ($status === 'FAILED_PRECONDITION') {
                throw new \RuntimeException('Gemini is not available in this region (FAILED_PRECONDITION)');
            }
            if ($status === 'RESOURCE_EXHAUSTED' || $response->status() === 429) {
                throw new \RuntimeException('Gemini quota exceeded (RESOURCE_EXHAUSTED)');
            }
            throw new \RuntimeException(
                "Gemini error: {$response->status()} {$response->body()}"
            );
        }

        $result = $response->json();

        // Safety block チェック
        $finishReason = data_get($result, 'candidates.0.finishReason');
        if ($finishReason === 'SAFETY') {
            $blockReason = data_get($result, 'promptFeedback.blockReason', 'SAFETY');
            throw new \RuntimeException("Gemini blocked: {$blockReason}");
        }

        return $result;
    }

    private function extractText(array $json): string
    {
        return data_get($json, 'candidates.0.content.parts.0.text', '');
    }

    /**
     * JSON デコード。responseMimeType=application/json 使用時はほぼ素直に解析できる想定。
     * 万が一のコードブロック混入にも対応する。
     */
    private function decodeJson(string $text): array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // オブジェクト部分を抽出して再試行
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('Gemini returned invalid JSON: ' . mb_substr($text, 0, 200));
    }
}
