<?php

namespace App\Services;

use Gemini;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;

class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model  = config('services.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * @param string $systemInstruction AIのペルソナ・制約（モード別）
     * @param string $userPrompt        データを埋め込んだユーザーパート
     */
    public function generateAdvice(string $systemInstruction, string $userPrompt): string
    {
        $client = Gemini::client($this->apiKey);

        $config = new GenerationConfig(maxOutputTokens: 500);

        $response = $client->generativeModel(model: $this->model)
            ->withSystemInstruction(Content::parse($systemInstruction))
            ->withGenerationConfig($config)
            ->generateContent($userPrompt);

        return $response->text();
    }
}
