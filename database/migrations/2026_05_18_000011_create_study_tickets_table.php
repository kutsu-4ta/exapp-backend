<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->string('title');
            $table->text('acceptance_criteria');
            $table->date('due_date');
            $table->string('status')->default('todo');     // todo | doing | done
            $table->string('priority')->default('medium'); // high | medium | low
            $table->string('ticket_type');                 // knowledge | practice | understanding | memorization
            $table->string('source')->default('manual');   // wrong_answer | load_map | review | manual
            $table->unsignedSmallInteger('estimate_minutes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['sprint_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_tickets');
    }
};
