<?php

namespace App\Http\Controllers;

use App\Domain\Material\MaterialRepositoryInterface;
use App\Domain\Snippet\SnippetRepositoryInterface;
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
        private readonly SnippetRepositoryInterface $snippetRepository,
        private readonly FirebaseAuth $firebaseAuth,
    ) {}

    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string']);

        $user = $this->resolveUserFromFirebaseToken($request->input('id_token'));
        $token = $this->issueToken($user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['id_token' => 'required|string']);

        $user = $this->resolveUserFromFirebaseToken($request->input('id_token'));

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
        $user = $request->user() ?? auth('sanctum')->user();

        return response()->json($this->userPayload($user));
    }

    // ----------------------------------------------------------------
    // private helpers
    // ----------------------------------------------------------------

    /**
     * Firebase IDトークンを検証してユーザーを返す。
     * isNewRegistration=true のとき、初回登録時の初期データ投入も行う。
     */
    private function resolveUserFromFirebaseToken(string $idToken): User
    {
        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            // ここでエラーが出る場合は、DB以前に「トークン自体」が壊れているか期限切れ。
            \Log::error('Firebase Verify Error: ' . $e->getMessage());
            throw new AuthenticationException('Firebase IDトークンが無効です。');
        }

        $firebaseUid = $verified->claims()->get('sub');
        $email       = $verified->claims()->get('email');
        $name        = $verified->claims()->get('name');

        // ユーザーを取得または作成（updateOrCreate）
        // firebase_uid をキーにしつつ、email も保持・更新する
        $user = User::updateOrCreate(
            ['firebase_uid' => $firebaseUid],
            [
                'name'  => $name ?? 'Google User',
                'email' => $email,
                'password' => null, // Firebase認証のためパスワード不要
            ]
        );

        // ユーザー作成直後、あるいは初期データが欠損している場合にシードを実行
        // wasRecentlyCreated は Eloquent の標準機能
        if ($user->wasRecentlyCreated || $this->subjectRepository->findAll($user->id)->isEmpty()) {
            \DB::transaction(function () use ($user) {
                $this->subjectRepository->seedDefaults($user->id);
                $this->materialRepository->seedDefaults($user->id);
                $this->snippetRepository->seedDefaults($user->id);
            });
        }

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
