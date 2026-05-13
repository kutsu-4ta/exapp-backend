<?php

namespace App\UseCases\Analysis;

use App\Domain\Analysis\AnalysisProblemRepositoryInterface;
use App\Models\AiUserProfile;
use App\Services\AiAdvice\UserProfile;
use App\Models\UserProfile as UserProfileModel;
use App\Models\Problem as ProblemModel;
use App\Services\GeminiService;
use Gemini\Enums\MimeType;
use Illuminate\Http\UploadedFile;

class AnalyzeProblemImageUseCase
{
    public function __construct(
        private readonly GeminiService                      $gemini,
        private readonly AnalysisProblemRepositoryInterface $analysisProblemRepository,
    ) {
    }

    public function __invoke(int $userId, UploadedFile $image, int $problemId): array
    {
        $problem = ProblemModel::findOrFail($problemId);
        $dbProfile = UserProfileModel::where('user_id', $userId)->first();

        $profile = $this->toServiceProfile($dbProfile);

        $goal = $profile->goal;
        $subjectName = $problem->subject?->name;
        $subCategoryName = $problem->subCategory?->name;

        $questionName = $subCategoryName
            ? "$subjectName の $subCategoryName"
            : "$subjectName";

        $questionNameAndNumber = $problem->question_ref
            ? "$questionName の $problem->question_ref"
            : $questionName;

        $missType = collect($problem->failure_types ?? [])
            ->implode(',');

        $proficiency = match ($problem->proficiency) {
            "○" => "応用に備えたい",
            "×△" => "正解したが不安だ",
            default => "不正解だった",
        };

        $memoSection = $problem->note
            ? "\n\n- 私の解答メモ:\n{$problem->note}\nを踏まえて改善点を出すこと。"
            : '';

        $prompt = <<<PROMPT
- 私の目的は{$goal}
- あなたはそのアシスタント。
- この画像は{$questionNameAndNumber}。
- {$missType}の観点で対策する必要がある。
- {$proficiency}。
- 答えそのものよりもコツやポイントの整理を重視して。
{$memoSection}
- 以下の JSON 形式で回答して。
- 他のテキストは一切含めないで。
```json
{
  "note": "内容",
}
```
PROMPT;

        $mimeType = $this->detectMimeType($image);
        $binary = file_get_contents($image->getRealPath());

        $geminiToken = $dbProfile?->gemini_token;
        $geminiModel = AiUserProfile::where('user_id', $userId)->value('gemini_model');

        $json = $this->gemini->analyzeImage(
            $binary,
            $mimeType->value,
            $prompt,
            [],
            $geminiToken,
            $geminiModel
        );

        $note = is_string($json['note'] ?? null) ? $json['note'] : null;

        if ($note) {
            $this->analysisProblemRepository->updateNote($problem, $note);
        }

        return [
            'note' => $note,
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

    private function toServiceProfile(?UserProfileModel $db): UserProfile
    {
        if ($db === null) {
            return UserProfile::default();
        }

        return new UserProfile(
            occupation: $db->occupation ?? UserProfile::default()->occupation,
            goal: $db->goal ?? UserProfile::default()->goal,
            weakAreas: $db->weak_areas ?? UserProfile::default()->weakAreas,
            strongAreas: $db->strong_areas ?? UserProfile::default()->strongAreas,
            interests: $db->interests ?? UserProfile::default()->interests,
            weeklyTargetMinutes: UserProfile::default()->weeklyTargetMinutes,
        );
    }
}
