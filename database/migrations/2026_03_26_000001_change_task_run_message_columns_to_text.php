<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->text('progress_message')->nullable()->change();
            $table->text('failure_message')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->string('progress_message', 255)->nullable()->change();
            $table->string('failure_message', 255)->nullable()->change();
        });
    }
};
