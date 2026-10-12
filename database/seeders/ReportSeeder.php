<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Reportes ciudadanos de prueba en todos los estados del flujo.
 *
 * Genera 11 reportes con ticket codes REP-2026-0001 a REP-2026-0011,
 * coordenadas GPS reales de Bogotá (Colombia) y distribuidos en los 5
 * estados del ciclo de vida: reported, in_progress, under_inspection,
 * rework y closed.
 *
 * Requiere que DepartmentSeeder, CategorySeeder y UserSeeder se hayan
 * ejecutado primero (así lo garantiza DatabaseSeeder).
 */
class ReportSeeder extends Seeder
{
    /**
     * Coordenadas GPS reales de localidades de Bogotá, Colombia.
     * Formato: [latitude, longitude, referencia de dirección]
     */
    private const LOCATIONS = [
        ['4.60971000', '-74.08174000', 'Plaza de Bolívar, Centro Histórico'],
        ['4.65418000', '-74.06362000', 'Calle 53 con Carrera 15, Chapinero'],
        ['4.74196000', '-74.10235000', 'Avenida Suba con Calle 146, Suba'],
        ['4.61897000', '-74.12378000', 'Avenida Kennedy con Carrera 72, Kennedy'],
        ['4.70134000', '-74.04153000', 'Parque Usaquén, Carrera 6 con Calle 119'],
        ['4.68694000', '-74.10892000', 'Avenida Engativá con Calle 80, Engativá'],
        ['4.63218000', '-74.08354000', 'Parque de la 93, Teusaquillo'],
        ['4.66018000', '-74.12734000', 'Avenida Calle 26 con Carrera 100, Fontibón'],
        ['4.60830000', '-74.06612000', 'Parque Santa Fe, Carrera 1 con Calle 12'],
        ['4.65123000', '-74.09876000', 'Centro Comercial Andino, Zona Rosa'],
        ['4.69543000', '-74.05123000', 'Calle 170 con Autopista Norte, Usaquén'],
    ];

    public function run(): void
    {
        $citizens = User::query()->where('role', 'citizen')->pluck('id')->all();

        if (count($citizens) < 5) {
            throw new RuntimeException(
                'Se requieren al menos 5 ciudadanos. Ejecuta UserSeeder antes de ReportSeeder.'
            );
        }

        $categories = $this->loadCategories();

        $reports = [
            // Reportes en estado "reported" (sin orden de trabajo)
            [
                'ticket_code' => 'REP-2026-0001',
                'user_id' => null, // Anónimo
                'citizen_name' => 'Juan Pérez',
                'citizen_phone' => '+57 320 999 1111',
                'citizen_email' => 'jperez@example.com',
                'category_id' => $categories['Baches y Malla Vial'],
                'title' => 'Bache peligroso en vía principal',
                'description' => 'Bache de aproximadamente 1 metro de diámetro y 30 cm de profundidad que representa riesgo para vehículos y motociclistas.',
                'damage_photo_url' => '/storage/reports/damage/bache-0001.jpg',
                'latitude' => self::LOCATIONS[0][0],
                'longitude' => self::LOCATIONS[0][1],
                'address_reference' => self::LOCATIONS[0][2],
                'status' => ReportStatus::Reported,
                'created_at' => now()->subDays(10),
            ],
            [
                'ticket_code' => 'REP-2026-0002',
                'user_id' => null, // Anónimo
                'citizen_name' => 'María González',
                'citizen_phone' => '+57 320 999 2222',
                'citizen_email' => 'mgonzalez@example.com',
                'category_id' => $categories['Luminaria Apagada o en Falla'],
                'title' => 'Luminaria apagada desde hace 3 días',
                'description' => 'La luminaria frente al parque está completamente apagada, generando inseguridad en la zona.',
                'damage_photo_url' => '/storage/reports/damage/luminaria-0002.jpg',
                'latitude' => self::LOCATIONS[1][0],
                'longitude' => self::LOCATIONS[1][1],
                'address_reference' => self::LOCATIONS[1][2],
                'status' => ReportStatus::Reported,
                'created_at' => now()->subDays(8),
            ],

            // Reportes en estado "in_progress" (con orden de trabajo asignada o en ejecución)
            [
                'ticket_code' => 'REP-2026-0003',
                'user_id' => $citizens[0],
                'category_id' => $categories['Fuga de Agua Potable'],
                'title' => 'Fuga de agua en acera',
                'description' => 'Fuga constante de agua potable que está inundando la acera y la vía.',
                'damage_photo_url' => '/storage/reports/damage/fuga-0003.jpg',
                'latitude' => self::LOCATIONS[2][0],
                'longitude' => self::LOCATIONS[2][1],
                'address_reference' => self::LOCATIONS[2][2],
                'status' => ReportStatus::InProgress,
                'created_at' => now()->subDays(7),
            ],
            [
                'ticket_code' => 'REP-2026-0004',
                'user_id' => $citizens[1],
                'category_id' => $categories['Semáforo en Falla'],
                'title' => 'Semáforo desincronizado',
                'description' => 'El semáforo del cruce permanece en rojo para todas las direcciones simultáneamente.',
                'damage_photo_url' => '/storage/reports/damage/semaforo-0004.jpg',
                'latitude' => self::LOCATIONS[3][0],
                'longitude' => self::LOCATIONS[3][1],
                'address_reference' => self::LOCATIONS[3][2],
                'status' => ReportStatus::InProgress,
                'created_at' => now()->subDays(6),
            ],

            // Reportes en estado "under_inspection" (orden completada, esperando inspección)
            [
                'ticket_code' => 'REP-2026-0005',
                'user_id' => $citizens[2],
                'category_id' => $categories['Árbol en Riesgo de Caída'],
                'title' => 'Árbol inclinado sobre vía',
                'description' => 'Árbol de gran tamaño inclinado peligrosamente sobre la calzada, riesgo inminente de caída.',
                'damage_photo_url' => '/storage/reports/damage/arbol-0005.jpg',
                'latitude' => self::LOCATIONS[4][0],
                'longitude' => self::LOCATIONS[4][1],
                'address_reference' => self::LOCATIONS[4][2],
                'status' => ReportStatus::UnderInspection,
                'created_at' => now()->subDays(5),
            ],
            [
                'ticket_code' => 'REP-2026-0006',
                'user_id' => $citizens[3],
                'category_id' => $categories['Alcantarillado o Sumidero Tapado'],
                'title' => 'Sumidero completamente tapado',
                'description' => 'Sumidero obstruido que causa inundación de la vía cada vez que llueve.',
                'damage_photo_url' => '/storage/reports/damage/sumidero-0006.jpg',
                'latitude' => self::LOCATIONS[5][0],
                'longitude' => self::LOCATIONS[5][1],
                'address_reference' => self::LOCATIONS[5][2],
                'status' => ReportStatus::UnderInspection,
                'created_at' => now()->subDays(4),
            ],

            // Reportes en estado "closed" (orden completada e inspección aprobada)
            [
                'ticket_code' => 'REP-2026-0007',
                'user_id' => $citizens[4],
                'category_id' => $categories['Andenes Deteriorados'],
                'title' => 'Andén hundido con baldosas rotas',
                'description' => 'Tramo de andén de 10 metros con múltiples baldosas rotas y hundimiento de 15 cm.',
                'damage_photo_url' => '/storage/reports/damage/anden-0007.jpg',
                'latitude' => self::LOCATIONS[6][0],
                'longitude' => self::LOCATIONS[6][1],
                'address_reference' => self::LOCATIONS[6][2],
                'status' => ReportStatus::Closed,
                'created_at' => now()->subDays(15),
            ],
            [
                'ticket_code' => 'REP-2026-0008',
                'user_id' => $citizens[0],
                'category_id' => $categories['Basuras y Escombros en Vía Pública'],
                'title' => 'Escombros de construcción en esquina',
                'description' => 'Escombros de una demolición abandonados en la esquina desde hace más de una semana.',
                'damage_photo_url' => '/storage/reports/damage/escombros-0008.jpg',
                'latitude' => self::LOCATIONS[7][0],
                'longitude' => self::LOCATIONS[7][1],
                'address_reference' => self::LOCATIONS[7][2],
                'status' => ReportStatus::Closed,
                'created_at' => now()->subDays(14),
            ],
            [
                'ticket_code' => 'REP-2026-0009',
                'user_id' => $citizens[1],
                'category_id' => $categories['Sin Suministro de Agua'],
                'title' => 'Sin agua potable desde hace 2 días',
                'description' => 'Toda la manzana lleva 2 días sin servicio de agua sin aviso previo de la empresa.',
                'damage_photo_url' => '/storage/reports/damage/agua-0009.jpg',
                'latitude' => self::LOCATIONS[8][0],
                'longitude' => self::LOCATIONS[8][1],
                'address_reference' => self::LOCATIONS[8][2],
                'status' => ReportStatus::Closed,
                'created_at' => now()->subDays(13),
            ],

            // Reportes en estado "rework" (inspección rechazada, necesita ajustes)
            [
                'ticket_code' => 'REP-2026-0010',
                'user_id' => $citizens[2],
                'category_id' => $categories['Señalización Vial Deteriorada'],
                'title' => 'Señales de tránsito dañadas',
                'description' => 'Tres señales de tránsito principales están dobladas y prácticamente invisibles.',
                'damage_photo_url' => '/storage/reports/damage/señal-0010.jpg',
                'latitude' => self::LOCATIONS[9][0],
                'longitude' => self::LOCATIONS[9][1],
                'address_reference' => self::LOCATIONS[9][2],
                'status' => ReportStatus::Rework,
                'created_at' => now()->subDays(12),
            ],
            [
                'ticket_code' => 'REP-2026-0011',
                'user_id' => $citizens[3],
                'category_id' => $categories['Cableado Expuesto'],
                'title' => 'Cables colgantes sobre la calle',
                'description' => 'Cables de electricidad colgantes a 2 metros de altura, riesgo de electrocución.',
                'damage_photo_url' => '/storage/reports/damage/cable-0011.jpg',
                'latitude' => self::LOCATIONS[10][0],
                'longitude' => self::LOCATIONS[10][1],
                'address_reference' => self::LOCATIONS[10][2],
                'status' => ReportStatus::Rework,
                'created_at' => now()->subDays(11),
            ],
        ];

        foreach ($reports as $report) {
            Report::updateOrCreate(
                ['ticket_code' => $report['ticket_code']],
                $report,
            );
        }
    }

    /**
     * Carga todas las categorías y las indexa por nombre para referencia rápida.
     */
    private function loadCategories(): array
    {
        $categories = Category::query()->get()->keyBy('name');

        if ($categories->isEmpty()) {
            throw new RuntimeException(
                'No se encontraron categorías. Ejecuta CategorySeeder antes de ReportSeeder.'
            );
        }

        return $categories->map(fn ($cat) => $cat->id)->all();
    }
}
