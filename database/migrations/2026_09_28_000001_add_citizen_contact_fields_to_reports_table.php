<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite radicación pública de reportes (ciudadanos sin cuenta).
 *
 * user_id ya era nullable en la migración original (para soportar cuentas
 * eliminadas). Agregamos campos de contacto opcionales para que el ciudadano
 * pueda recibir novedades sin necesidad de crear una cuenta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('citizen_name', 120)
                ->nullable()
                ->after('user_id')
                ->comment('Nombre del ciudadano (opcional, para reportes públicos)');

            $table->string('citizen_phone', 25)
                ->nullable()
                ->after('citizen_name')
                ->comment('Teléfono de contacto (opcional)');

            $table->string('citizen_email', 255)
                ->nullable()
                ->after('citizen_phone')
                ->comment('Correo electrónico (opcional)');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['citizen_name', 'citizen_phone', 'citizen_email']);
        });
    }
};
