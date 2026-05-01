<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private string $defaultApiKey;
    private string $defaultModel;

    public function __construct()
    {
        $this->defaultApiKey = config('services.gemini.api_key');
        $this->defaultModel = config('services.gemini.model');
    }

    /**
     * テキストのみのアドバイス生成（モード別プロンプト用）。
     *
     * @param string $systemInstruction AIのペルソナ・制約（モード別）
     * @param string $userPrompt データを埋め込んだユーザーパート
     * @param string|null $apiKey ユーザー個別トークン（未設定時はサーバー共通キーを使用）
     */
    public function generateAdvice(
        string  $systemInstruction,
        string  $userPrompt,
        ?string $apiKey = null
    ): string
    {
        $key = $apiKey ?: $this->defaultApiKey;
        $model = $this->defaultModel;

        $fullPrompt = "System Instruction:\n{$systemInstruction}\n\nUser Request:\n{$userPrompt}";

        $response = retry(3, function () use ($key, $model, $fullPrompt) {
            return Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$key}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $fullPrompt],
                            ],
                        ],
                    ],
                ]
            );
        }, 1000); // 1秒間隔

        if ($response->status() === 503) {
            throw new \RuntimeException('Gemini is busy. retry later.');
        }

        if ($response->failed()) {
            throw new \RuntimeException($response->body());
        }

        return data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }

    public function analyzeImage(
        string  $imageBinary,
        string  $mimeType, // 'image/png' とか
        string  $prompt,
        ?string $apiKey = null,
): array {
        $key = $apiKey ?: config('services.gemini.api_key');
        $model = $this->defaultModel;

        // JSON強制
        $prompt .= "\n\n以下の条件を厳守：
- JSONで出力
- 説明文禁止
- ```禁止";

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$key}",
            [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => base64_encode($imageBinary),
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        if ($response->failed()) {
            throw new \RuntimeException($response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        return $this->decodeJsonRobust($text);
    }


    private function decodeJsonRobust(string $text): array
    {
        // ```json ``` 除去
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);

        $text = trim($text);

        // まず普通にパース
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // JSONっぽい部分だけ抽出
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // 完全失敗
        throw new \RuntimeException(
            'Gemini returned non-JSON response: ' . mb_substr($text, 0, 200)
        );
    }
}
