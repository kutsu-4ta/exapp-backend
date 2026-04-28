<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// 認証
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
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
        $checks['mysql'] = 'ok';
    } catch (\Throwable $e) {
        $checks['mysql'] = 'error: '.$e->getMessage();
    }

    try {
        Redis::ping();
        $checks['redis'] = 'ok';
    } catch (\Throwable $e) {
        $checks['redis'] = 'error: '.$e->getMessage();
    }

    $allOk = collect($checks)->every(fn ($v) => $v === 'ok');

    return response()->json([
        'status' => $allOk ? 'ok' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $allOk ? 200 : 503);
});
