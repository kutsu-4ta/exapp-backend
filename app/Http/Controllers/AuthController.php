<?php

namespace App\Http\Controllers;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'api';

    public function __construct(
        private readonly SubjectRepositoryInterface $subjectRepository,
        private readonly MaterialRepositoryInterface $materialRepository,
        private readonly FirebaseAuth $firebaseAuth,
    ) {}

    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string']);

        $user = $this->resolveUserFromFirebaseToken($request->input('id_token'), isNewRegistration: true);
        $token = $this->issueToken($user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string']);

        $user = $this->resolveUserFromFirebaseToken($request->input('id_token'), isNewRegistration: false);

        // 既存トークンをすべて削除してから新規発行
        $user->tokens()->delete();
        $token = $this->issueToken($user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (!$user) {
            throw new AuthenticationException('ユーザー認証に失敗しました。');
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'ログアウトしました。']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($this->userPayload($user));
    }

    // ----------------------------------------------------------------
    // private helpers
    // ----------------------------------------------------------------

    /**
     * Firebase IDトークンを検証してユーザーを返す。
     * isNewRegistration=true のとき、初回登録時の初期データ投入も行う。
     */
    private function resolveUserFromFirebaseToken(string $idToken, bool $isNewRegistration): User
    {
        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            throw new AuthenticationException('Firebase IDトークンが無効です: ' . $e->getMessage());
        }

        $firebaseUid = $verified->claims()->get('sub');
        $email       = $verified->claims()->get('email');
        $name        = $verified->claims()->get('name');

        // firebase_uid → email の順で既存ユーザーを探す
        $user = User::where('firebase_uid', $firebaseUid)->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            // 既存ユーザー: firebase_uid が未紐付けならリンク、プロフィールを最新化
            $user->firebase_uid = $user->firebase_uid ?? $firebaseUid;
            $user->name         = $name ?? $user->name;
            $user->email        = $email;
            $user->save();

            return $user;
        }

        if (!$isNewRegistration) {
            throw new AuthenticationException('ユーザーが見つかりません。先にGoogleログインで登録してください。');
        }

        // 新規ユーザー作成
        $user = User::create([
            'firebase_uid' => $firebaseUid,
            'name'         => $name ?? 'Google User',
            'email'        => $email,
            'password'     => null,
        ]);

        $this->subjectRepository->seedDefaults($user->id);
        $this->materialRepository->seedDefaults($user->id);

        return $user;
    }

    /** 有効期限付き Sanctum トークンを発行する */
    private function issueToken(User $user): string
    {
        $expiresAt = now()->addDays((int) env('SANCTUM_TOKEN_EXPIRATION_DAYS', 30));

        return $user->createToken(self::TOKEN_NAME, ['*'], $expiresAt)->plainTextToken;
    }

    /** レスポンス用ユーザー情報 */
    private function userPayload(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
}
