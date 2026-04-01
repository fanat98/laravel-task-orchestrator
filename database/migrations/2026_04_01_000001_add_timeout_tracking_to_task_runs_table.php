<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->unsignedInteger('timeout_seconds')->nullable()->after('pipeline_id');
            $table->timestamp('timeout_at')->nullable()->after('started_at');

            $table->index(['timeout_at']);
        });
    }

    public function down(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->dropIndex(['timeout_at']);
            $table->dropColumn(['timeout_seconds', 'timeout_at']);
        });
    }
};

