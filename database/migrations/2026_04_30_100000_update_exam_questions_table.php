<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_questions', 'answered_time_ms')) {
                $table->bigInteger('answered_time_ms')->nullable()->after('note');
            }
            $table->string('my_answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_questions', 'answered_time_ms')) {
                $table->dropColumn('answered_time_ms');
            }
            $table->string('my_answer')->nullable(false)->change();
        });
    }
};
