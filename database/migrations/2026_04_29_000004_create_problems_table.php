<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('material');
            $table->string('question_ref');
            $table->text('note')->nullable();
            $table->string('proficiency');
            $table->jsonb('failure_types');
            $table->boolean('is_good_question')->default(false);
            $table->date('solved_at');
            $table->timestamps();

            $table->index(['user_id', 'subject_id']);
            $table->index(['user_id', 'solved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problems');
    }
};
