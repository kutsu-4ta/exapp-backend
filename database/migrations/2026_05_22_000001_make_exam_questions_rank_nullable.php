<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->string('rank', 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        // 既存データの null を 'B' で埋めてから非 nullable に戻す
        \DB::statement("UPDATE exam_questions SET rank = 'B' WHERE rank IS NULL");
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->string('rank', 1)->nullable(false)->change();
        });
    }
};
