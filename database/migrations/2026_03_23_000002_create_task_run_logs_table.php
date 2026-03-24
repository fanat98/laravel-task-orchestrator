<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_run_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('task_run_id');
            $table->string('level')->default('info');
            $table->text('message');
            $table->timestamps();

            $table->index(['task_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_run_logs');
    }
};
