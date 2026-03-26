<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->uuid('pipeline_id')->nullable()->after('trigger_type');
            $table->index('pipeline_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->dropIndex(['pipeline_id']);
            $table->dropColumn('pipeline_id');
        });
    }
};
