<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_monthly_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->text('goal')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'subject_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_monthly_goals');
    }
};
