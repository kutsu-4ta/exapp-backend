<?php

namespace App\UseCases\Analysis;

use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Models\AiUserProfile;
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

    public function __invoke(int $userId, UploadedFile $image, ?string $memo = null): array
    {
        $dbProfile = UserProfileModel::where('user_id', $userId)->first();
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

        $memoSection = $memo !== null && trim($memo) !== ''
            ? "\n\n- ユーザーのメモ（解答時の思考）:\n{$memo}\nを踏まえ、間違えた原因や改善ポイントを重点的に含めること。"
            : '';

        $prompt = <<<PROMPT
あなたは{$goal}のアシスタントです。
この画像は試験の問題集のページです。
この問題を分析して、以下の JSON 形式で回答してください（他のテキストは一切含めないこと）。
```json
{
  "subject_name": "科目",
  "sub_category_name": "小分類",
  "question_ref": "問題番号",
  "note": "ノート",
  "failure_types": ミスタイプ,
  "is_good_question": "良問"
}
```
- 科目は、{$subjects}の中から最も近いものを選ぶ。
- 小分類は、{$subCategories}の中から最も近いものを選ぶ。
- 問題番号は、（例: 12問、不明なら null）
- ノートは、問題の要約ではなく、復習時に一目で「解法パターン」がわかる「自分専用まとめ」としてください。
- 以下の構成を含めること：
  - 【直感的な理解】: 複雑な概念を、日常的な状況や立場に置き換えて説明。
  - 【判断の軸】: 迷ったときに、AかBかを判定するための「チェック項目」や「思考のプロセス」。
  - 【診断士試験の急所】: 過去問で狙われやすい「ひっかけパターン」や、覚えるべき「優先順位」。
  {$memoSection}
PROMPT;

        $mimeType = $this->detectMimeType($image);
        $binary = file_get_contents($image->getRealPath());

        $geminiToken = $dbProfile?->gemini_token;
        $geminiModel = AiUserProfile::where('user_id', $userId)->value('gemini_model');

        $json = $this->gemini->analyzeImage($binary, $mimeType->value, $prompt, [], $geminiToken, $geminiModel);

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
