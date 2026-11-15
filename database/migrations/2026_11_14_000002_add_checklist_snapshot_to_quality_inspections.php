<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_inspections', function (Blueprint $table): void {
            $table->json('checklist_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quality_inspections', function (Blueprint $table): void {
            $table->dropColumn('checklist_snapshot');
        });
    }
};
