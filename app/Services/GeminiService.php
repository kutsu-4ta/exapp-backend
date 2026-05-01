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
            $message = $e->getMessage();

            // クォータ制限（Rate Limit Exceeded）
            if (str_contains($message, 'quota')) {
                return "現在、APIの利用制限に達しています。1分ほど待ってから再度お試しいただくか、設定からご自身のAPIキーを登録してください。";
            }

            // 高負荷（High Demand / Server Overloaded）
            if (str_contains($message, 'high demand') || str_contains($message, 'overloaded')) {
                return "現在、AIサーバーが非常に混み合っています。一時的なスパイクが発生しているため、数秒〜数十秒おいてから再度実行してください。";
            }

            // その他、予期せぬエラーはそのまま投げるか、汎用メッセージを返す
            throw $e;
        }
    }
}
