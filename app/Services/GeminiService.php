<?php

namespace App\Services;

use Gemini;
use Gemini\Data\Content;

class GeminiService
{
    private string $defaultApiKey;

    public function __construct()
    {
        $this->defaultApiKey = config('services.gemini.api_key');

    }

    /**
     * @param string $systemInstruction AIのペルソナ・制約（モード別）
     * @param string $userPrompt データを埋め込んだユーザーパート
     * @param string|null $apiKey ユーザー個別トークン（未設定時はサーバー共通キーを使用）
     */
    public function generateAdvice(string $systemInstruction, string $userPrompt, ?string $apiKey = null): string
    {
        $key = ($apiKey !== null && $apiKey !== '') ? $apiKey : $this->defaultApiKey;

        try {
            $client = \Gemini::factory()
                ->withApiKey($key)
                ->make();

            $modelName = 'models/gemini-flash-latest';

            $response = $client->generativeModel(model: $modelName)
                ->withSystemInstruction(Content::parse($systemInstruction))
                ->generateContent($userPrompt);

            return $response->text();
        } catch (\Gemini\Exceptions\ErrorException $e) {
            // クォータエラーなどの場合に、ユーザーに分かりやすいメッセージを返す
            if (str_contains($e->getMessage(), 'quota')) {
                return "現在、AIの利用制限に達しています。1分ほど待ってから再度お試しいただくか、設定からご自身のAPIキーを登録してください。";
            }
            throw $e;
        }
    }
}
