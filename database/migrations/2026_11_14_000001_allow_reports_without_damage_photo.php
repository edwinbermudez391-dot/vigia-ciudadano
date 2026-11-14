<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('damage_photo_url', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        // No se descartan reportes sin foto al revertir la migración.
        \Illuminate\Support\Facades\DB::table('reports')->whereNull('damage_photo_url')->update(['damage_photo_url' => '']);
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('damage_photo_url', 500)->nullable(false)->change();
        });
    }
};
