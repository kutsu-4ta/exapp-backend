<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('problems', 'defeat_reason')) {
            Schema::table('problems', function (Blueprint $table) {
                $table->dropColumn('defeat_reason');
            });
        }
    }

    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->string('defeat_reason')->nullable();
        });
    }
};
