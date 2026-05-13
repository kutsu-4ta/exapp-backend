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
        private readonly AnalysisProblemRepositoryInterface $repo,
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
            "○" => "応用に備える",
            "×△" => "正解だが不安な",
            default => "誤答した",
        };

        $memoSection = $problem->note
            ? "\n\n- 私の解答メモ:\n{$problem->note}\nを踏まえて改善点を出すこと。"
            : '';

        $prompt = <<<PROMPT
- 挨拶等は不要
- 私の目的は{$goal}
- この画像は{$questionNameAndNumber}
- {$proficiency}ため{$missType}の観点で整理したい
- 答えよりもコツやポイントの整理を重視して
{$memoSection}
PROMPT;

        $binary = file_get_contents($image->getRealPath());

        $ai = $this->gemini->analyzeImage(
            $binary,
            $this->mime($image)->value,
            $prompt,
            $dbProfile->gemini_token,
            AiUserProfile::where('user_id', $userId)->value('gemini_model')
        );

        $note = is_string($ai) ? $ai : null;

        if ($note) {
            $this->repo->updateNote($problem, $note);
        }

        return ['note' => $note];
    }

    private function mime(UploadedFile $image): MimeType
    {
        return match (strtolower($image->getClientOriginalExtension())) {
            'png' => MimeType::IMAGE_PNG,
            'webp' => MimeType::IMAGE_WEBP,
            'heic' => MimeType::IMAGE_HEIC,
            default => MimeType::IMAGE_JPEG,
        };
    }

    private function toServiceProfile(?UserProfileModel $db): UserProfile
    {
        $profile = UserProfile::default();

        if (!$db) return $profile;

        return new UserProfile(
            occupation: $db->occupation ?? $profile->occupation,
            goal: $db->goal ?? $profile->goal,
            weakAreas: $db->weak_areas ?? $profile->weakAreas,
            strongAreas: $db->strong_areas ?? $profile->strongAreas,
            interests: $db->interests ?? $profile->interests,
            weeklyTargetMinutes: $profile->weeklyTargetMinutes,
        );
    }

}
