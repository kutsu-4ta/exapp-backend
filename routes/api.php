<?php

use App\Http\Controllers\AiAdviceController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\AlertSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\MonthlySettingController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\StopwatchController;
use App\Http\Controllers\SubCategoryController;
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

    // プロフィール
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

    // 科目
    Route::get('subjects', [SubjectController::class, 'index']);
    Route::put('subjects/{name}', [SubjectController::class, 'update']);
    Route::delete('subjects/{name}', [SubjectController::class, 'destroy']);

    // 小分類
    Route::get('sub-categories', [SubCategoryController::class, 'index']);
    Route::post('sub-categories', [SubCategoryController::class, 'store']);
    Route::put('sub-categories/{id}', [SubCategoryController::class, 'update']);
    Route::delete('sub-categories/{id}', [SubCategoryController::class, 'destroy']);

    // 教材
    Route::get('materials', [MaterialController::class, 'index']);
    Route::put('materials/{name}', [MaterialController::class, 'update']);
    Route::delete('materials/{name}', [MaterialController::class, 'destroy']);

    // アラート設定
    Route::get('alert-settings', [AlertSettingController::class, 'show']);
    Route::put('alert-settings', [AlertSettingController::class, 'upsert']);

    // 月間設定
    Route::get('monthly-settings/{year}/{month}', [MonthlySettingController::class, 'show']);
    Route::put('monthly-settings/{year}/{month}', [MonthlySettingController::class, 'upsert']);

    // デイリーログ
    Route::get('daily-logs', [DailyLogController::class, 'index']);
    Route::get('daily-logs/recent', [DailyLogController::class, 'recent']);
    Route::post('daily-logs', [DailyLogController::class, 'store']);
    Route::get('daily-logs/{date}', [DailyLogController::class, 'show']);
    Route::put('daily-logs/{date}', [DailyLogController::class, 'update']);
    Route::delete('daily-logs/{date}', [DailyLogController::class, 'destroy']);
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

    // AIアドバイス
    Route::post('ai/advice', [AiAdviceController::class, 'advice']);

    // AI画像解析
    Route::post('ai/analysis', [AnalysisController::class, 'analyzeProblem']);

    // ストップウォッチ
    Route::get('stopwatch', [StopwatchController::class, 'show']);
    Route::post('stopwatch/start', [StopwatchController::class, 'start']);
    Route::post('stopwatch/stop', [StopwatchController::class, 'stop']);

    // 試験セッション
    Route::get('exam-sessions', [ExamSessionController::class, 'index']);
    Route::post('exam-sessions', [ExamSessionController::class, 'store']);
    Route::get('exam-sessions/{id}', [ExamSessionController::class, 'show']);
    Route::put('exam-sessions/{id}', [ExamSessionController::class, 'update']);
    Route::delete('exam-sessions/{id}', [ExamSessionController::class, 'destroy']);
    Route::post('exam-sessions/{id}/complete', [ExamSessionController::class, 'complete']);
    Route::patch('exam-sessions/{id}/questions/{sortOrder}', [ExamSessionController::class, 'updateQuestion']);

    // 科目別試験分析
    Route::get('exam-subjects/{subject}/stats', [ExamSessionController::class, 'subjectStats']);
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
