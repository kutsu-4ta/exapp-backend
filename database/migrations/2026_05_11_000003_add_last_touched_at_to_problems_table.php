<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->date('last_touched_at')->nullable()->after('solved_at');
            $table->index(['user_id', 'subject_id', 'last_touched_at']);
        });
    }

    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'subject_id', 'last_touched_at']);
            $table->dropColumn('last_touched_at');
        });
    }
};
