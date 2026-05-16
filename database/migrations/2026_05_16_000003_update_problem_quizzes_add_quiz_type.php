<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problem_quizzes', function (Blueprint $table) {
            $table->string('quiz_type', 20)->default('multiple_choice')->after('problem_id');
            $table->integer('correct_index')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('problem_quizzes', function (Blueprint $table) {
            $table->dropColumn('quiz_type');
            $table->integer('correct_index')->nullable(false)->change();
        });
    }
};
