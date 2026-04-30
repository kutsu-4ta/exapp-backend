<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->unsignedBigInteger('subject_id')->nullable()->change();
            $table->foreign('subject_id')->references('id')->on('subjects')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->unsignedBigInteger('subject_id')->nullable(false)->change();
            $table->foreign('subject_id')->references('id')->on('subjects')->restrictOnDelete();
        });
    }
};
