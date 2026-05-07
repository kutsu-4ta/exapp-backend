<?php

namespace App\Observers;

use App\Models\UserProfile;
use App\Services\AiProfile\AiProfileBuilder;
use Illuminate\Support\Facades\Log;

class UserProfileObserver
{
    public function __construct(
        private readonly AiProfileBuilder $profileBuilder,
    ) {}

    /**
     * 新規作成・更新いずれの保存後も AI 向けキャッシュを再生成する。
     * 翻訳 API 障害時はプロファイル更新自体を止めないようにログのみ残す。
     */
    public function saved(UserProfile $profile): void
    {
        try {
            $this->profileBuilder->rebuild($profile);
        } catch (\Throwable $e) {
            Log::warning('AiUserProfile rebuild failed', [
                'user_id' => $profile->user_id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
