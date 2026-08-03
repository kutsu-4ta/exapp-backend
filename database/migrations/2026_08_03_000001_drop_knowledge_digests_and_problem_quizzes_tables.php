<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('knowledge_digests');
        Schema::dropIfExists('problem_quizzes');
    }

    public function down(): void
    {
        Schema::create('problem_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained()->cascadeOnDelete();
            $table->string('quiz_type', 20)->default('multiple_choice');
            $table->text('question');
            $table->json('options');
            $table->integer('correct_index')->nullable();
            $table->text('explanation');
            $table->timestamps();
        });

        Schema::create('knowledge_digests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('definitions')->nullable();
            $table->jsonb('formulas')->nullable();
            $table->jsonb('keywords')->nullable();
            $table->jsonb('pitfalls')->nullable();
            $table->jsonb('examples')->nullable();
            $table->jsonb('relations')->nullable();
            $table->jsonb('memory_hooks')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();
        });
    }
};
