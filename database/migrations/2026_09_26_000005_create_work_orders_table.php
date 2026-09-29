<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de trabajo asignada a un contratista para reparar un reporte.
 * Depende de: reports (report_id) y users (contractor_id).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();

            // Relación 1:1 con el reporte: un reporte tiene a lo sumo una orden.
            // El índice único se declara en la cadena de la columna (fluent index).
            $table->foreignId('report_id')
                ->unique()
                ->comment('Reporte ciudadano que originó la orden')
                ->constrained('reports')
                ->cascadeOnDelete();

            $table->foreignId('contractor_id')
                ->comment('Usuario con rol contractor responsable de la reparación')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('scheduled_date');
            $table->dateTime('completion_date')->nullable();
            $table->text('work_description')->nullable();
            $table->string('solution_photo_url', 500)->nullable();

            $table->enum('status', ['assigned', 'in_execution', 'completed'])
                ->default('assigned');

            $table->timestamps();

            // Agenda del contratista por estado.
            $table->index(['contractor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
