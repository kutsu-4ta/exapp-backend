<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 依存データを削除（フレッシュデプロイ前提）
        DB::table('exam_questions')->delete();
        DB::table('exam_sessions')->delete();
        DB::table('problems')->delete();
        DB::table('study_sessions')->delete();
        DB::table('sub_categories')->delete();
        DB::table('subjects')->delete();

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'name']);
            $table->dropColumn('user_id');
            $table->unique('name');
        });
    }
};
