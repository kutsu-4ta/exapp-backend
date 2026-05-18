<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sub_categories', function (Blueprint $table) {
            $table->foreignId('ticket_id')->constrained('study_tickets')->cascadeOnDelete();
            $table->foreignId('sub_category_id')->constrained('sub_categories')->cascadeOnDelete();

            $table->primary(['ticket_id', 'sub_category_id']);
            $table->index('sub_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sub_categories');
    }
};
