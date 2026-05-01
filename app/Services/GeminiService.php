<?php

namespace App\Services;

use Gemini;
use Gemini\Data\Blob;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Enums\MimeType;
use Gemini\Enums\ResponseMimeType;

class GeminiService
{
    private string $defaultApiKey;

    public function __construct()
    {
        $this->defaultApiKey = config('services.gemini.api_key');
    }

    /**
     * テキストのみのアドバイス生成（モード別プロンプト用）。
     *
     * @param string      $systemInstruction AIのペルソナ・制約（モード別）
     * @param string      $userPrompt        データを埋め込んだユーザーパート
     * @param string|null $apiKey            ユーザー個別トークン（未設定時はサーバー共通キーを使用）
     */
    public function generateAdvice(string $systemInstruction, string $userPrompt, ?string $apiKey = null): string
    {
        $key = $this->resolveKey($apiKey);

        try {
            $client = \Gemini::factory()->withApiKey($key)->make();

            $response = $client->generativeModel(model: 'models/gemini-flash-latest')
                ->withSystemInstruction(Content::parse($systemInstruction))
                ->generateContent($userPrompt);

            return $response->text();

        } catch (\Gemini\Exceptions\ErrorException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'quota')) {
                return 'APIの利用制限に達しています。1分ほど待つか、設定からご自身のAPIキーを登録してください。';
            }
            if (str_contains($message, 'high demand') || str_contains($message, 'overloaded')) {
                return 'AIサーバーが混み合っています。数秒後に再度お試しください。';
            }

            throw $e;
        }
    }

    /**
     * 画像＋テキストのマルチモーダル解析。Gemini に JSON を強制返答させる。
     *
     * @param string      $imageBinary 画像のバイナリデータ（未エンコード）
     * @param MimeType    $mimeType    画像の MIME タイプ
     * @param string      $prompt      テキスト指示
     * @param string|null $apiKey      ユーザー個別トークン
     * @return array<string, mixed>    パース済み JSON 連想配列
     *
     * @throws \RuntimeException Gemini が JSON を返さなかった場合
     */
    public function analyzeImage(
        string   $imageBinary,
        MimeType $mimeType,
        string   $prompt,
        ?string  $apiKey = null,
    ): array {
        $key = $this->resolveKey($apiKey);

        $client = \Gemini::factory()->withApiKey($key)->make();

        $config = new GenerationConfig(
            maxOutputTokens:  2048,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
        );

        // 画像を Base64 エンコードして Blob に渡す
        $blob    = new Blob(mimeType: $mimeType, data: base64_encode($imageBinary));
        $content = Content::parse([$blob, $prompt]);

        $response = $client->generativeModel(model: 'models/gemini-1.5-flash')
            ->withGenerationConfig($config)
            ->generateContent($content);

        $raw = $response->text();

        return $this->decodeJson($raw);
    }

    // ----------------------------------------------------------------

    private function resolveKey(?string $apiKey): string
    {
        return ($apiKey !== null && $apiKey !== '') ? $apiKey : $this->defaultApiKey;
    }

    /**
     * Gemini のレスポンスを JSON 配列にデコードする。
     * ResponseMimeType::APPLICATION_JSON を使っているので通常はクリーンだが、
     * 念のりマークダウン記法を除去してから試みる。
     *
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    private function decodeJson(string $text): array
    {
        // ```json ... ``` を除去
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);

        $decoded = json_decode(trim($text), true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Gemini returned non-JSON response: ' . mb_substr($text, 0, 200));
        }

        return $decoded;
    }
}
