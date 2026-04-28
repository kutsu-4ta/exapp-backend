<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('time_slot');
            $table->unsignedSmallInteger('minutes');
            $table->string('material');
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['daily_log_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
