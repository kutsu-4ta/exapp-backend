<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 教材マスターテーブル
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // インデックス設計
            $table->unique(['user_id', 'name']); // 同一ユーザー内の重複防止
            $table->index(['user_id', 'display_order']);
        });

        // 2. 学習セッション側へのカラム追加（既存テーブルへの追加を想定）
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->foreignId('material_id')
                ->nullable()
                ->after('subject_id')
                ->constrained()
                ->onDelete('set null'); // 教材を消しても学習実績は残す
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropForeign(['material_id']);
            $table->dropColumn('material_id');
        });
        Schema::dropIfExists('materials');
    }
};
