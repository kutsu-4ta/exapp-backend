<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// 認証（公開）
Route::post('auth/google', [AuthController::class, 'googleLogin']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);

// 認証必須
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // 科目マスタ
    Route::get('subjects', [SubjectController::class, 'index']);
    Route::post('subjects', [SubjectController::class, 'store']);
    Route::put('subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('subjects/{id}', [SubjectController::class, 'destroy']);

    // デイリーログ
    Route::get('daily-logs', [DailyLogController::class, 'index']);
    Route::post('daily-logs', [DailyLogController::class, 'store']);
    Route::get('daily-logs/{date}', [DailyLogController::class, 'show']);
    Route::put('daily-logs/{date}', [DailyLogController::class, 'update']);
    Route::post('daily-logs/{date}/complete', [DailyLogController::class, 'complete']);
    Route::post('daily-logs/{date}/uncomplete', [DailyLogController::class, 'uncomplete']);

    // 学習ブロック
    Route::post('study-sessions', [StudySessionController::class, 'store']);
    Route::put('study-sessions/{id}', [StudySessionController::class, 'update']);
    Route::delete('study-sessions/{id}', [StudySessionController::class, 'destroy']);

    // 苦手問題
    Route::get('problems', [ProblemController::class, 'index']);
    Route::post('problems', [ProblemController::class, 'store']);
    Route::put('problems/{id}', [ProblemController::class, 'update']);
    Route::delete('problems/{id}', [ProblemController::class, 'destroy']);

    // ダッシュボード
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
});

// ヘルスチェック
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'exapp-api',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/health/db', function () {
    $checks = [];

    try {
        DB::select('SELECT 1');
        $checks['postgres'] = 'ok';
    } catch (\Throwable $e) {
        $checks['postgres'] = 'error: '.$e->getMessage();
    }

    $allOk = collect($checks)->every(fn ($v) => $v === 'ok');

    return response()->json([
        'status' => $allOk ? 'ok' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $allOk ? 200 : 503);
});
