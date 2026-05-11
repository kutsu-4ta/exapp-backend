<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_user_profiles', function (Blueprint $table) {
            $table->string('gemini_model')->nullable()->after('translation_version');
        });
    }

    public function down(): void
    {
        Schema::table('ai_user_profiles', function (Blueprint $table) {
            $table->dropColumn('gemini_model');
        });
    }
};
