<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $profile = $user->profile;

        return response()->json($this->format($profile));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $validated = $request->validated();

        // バリデーション済みキー（camelCase）だけを snake_case にマップして保存。
        // 送信されなかったキーは更新対象外（`sometimes` ルールの効果）。
        $keyMap = [
            'nickname'    => 'nickname',
            'occupation'  => 'occupation',
            'goal'        => 'goal',
            'weakAreas'   => 'weak_areas',
            'strongAreas' => 'strong_areas',
            'interests'   => 'interests',
            'geminiToken' => 'gemini_token',
        ];

        $fillable = [];
        foreach ($keyMap as $camel => $snake) {
            if (array_key_exists($camel, $validated)) {
                $fillable[$snake] = $validated[$camel];
            }
        }

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            $fillable,
        );

        return response()->json($this->format($profile));
    }

    // ----------------------------------------------------------------

    private function format(?UserProfile $profile): array
    {
        if ($profile === null) {
            return [
                'nickname'       => null,
                'occupation'     => null,
                'goal'           => null,
                'weakAreas'      => null,
                'strongAreas'    => null,
                'interests'      => null,
                'geminiTokenSet' => false,
            ];
        }

        return [
            'nickname'       => $profile->nickname,
            'occupation'     => $profile->occupation,
            'goal'           => $profile->goal,
            'weakAreas'      => $profile->weak_areas,
            'strongAreas'    => $profile->strong_areas,
            'interests'      => $profile->interests,
            'geminiTokenSet' => $profile->gemini_token !== null,
        ];
    }
}
