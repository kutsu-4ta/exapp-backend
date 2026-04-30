<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order');
            $table->string('display_id');
            $table->boolean('is_sub')->default(false);
            $table->boolean('has_children')->default(false);
            $table->string('rank', 1);
            $table->string('my_answer');
            $table->boolean('is_correct')->nullable();
            $table->boolean('is_doubtful')->default(false);
            $table->integer('point')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['exam_session_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
