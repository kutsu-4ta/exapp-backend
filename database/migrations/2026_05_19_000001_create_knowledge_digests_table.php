<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_digests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('definitions')->nullable();
            $table->jsonb('formulas')->nullable();
            $table->jsonb('keywords')->nullable();
            $table->jsonb('pitfalls')->nullable();
            $table->jsonb('examples')->nullable();
            $table->jsonb('relations')->nullable();
            $table->jsonb('memory_hooks')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_digests');
    }
};
