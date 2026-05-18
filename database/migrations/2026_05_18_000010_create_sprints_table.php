<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('active'); // backlog | active
            $table->string('status')->default('active'); // active | completed
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        $this->createBacklogUniqueIndex();
    }

    private function createBacklogUniqueIndex(): void
    {
        // ユーザーごとにバックログスプリントは1件のみ許可する partial unique index
        DB::statement("CREATE UNIQUE INDEX sprints_user_backlog_unique ON sprints (user_id) WHERE type = 'backlog'");
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
