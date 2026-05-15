<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->boolean('touch_alert_enabled')->default(true);
            $table->unsignedSmallInteger('threshold_days')->default(7);
            $table->boolean('include_untouched')->default(false);
            $table->boolean('minutes_alert_enabled')->default(false);
            $table->unsignedSmallInteger('minutes_threshold_days')->default(7);
            $table->unsignedSmallInteger('minutes_threshold')->default(60);
            $table->timestamps();

            $table->unique(['user_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_alert_settings');
    }
};
