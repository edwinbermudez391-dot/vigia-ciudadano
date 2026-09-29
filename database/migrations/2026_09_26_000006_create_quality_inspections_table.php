<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inspección de calidad sobre una orden de trabajo terminada.
 * Depende de: work_orders (work_order_id) y users (inspector_id).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_order_id')
                ->constrained('work_orders')
                ->cascadeOnDelete();

            $table->foreignId('inspector_id')
                ->comment('Usuario con rol quality_inspector que valida la obra')
                ->constrained('users')
                ->restrictOnDelete();

            // Checklist flexible: respuesta por cada ítem evaluado.
            $table->json('checklist_results');

            $table->enum('decision', ['approved', 'rework']);
            $table->text('rejection_reason')->nullable();
            $table->string('verification_photo_url', 500);

            // Posición GPS del inspector en el momento de la validación.
            $table->decimal('inspector_latitude', 10, 8);
            $table->decimal('inspector_longitude', 11, 8);

            $table->dateTime('inspected_at');
            $table->timestamps();

            // Trazabilidad de inspecciones por orden y decisión.
            $table->index(['work_order_id', 'decision']);
            // Auditoría temporal por inspector.
            $table->index(['inspector_id', 'inspected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
    }
};
