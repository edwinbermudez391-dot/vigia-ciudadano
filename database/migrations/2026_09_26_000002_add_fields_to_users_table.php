<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la tabla base de usuarios con la información propia del dominio
 * de "Vigía Ciudadano".
 *
 * Se ejecuta como ALTER y DESPUÉS de create_departments_table porque
 * department_id es una clave foránea hacia departments (InnoDB exige que la
 * tabla referenciada exista antes de declarar la restricción). Por el mismo
 * motivo no se añaden aquí los campos del scaffold base 0001_01_01_000000.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('email')
                ->comment('Dependencia a la que pertenece el funcionario (null para ciudadanos)')
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('phone', 30)
                ->after('email');

            $table->enum('role', ['superadmin', 'citizen', 'contractor', 'quality_inspector'])
                ->default('citizen')
                ->after('password');

            $table->boolean('is_active')
                ->default(true)
                ->after('role');

            // Filtro habitual: usuarios activos por rol y por dependencia.
            // department_id no necesita un índice explícito adicional:
            // la propia restricción foránea crea uno en InnoDB.
            $table->index(['role', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Primero se retiran los índices explícitos dependientes de columnas.
            $table->dropIndex(['role', 'is_active']);

            // Luego la clave foránea y las columnas en orden inverso.
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['is_active', 'role', 'phone']);
        });
    }
};
