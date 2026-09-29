<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla raíz de la jerarquía organizacional.
 * Debe ejecutarse ANTES de las migraciones que referencian departments
 * (users.department_id y categories.department_id).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->comment('Nombre de la dependencia (p. ej. Movilidad, Servicio Ciudadano)');
            $table->string('code', 20)->unique()->comment('Código corto único, usado en prefijos de ticket');
            $table->string('contact_email');
            $table->string('phone', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
