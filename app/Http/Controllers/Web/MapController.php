<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Mapa interactivo público: visualización geográfica de todos los reportes.
 *
 * Endpoint abierto (sin auth) que renderiza una vista con Leaflet.js
 * mostrando todos los reportes activos con clustering, marcadores de colores
 * según estado y popups interactivos.
 */
final class MapController extends Controller
{
    public function __invoke(): View
    {
        return view('map');
    }
}
