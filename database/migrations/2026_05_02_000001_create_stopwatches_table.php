<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stopwatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 将来の複数セッション対応用。現時点は常に 'default'
            $table->string('session_key', 64)->default('default');
            $table->boolean('is_running')->default(false);
            $table->dateTime('started_at')->nullable();
            $table->unsignedInteger('elapsed_seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'session_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stopwatches');
    }
};
