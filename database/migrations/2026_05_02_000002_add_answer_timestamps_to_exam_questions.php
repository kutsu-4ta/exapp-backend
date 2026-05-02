<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            // answered_time_ms はすでに存在するため追加しない
            $table->dateTime('answered_started_at')->nullable()->after('answered_time_ms');
            $table->dateTime('answered_finished_at')->nullable()->after('answered_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn(['answered_started_at', 'answered_finished_at']);
        });
    }
};
