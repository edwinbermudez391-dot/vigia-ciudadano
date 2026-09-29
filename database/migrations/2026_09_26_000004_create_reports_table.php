<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reporte ciudadano: corazón del sistema.
 * Depende de: users (user_id) y categories (category_id).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 40)->unique()
                ->comment('Código público único de seguimiento');

            $table->foreignId('user_id')
                ->nullable()
                ->comment('Ciudadano que reporta; se anula si la cuenta se elimina')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('title', 180);
            $table->text('description');
            $table->string('damage_photo_url', 500);

            // Coordenadas WGS84. Precisión DECIMAL fija: 8 decimales (~1 mm).
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            $table->string('address_reference', 255);

            $table->enum('status', [
                'reported',
                'in_progress',
                'under_inspection',
                'rework',
                'closed',
            ])->default('reported');

            $table->timestamps();

            // Índice compuesto principal para el tablero por estado + categoría.
            $table->index(['status', 'category_id']);
            // Filtro de "mis reportes" ordenado por fecha.
            $table->index(['user_id', 'created_at']);
            // Búsquedas geoespaciales por bounding box (lat/long).
            // Un índice SPATIAL real requeriría una columna GEOMETRY NOT NULL;
            // con coordenadas decimales este índice B-tree compuesto acelera
            // los rangos rectangulares típicos de mapa.
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
