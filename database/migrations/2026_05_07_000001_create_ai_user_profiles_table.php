<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();

            // 英語化済みスカラー値
            $table->string('occupation_en')->nullable();
            $table->string('goal_en')->nullable();
            $table->string('interests_en')->nullable();
            $table->string('strong_areas_en')->nullable();

            // 英語化済み配列値（machine-oriented）
            $table->jsonb('weak_subjects_json')->nullable();   // ['commercial law', 'SME policy']
            $table->jsonb('study_style_json')->nullable();     // {'weeklyTargetH': 45}

            // Gemini へそのまま投入できる compact 構造
            $table->jsonb('normalized_prompt_json')->nullable();

            // 翻訳ロジック変更時に再生成要否を判定するバージョン番号
            $table->unsignedSmallInteger('translation_version')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_profiles');
    }
};
