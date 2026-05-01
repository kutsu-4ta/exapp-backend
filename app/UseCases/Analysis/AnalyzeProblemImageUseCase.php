<?php

namespace App\UseCases\Analysis;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Models\UserProfile;
use App\Services\GeminiService;
use Gemini\Enums\MimeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class AnalyzeProblemImageUseCase
{
    public function __construct(
        private readonly GeminiService              $gemini,
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly ProblemRepositoryInterface $problemRepository,
    ) {
    }

    public function __invoke(int $userId, UploadedFile $image): array
    {
        $subjects = $this->subjectRepository->findAll($userId)
            ->pluck('name')
            ->implode('、');

        $prompt = <<<PROMPT
あなたは中小企業診断士の試験対策アプリのアシスタントです。
以下の画像は試験の過去問または問題集のページです。

この問題を分析して、以下の JSON 形式で回答してください（他のテキストは一切含めないこと）。

```json
{
  "subject": "科目名（後述の候補から選ぶ。なければ最も近いものを選ぶ）",
  "question_ref": "問題番号や参照情報（例: 令和5年 第12問、Chapter3 Q5 など。不明なら null）",
  "note": "この問題の要点・間違えやすいポイントを100字以内で記述",
  "failure_types": ["定義ミス", "解法ミス", "計算ミス"],
  "is_good_question": true
}
```

- `failure_types` は次の値のみ使用可: "定義ミス"、"解法ミス"、"計算ミス"。該当なければ空配列 []。
- `is_good_question` は複数のポイントにまたがっていたり、公式が複数必要であったりする問題である。
- 科目候補: {$subjects}

PROMPT;

        $mimeType = $this->detectMimeType($image);
        $binary = file_get_contents($image->getRealPath());

        $dbProfile = UserProfile::where('user_id', $userId)->first();
        $geminiToken = $dbProfile?->gemini_token;

        $json = $this->gemini->analyzeImage($binary, $mimeType->value, $prompt, $geminiToken);

        // 科目名の特定とIDの解決（DB保存はせず、既存IDの検索にとどめるか、新規ならnullで返す）
        $subjectName = is_string($json['subject_name'] ?? null) ? $json['subject_name'] : '未分類';
        $subject = $this->subjectRepository->findAll($userId)
            ->where('name', $subjectName)
            ->first();

        $validFailureTypes = $this->filterFailureTypes($json['failure_types'] ?? []);

        return [
            'subject_id' => $subject?->id, // 見つからなければ null。フロントで選択させる
            'subject_name' => $subjectName,
            'question_ref' => is_string($json['question_ref'] ?? null) ? $json['question_ref'] : null,
            'note' => is_string($json['note'] ?? null) ? $json['note'] : null,
            'proficiency' => Proficiency::Incorrect->value, // デフォルトで「×」
            'failure_types' => $validFailureTypes,
            'is_good_question' => (bool)($json['is_good_question'] ?? false),
            'solved_at' => Carbon::today()->toDateString(),
        ];
    }

    private function detectMimeType(UploadedFile $image): MimeType
    {
        return match (strtolower($image->getClientOriginalExtension())) {
            'jpg', 'jpeg' => MimeType::IMAGE_JPEG,
            'png' => MimeType::IMAGE_PNG,
            'webp' => MimeType::IMAGE_WEBP,
            'heic' => MimeType::IMAGE_HEIC,
            default => MimeType::IMAGE_JPEG,
        };
    }

    /** @return string[] */
    private function filterFailureTypes(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $valid = array_column(FailureType::cases(), 'value');

        return array_values(array_filter($raw, fn($v) => is_string($v) && in_array($v, $valid, true)));
    }
}
