<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nickname')->nullable();
            $table->string('occupation')->nullable();
            $table->text('goal')->nullable();
            $table->text('weak_areas')->nullable();
            $table->text('strong_areas')->nullable();
            $table->text('interests')->nullable();
            $table->text('gemini_token')->nullable(); // 暗号化して保存
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
