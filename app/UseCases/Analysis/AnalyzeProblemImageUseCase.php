<?php

namespace App\UseCases\Analysis;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Services\AiAdvice\UserProfile;
use App\Models\UserProfile as UserProfileModel;
use App\Services\GeminiService;
use Gemini\Enums\MimeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class AnalyzeProblemImageUseCase
{
    public function __construct(
        private readonly GeminiService              $gemini,
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly SubCategoryRepositoryInterface $subCategoryRepository
    ) {
    }

    public function __invoke(int $userId, UploadedFile $image): array
    {$dbProfile = UserProfileModel::where('user_id', $userId)->first();
        $profile  = $this->toServiceProfile($dbProfile);
        $goal = $profile->goal;
        $weak = $profile->weakAreas;
        $strong = $profile->strongAreas;
        $subjects = $this->subjectRepository->findAll($userId)
            ->pluck('name')
            ->implode('、');
        $subCategories = $this->subCategoryRepository->findAllByUser($userId)
            ->pluck('name')
            ->implode('、');

        $prompt = <<<PROMPT
あなたは{$goal}のアシスタントです。
この画像は試験の問題集のページです。

この問題を分析して、以下の JSON 形式で回答してください（他のテキストは一切含めないこと）。

```json
{
  "subject_name": "科目",
  "sub_category_name": "小分類"
  "question_ref": "問題番号",
  "note": "ノート",
  "failure_types": ミスタイプ,
  "is_good_question": "良問"
}
```

- 科目は、{$subjects}の中から最も近いものを選ぶ。
- 小分類は、{$subCategories}の中から最も近いものを選ぶ。
- 問題番号は、問題番号や参照情報（例: 令和5年 第12問、Chapter3 Q5 など。不明なら null）
- ノートは、この問題の要点・間違えやすいポイントを要約。ユーザーの得意（{$strong}）と不得意（{$weak}）を考慮する。
- ミスタイプは、次の値のみ使用可: "定義ミス"、"解法ミス"、"計算ミス"。該当なければ空配列 []。
- 良問は、複数のポイントにまたがっていたり、複数の公式を使う必要がある問題はtrue,頻出でないニッチな問題ならfalse。

PROMPT;

        $mimeType = $this->detectMimeType($image);
        $binary = file_get_contents($image->getRealPath());

        $dbProfile = UserProfileModel::where('user_id', $userId)->first();
        $geminiToken = $dbProfile?->gemini_token;

        $json = $this->gemini->analyzeImage($binary, $mimeType->value, $prompt, [], $geminiToken);

        $validFailureTypes = $this->filterFailureTypes($json['failure_types'] ?? []);

        return [
            'subject_name' =>  $json['subject_name'] ?? null,
            'sub_category_name' =>  $json['sub_category_name'] ?? null,
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

    private function toServiceProfile(?UserProfileModel $db): UserProfile
    {
        if ($db === null) {
            return UserProfile::default();
        }

        return new UserProfile(
            occupation:          $db->occupation          ?? UserProfile::default()->occupation,
            goal:                $db->goal                ?? UserProfile::default()->goal,
            weakAreas:           $db->weak_areas          ?? UserProfile::default()->weakAreas,
            strongAreas:         $db->strong_areas        ?? UserProfile::default()->strongAreas,
            interests:           $db->interests           ?? UserProfile::default()->interests,
            weeklyTargetMinutes: UserProfile::default()->weeklyTargetMinutes,
        );
    }

}
