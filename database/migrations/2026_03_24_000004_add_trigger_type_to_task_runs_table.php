<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->string('trigger_type')->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('task_runs', function (Blueprint $table): void {
            $table->dropColumn('trigger_type');
        });
    }
};
