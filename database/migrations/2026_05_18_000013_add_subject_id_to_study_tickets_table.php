<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_tickets', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('sprint_id')->constrained('subjects')->nullOnDelete();
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::table('study_tickets', function (Blueprint $table) {
            $table->dropForeignIdFor('subjects');
            $table->dropColumn('subject_id');
        });
    }
};
