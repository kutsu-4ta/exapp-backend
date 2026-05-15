<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // quick-score で直接記録するスコア（通常セッションは questions から計算するため null）
            $table->unsignedSmallInteger('total_score')->nullable()->after('completed_at');
            $table->unsignedSmallInteger('pure_score')->nullable()->after('total_score');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['total_score', 'pure_score']);
        });
    }
};
