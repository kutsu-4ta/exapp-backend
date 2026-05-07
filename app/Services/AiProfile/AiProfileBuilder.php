<?php

namespace App\Services\AiProfile;

use App\Models\AiUserProfile;
use App\Models\UserProfile;
use App\Services\GoogleTranslateService;

/**
 * UserProfile（日本語）→ AiUserProfile（英語化・正規化済み）を生成・保存する。
 * Gemini へ長い日本語を送らず、compact English JSON を永続化するキャッシュ層。
 */
final class AiProfileBuilder
{
    /** 現在の翻訳ロジックのバージョン。変更時にインクリメントすると再生成が必要と判定できる */
    private const TRANSLATION_VERSION = 1;

    public function __construct(
        private readonly GoogleTranslateService $translator,
    ) {}

    /**
     * UserProfile を英語化・正規化して ai_user_profiles に upsert する。
     * translation_version が同じならスキップして既存レコードを返す。
     */
    public function buildAndSave(UserProfile $profile): AiUserProfile
    {
        $existing = AiUserProfile::where('user_id', $profile->user_id)->first();

        if ($existing !== null && $existing->translation_version === self::TRANSLATION_VERSION) {
            return $existing;
        }

        return $this->translate($profile);
    }

    /**
     * translation_version に関わらず強制的に再生成する。
     * プロファイル更新時のオブザーバから呼ぶ。
     */
    public function rebuild(UserProfile $profile): AiUserProfile
    {
        return $this->translate($profile);
    }

    // ----------------------------------------------------------------

    private function translate(UserProfile $profile): AiUserProfile
    {
        [$occupation, $goal, $interests, $strongAreas, $weakAreas] =
            $this->translator->translateBatchToEnglish([
                $profile->occupation  ?? '',
                $profile->goal        ?? '',
                $profile->interests   ?? '',
                $profile->strong_areas ?? '',
                $profile->weak_areas  ?? '',
            ]);

        $interestsList = $this->parseList($interests);
        $strongList    = $this->parseList($strongAreas);
        $weakList      = $this->parseList($weakAreas);

        // Gemini へそのまま投入する compact 構造
        $normalized = [
            'occupation'    => $occupation ?: null,
            'goal'          => $goal       ?: null,
            'interests'     => $interestsList,
            'strong'        => $strongList,
            'weak'          => $weakList,
            'weeklyTargetH' => 45,
        ];

        return AiUserProfile::updateOrCreate(
            ['user_id' => $profile->user_id],
            [
                'occupation_en'          => $occupation,
                'goal_en'                => $goal,
                'interests_en'           => $interests,
                'strong_areas_en'        => $strongAreas,
                'weak_subjects_json'     => $weakList,
                'study_style_json'       => ['weeklyTargetH' => 45],
                'normalized_prompt_json' => $normalized,
                'translation_version'    => self::TRANSLATION_VERSION,
            ],
        );
    }

    /**
     * 「日本語コンマ / 読点 / 中点 / 改行」区切りのテキストをトリム済み配列に変換する。
     * 翻訳後は英語コンマ区切りになるため、コンマだけで OK。
     */
    private function parseList(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[,、・\n]+/u', $text)),
            fn (string $s) => $s !== '',
        ));
    }
}
