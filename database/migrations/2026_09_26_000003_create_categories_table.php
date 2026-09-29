<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos de daño/ámbito de reporte, agrupados por dependencia.
 * Depende de: departments.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('sla_hours')
                ->comment('Plazo máximo de atención en horas');
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // El mismo nombre no puede repetirse dentro de una dependencia.
            $table->unique(['department_id', 'name']);
            // Catálogo por dependencia activa.
            $table->index(['department_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
