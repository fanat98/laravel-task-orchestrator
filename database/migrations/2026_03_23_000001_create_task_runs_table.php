<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('task_name');
            $table->string('task_label');
            $table->string('command');
            $table->string('status');
            $table->unsignedInteger('progress_current')->nullable();
            $table->unsignedInteger('progress_total')->nullable();
            $table->string('progress_message')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['task_name']);
            $table->index(['status']);
            $table->index(['started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_runs');
    }
};
