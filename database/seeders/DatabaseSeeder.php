<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Orquesta el poblado maestro de "Vigía Ciudadano".
 *
 * El orden de llamadas respeta las dependencias de clave foránea:
 * departments -> categories -> users. Cada seeder es idempotente
 * (updateOrCreate sobre su clave natural), así que
 * `php artisan db:seed` puede re-ejecutarse sin duplicar datos.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
        ]);
    }
}
