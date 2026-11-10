<?php

declare(strict_types=1);

/**
 * Genera un archivo .docx (Word real, OOXML) con la documentación de
 * Vigía Ciudadano: qué es el proyecto, su objetivo y el detalle de una
 * funcionalidad terminada al 100% (inspección de calidad de obra).
 *
 * Uso: php generar_documento_word.php
 */

$outputFile = __DIR__ . '/Documentacion_Vigia_Ciudadano.docx';

// ---------------------------------------------------------------------------
// Utilidades XML
// ---------------------------------------------------------------------------

function x(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Convierte texto con **negritas** en runs OOXML.
 */
function runs(string $text, array $base = []): string
{
    $parts = preg_split('/(\*\*.*?\*\*)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
    $xml = '';

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        $bold = str_starts_with($part, '**') && str_ends_with($part, '**') && strlen($part) > 4;
        $content = $bold ? substr($part, 2, -2) : $part;

        $props = $base;
        if ($bold) {
            $props[] = '<w:b/>';
        }

        $rPr = $props === [] ? '' : '<w:rPr>' . implode('', $props) . '</w:rPr>';

        // Respeta saltos de línea internos.
        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $xml .= '<w:r>' . $rPr . '<w:br/></w:r>';
            }
            $xml .= '<w:r>' . $rPr . '<w:t xml:space="preserve">' . x($line) . '</w:t></w:r>';
        }
    }

    return $xml;
}

function paragraph(string $text, string $style = 'Normal', array $extraPPr = []): string
{
    $pPr = '<w:pPr><w:pStyle w:val="' . $style . '"/>' . implode('', $extraPPr) . '</w:pPr>';

    return '<w:p>' . $pPr . runs($text) . '</w:p>';
}

function bullet(string $text, string $marker = '•'): string
{
    $pPr = '<w:pPr><w:pStyle w:val="Normal"/>'
        . '<w:spacing w:after="80" w:line="276" w:lineRule="auto"/>'
        . '<w:ind w:left="420" w:hanging="240"/></w:pPr>';

    return '<w:p>' . $pPr
        . '<w:r><w:t xml:space="preserve">' . x($marker) . '  </w:t></w:r>'
        . runs($text)
        . '</w:p>';
}

function numbered(int $number, string $text): string
{
    $pPr = '<w:pPr><w:pStyle w:val="Normal"/>'
        . '<w:spacing w:after="80" w:line="276" w:lineRule="auto"/>'
        . '<w:ind w:left="420" w:hanging="300"/></w:pPr>';

    return '<w:p>' . $pPr
        . '<w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">' . $number . '.  </w:t></w:r>'
        . runs($text)
        . '</w:p>';
}

function pageBreak(): string
{
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
}

/**
 * @param  list<string>  $header
 * @param  list<list<string>>  $rows
 * @param  list<int>  $widths  Anchos en twips (A4 útil ≈ 9360)
 */
function table(array $header, array $rows, array $widths): string
{
    $border = '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
        . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
        . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
        . '</w:tblBorders>';

    $grid = '<w:tblGrid>';
    foreach ($widths as $width) {
        $grid .= '<w:gridCol w:w="' . $width . '"/>';
    }
    $grid .= '</w:tblGrid>';

    $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/>'
        . $border
        . '<w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="100" w:type="dxa"/>'
        . '<w:bottom w:w="80" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar>'
        . '</w:tblPr>' . $grid;

    $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($header as $index => $cell) {
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[$index] . '" w:type="dxa"/>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="1E40AF"/></w:tcPr>'
            . '<w:p><w:pPr><w:pStyle w:val="TableText"/><w:spacing w:before="40" w:after="40"/></w:pPr>'
            . runs('**' . $cell . '**', ['<w:color w:val="FFFFFF"/>'])
            . '</w:p></w:tc>';
    }
    $xml .= '</w:tr>';

    foreach ($rows as $rowIndex => $row) {
        $fill = $rowIndex % 2 === 0 ? 'F8FAFC' : 'FFFFFF';
        $xml .= '<w:tr>';
        foreach ($row as $index => $cell) {
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[$index] . '" w:type="dxa"/>'
                . '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/></w:tcPr>'
                . '<w:p><w:pPr><w:pStyle w:val="TableText"/><w:spacing w:before="40" w:after="40"/></w:pPr>'
                . runs($cell)
                . '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';
    }

    return $xml . '</w:tbl>' . paragraph('', 'Normal', ['<w:spacing w:after="120"/>']);
}

function codeBlock(string $code): string
{
    $xml = '';
    foreach (explode("\n", $code) as $line) {
        $xml .= '<w:p><w:pPr><w:pStyle w:val="Code"/>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/>'
            . '<w:pBdr><w:left w:val="single" w:sz="18" w:space="6" w:color="1E40AF"/></w:pBdr>'
            . '</w:pPr><w:r><w:t xml:space="preserve">' . x($line === '' ? ' ' : $line) . '</w:t></w:r></w:p>';
    }

    return $xml . paragraph('', 'Normal', ['<w:spacing w:after="120"/>']);
}

function callout(string $title, string $text, string $fill = 'EFF6FF', string $accent = '2563EB'): string
{
    return '<w:p><w:pPr><w:pStyle w:val="Normal"/>'
        . '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>'
        . '<w:pBdr><w:left w:val="single" w:sz="24" w:space="8" w:color="' . $accent . '"/></w:pBdr>'
        . '<w:spacing w:before="120" w:after="60"/><w:ind w:left="120" w:right="120"/></w:pPr>'
        . runs('**' . $title . '**') . '</w:p>'
        . '<w:p><w:pPr><w:pStyle w:val="Normal"/>'
        . '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>'
        . '<w:pBdr><w:left w:val="single" w:sz="24" w:space="8" w:color="' . $accent . '"/></w:pBdr>'
        . '<w:spacing w:after="180"/><w:ind w:left="120" w:right="120"/></w:pPr>'
        . runs($text) . '</w:p>';
}

// ---------------------------------------------------------------------------
// Contenido del documento
// ---------------------------------------------------------------------------

$blocks = [];

// ---------------------------- PORTADA --------------------------------------
$blocks[] = paragraph('VIGÍA CIUDADANO', 'CoverKicker');
$blocks[] = paragraph('Sistema integral de gestión de incidencias urbanas', 'CoverTitle');
$blocks[] = paragraph('Documento descriptivo del proyecto y de una funcionalidad terminada al 100%', 'CoverSubtitle');
$blocks[] = paragraph('', 'Normal', ['<w:spacing w:after="600"/>']);
$blocks[] = table(
    ['Dato', 'Detalle'],
    [
        ['Tipo de documento', 'Descripción funcional y técnica'],
        ['Proyecto', 'Vigía Ciudadano'],
        ['Tecnología base', 'Laravel 12 · PHP 8.2+ · SQLite · Blade + Tailwind CSS 4 · Vite 7'],
        ['Funcionalidad destacada', 'Inspección de calidad de obra (dictamen: aprobar o reproceso)'],
        ['Estado verificado', '24 pruebas automatizadas aprobadas (95 aserciones)'],
        ['Fecha de elaboración', date('d/m/Y')],
    ],
    [2400, 6960]
);
$blocks[] = paragraph('', 'Normal', ['<w:spacing w:after="600"/>']);
$blocks[] = paragraph('Documento generado a partir del código fuente y de la ejecución real de la suite de pruebas del repositorio.', 'CoverNote');
$blocks[] = pageBreak();

// ---------------------------- 1. RESUMEN ------------------------------------
$blocks[] = paragraph('1. ¿Qué es Vigía Ciudadano?', 'Heading1');
$blocks[] = paragraph(
    'Vigía Ciudadano es una aplicación web desarrollada con Laravel cuyo propósito es **gestionar el ciclo de vida completo de las incidencias urbanas**: desde que un ciudadano reporta un problema de infraestructura pública (un bache, una fuga de agua, una luminaria apagada, un andén deteriorado, un árbol en riesgo) hasta que la obra de reparación es ejecutada por un contratista y auditada por un inspector de calidad.'
);
$blocks[] = paragraph(
    'El sistema conecta a cuatro tipos de actores —ciudadanía, contratistas, inspectores de calidad y alcaldía— sobre un mismo expediente digital identificado con un código de ticket único (formato **REP-AAAA-NNNN**). Cada acción queda registrada con evidencia fotográfica, coordenadas GPS y marcas de tiempo, de modo que la gestión deja de ser informal y se vuelve **transparente, trazable y auditable**.'
);
$blocks[] = callout(
    'Propósito en una frase',
    'Convertir el reporte ciudadano en una orden de trabajo verificable, ejecutada con evidencia y cerrada únicamente cuando un inspector de calidad aprueba la obra en el sitio.'
);

// ---------------------------- 2. PROBLEMÁTICA ------------------------------
$blocks[] = paragraph('2. Problemática que resuelve', 'Heading1');
$blocks[] = paragraph('En la gestión tradicional de incidencias urbanas suelen aparecer estos problemas:');
$blocks[] = bullet('Los reportes se pierden en canales dispersos (teléfono, redes sociales, ventanilla) y no existe un expediente único.');
$blocks[] = bullet('El ciudadano no recibe respuesta ni puede saber en qué va su solicitud.');
$blocks[] = bullet('No hay control sobre qué contratista atiende cada caso ni en cuánto tiempo.');
$blocks[] = bullet('La obra se declara terminada sin evidencia verificable ni inspección independiente.');
$blocks[] = bullet('No existen métricas confiables para la alcaldía (SLA, carga operativa, calidad de las reparaciones).');
$blocks[] = paragraph('Vigía Ciudadano ataca esas fallas con un flujo único, estados explícitos, evidencia obligatoria y roles separados que se controlan entre sí.');

// ---------------------------- 3. OBJETIVOS ---------------------------------
$blocks[] = paragraph('3. Objetivo del proyecto', 'Heading1');
$blocks[] = paragraph('3.1 Objetivo general', 'Heading2');
$blocks[] = paragraph(
    'Diseñar e implementar un sistema web que permita **radicar, asignar, ejecutar y auditar** incidencias de infraestructura pública, garantizando trazabilidad de punta a punta, control de calidad de las obras y transparencia frente a la ciudadanía.'
);
$blocks[] = paragraph('3.2 Objetivos específicos', 'Heading2');
$blocks[] = numbered(1, 'Permitir que cualquier ciudadano radique una incidencia **sin necesidad de crear una cuenta**, adjuntando foto del daño y coordenadas GPS, y que reciba un código de ticket para consultar su estado.');
$blocks[] = numbered(2, 'Centralizar la operación en un panel de alcaldía que asigna cada incidencia a la cuadrilla o contratista correspondiente y vigila el cumplimiento de los SLA por categoría.');
$blocks[] = numbered(3, 'Dar al contratista una agenda digital para iniciar y finalizar obras, registrando descripción del trabajo y foto de la solución.');
$blocks[] = numbered(4, 'Someter toda obra terminada a una **inspección de calidad independiente** que pueda aprobarla y cerrar la incidencia o rechazarla y devolverla a reproceso con justificación.');
$blocks[] = numbered(5, 'Notificar automáticamente a los ciudadanos suscritos y a los actores involucrados en cada cambio de estado.');
$blocks[] = numbered(6, 'Exponer una API REST autenticada que permita a futuros clientes móviles operar el mismo flujo sin duplicar reglas de negocio.');

// ---------------------------- 4. ALCANCE -----------------------------------
$blocks[] = paragraph('4. Alcance funcional por rol', 'Heading1');
$blocks[] = table(
    ['Rol', 'Capacidades principales'],
    [
        ['Ciudadano', 'Radicar reportes de forma pública y anónima, adjuntar evidencia, consultar trazabilidad por ticket, suscribirse a novedades y ver incidencias cercanas en el mapa.'],
        ['Contratista', 'Consultar su agenda de órdenes asignadas, iniciar la ejecución, finalizar la obra con descripción y foto de la solución.'],
        ['Inspector de calidad', 'Ver la cola de obras pendientes de dictamen, comparar foto del daño vs. foto de la solución, aplicar checklist técnico, capturar GPS y aprobar o rechazar la obra.'],
        ['Alcaldía (superadmin)', 'Dashboard operativo, analítica, despacho y reasignación de incidencias, gestión de contratistas y control de SLA.'],
    ],
    [2300, 7060]
);
$blocks[] = paragraph('4.1 Ciclo de vida de una incidencia', 'Heading2');
$blocks[] = table(
    ['Estado del reporte', 'Significado', 'Quién actúa después'],
    [
        ['reported', 'Incidencia radicada, pendiente de despacho.', 'Alcaldía'],
        ['in_progress', 'Orden de trabajo creada; el contratista ejecuta la obra.', 'Contratista'],
        ['under_inspection', 'Obra terminada con evidencia; espera auditoría.', 'Inspector de calidad'],
        ['closed', 'Obra aprobada; incidencia resuelta y cerrada.', '—'],
        ['rework', 'Obra rechazada; requiere correcciones del contratista.', 'Contratista / Alcaldía'],
    ],
    [2500, 4660, 2200]
);

// ---------------------------- 5. ARQUITECTURA ------------------------------
$blocks[] = paragraph('5. Arquitectura y stack tecnológico', 'Heading1');
$blocks[] = paragraph(
    'El proyecto sigue una arquitectura **monolítica modular** típica de Laravel, con separación entre controladores (HTTP), Form Requests (validación y autorización), servicios de dominio (reglas de negocio) y recursos (serialización). La misma lógica de dominio es consumida por la interfaz web (Blade SSR) y por la API REST, evitando duplicar reglas.'
);
$blocks[] = table(
    ['Capa', 'Tecnología'],
    [
        ['Backend', 'Laravel 12 sobre PHP 8.2+'],
        ['Base de datos', 'SQLite (por defecto); compatible con MySQL/PostgreSQL'],
        ['Autenticación', 'Laravel Sanctum (cookie de sesión para web y tokens Bearer para API)'],
        ['Frontend', 'Blade SSR + JavaScript + Tailwind CSS 4'],
        ['Build', 'Vite 7'],
        ['Pruebas', 'PHPUnit 11.5 (24 pruebas, 95 aserciones)'],
        ['Almacenamiento', 'Disco local/S3 para fotos de daños, soluciones y verificaciones'],
    ],
    [2500, 6860]
);
$blocks[] = paragraph('5.1 Servicios de dominio', 'Heading2');
$blocks[] = bullet('**ReportIntake**: radica el reporte, genera el ticket, sube la foto y valida coordenadas.');
$blocks[] = bullet('**TicketCodeGenerator**: produce códigos secuenciales REP-AAAA-NNNN con seguridad ante concurrencia.');
$blocks[] = bullet('**ImageUploader**: almacena imágenes con nombres únicos y permite compensar (borrar) si falla la transacción.');
$blocks[] = bullet('**WorkOrderDispatcher**: asigna o reasigna órdenes de trabajo a contratistas.');
$blocks[] = bullet('**WorkOrderExecution**: controla las transiciones assigned → in_execution → completed.');
$blocks[] = bullet('**QualityInspectionRecorder**: registra el dictamen y actualiza atómicamente el estado del reporte y de la orden.');

// ---------------------------- 6. FUNCIONALIDAD 100% ------------------------
$blocks[] = pageBreak();
$blocks[] = paragraph('6. Funcionalidad terminada al 100%: inspección de calidad de obra', 'Heading1');
$blocks[] = paragraph(
    'De todos los módulos del sistema, la **inspección de calidad de obra** es la funcionalidad que se encuentra completa de extremo a extremo: tiene interfaz web, endpoints de API, validaciones, reglas de negocio, transiciones de estado atómicas, notificaciones automáticas y pruebas automatizadas que cubren sus dos resultados posibles (aprobación y reproceso), además de sus restricciones de seguridad.'
);

$blocks[] = paragraph('6.1 ¿En qué consiste?', 'Heading2');
$blocks[] = paragraph(
    'Cuando un contratista termina una obra, la incidencia pasa al estado **under_inspection**. En ese momento el inspector de calidad recibe la obra en su cola de trabajo y debe emitir un **dictamen técnico** en el sitio. Para hacerlo, el sistema le muestra en paralelo la foto del daño original reportado por el ciudadano y la foto de la solución entregada por el contratista, junto con el acta escrita del trabajo realizado.'
);
$blocks[] = paragraph('El inspector solo tiene dos decisiones posibles:');
$blocks[] = bullet('**Aprobar (approved)**: la obra cumple; la incidencia pasa a **closed** y el ciudadano recibe la notificación de cierre.');
$blocks[] = bullet('**Reproceso (rework)**: la obra no cumple; la incidencia pasa a **rework**, la orden de trabajo vuelve a **in_execution** y el contratista debe corregir. La decisión exige justificación escrita de mínimo 20 caracteres.');
$blocks[] = callout(
    'Por qué se considera completa',
    'No es solo un formulario: la funcionalidad incluye la cola de trabajo, la comparación de evidencias, el checklist obligatorio, la geolocalización anti-inspección-remota, la foto de verificación, la transición atómica de estados, las notificaciones y pruebas que demuestran cada camino.',
    'ECFDF5',
    '059669'
);

$blocks[] = paragraph('6.2 Actores y control de acceso', 'Heading2');
$blocks[] = table(
    ['Control', 'Implementación'],
    [
        ['Rol requerido', 'Middleware role:quality_inspector en las rutas web y en la API.'],
        ['Cuenta activa', 'StoreQualityInspectionRequest::authorize() exige is_active = true y rol QualityInspector.'],
        ['Doble capa', 'La ruta valida el rol y el Form Request vuelve a validarlo de forma independiente.'],
        ['Propiedad / IDOR', 'El inspector solo puede dictaminar órdenes en estado completed cuyo reporte esté under_inspection.'],
        ['Acceso web', 'Toda la consola /inspector está dentro del grupo auth + role:quality_inspector.'],
    ],
    [2600, 6760]
);

$blocks[] = paragraph('6.3 Flujo paso a paso', 'Heading2');
$blocks[] = numbered(1, 'El contratista finaliza la obra. El sistema guarda la descripción y la foto de la solución, marca la orden como **completed** y el reporte como **under_inspection**.');
$blocks[] = numbered(2, 'El inspector inicia sesión y entra a **/inspector/inspecciones**. Ve la cola FIFO: primero las obras que llevan más tiempo esperando dictamen.');
$blocks[] = numbered(3, 'Abre la auditoría de una orden (**/inspector/auditoria/{workOrder}**). El sistema vuelve a verificar que la orden esté completed y el reporte under_inspection; si no, redirige con un mensaje claro.');
$blocks[] = numbered(4, 'Compara la evidencia: foto del daño reportado, foto de la solución entregada, acta del contratista y datos de la orden.');
$blocks[] = numbered(5, 'Responde el **checklist técnico** de cinco puntos con botones táctiles (Cumple / No cumple). Los cinco deben quedar respondidos.');
$blocks[] = numbered(6, 'Si marca al menos un “No cumple”, se habilita el panel de reproceso y se exige la justificación (mínimo 20 caracteres).');
$blocks[] = numbered(7, 'Adjunta la **foto de verificación** obligatoria (JPEG, PNG o WebP, máximo 5 MB y 4000×4000 px).');
$blocks[] = numbered(8, 'Captura su **posición GPS** con el botón “Capturar GPS”. El dictamen no se puede firmar sin latitud y longitud.');
$blocks[] = numbered(9, 'Pulsa **APROBAR** o **REPROCESO** y confirma la acción. El sistema registra la inspección y aplica la transición de estados en una sola transacción.');
$blocks[] = numbered(10, 'El sistema redirige a la trazabilidad pública del ticket con el mensaje del dictamen y dispara las notificaciones correspondientes.');

$blocks[] = paragraph('6.4 Reglas de negocio implementadas', 'Heading2');
$blocks[] = bullet('Solo se inspeccionan órdenes en estado **completed**; una orden sin terminar responde 422.');
$blocks[] = bullet('Solo se inspeccionan reportes en estado **under_inspection**; cualquier otro estado se rechaza con 422.');
$blocks[] = bullet('La aprobación cierra el reporte (**closed**) y deja la orden como completed.');
$blocks[] = bullet('El reproceso marca el reporte como **rework** y devuelve la orden a **in_execution**, reabriendo el ciclo para que el contratista vuelva a completar la obra.');
$blocks[] = bullet('Un dictamen aprobado **no admite** motivo de rechazo: el servicio lo normaliza a null aunque el cliente lo envíe.');
$blocks[] = bullet('El checklist debe tener entre 1 y 50 ítems y cada respuesta debe ser un escalar simple (sin arrays u objetos anidados).');
$blocks[] = bullet('Cada inspección se guarda con un sufijo temporal en el nombre de la foto, de modo que varios reprocesos no sobrescriben la evidencia de visitas anteriores.');
$blocks[] = bullet('Si falla la escritura en base de datos, la foto ya subida se elimina (compensación) para no dejar archivos huérfanos.');

$blocks[] = paragraph('6.5 Validaciones del formulario y de la API', 'Heading2');
$blocks[] = table(
    ['Campo', 'Regla'],
    [
        ['decision', 'Obligatorio. Solo approved o rework.'],
        ['checklist_results', 'Obligatorio, arreglo de 1 a 50 ítems; cada respuesta debe ser escalar (bool, número o texto ≤ 255).'],
        ['rejection_reason', 'Obligatorio si decision = rework; texto de 20 a 1000 caracteres.'],
        ['inspector_latitude', 'Obligatorio, numérico, entre -90 y 90.'],
        ['inspector_longitude', 'Obligatorio, numérico, entre -180 y 180.'],
        ['verification_photo', 'Obligatorio, imagen JPEG/PNG/WebP, máximo 5 MB y 4000×4000 px.'],
    ],
    [2800, 6560]
);
$blocks[] = paragraph('Los mensajes de error están redactados en español y explican al inspector exactamente qué corregir. La validación existe en dos capas: JavaScript en el navegador y Form Request en el servidor.');

$blocks[] = paragraph('6.6 Interfaz web del inspector', 'Heading2');
$blocks[] = bullet('**Cola de inspecciones** (/inspector/inspecciones): listado paginado de reportes under_inspection, ordenado por antigüedad, con categoría, contratista y acceso directo a la auditoría.');
$blocks[] = bullet('**Pantalla de auditoría** (/inspector/auditoria/{workOrder}): encabezado con ticket, título, categoría, contratista y fecha de terminación.');
$blocks[] = bullet('**Comparativa visual**: dos tarjetas lado a lado con la foto del daño reportado y la foto de la solución entregada.');
$blocks[] = bullet('**Checklist táctil** de cinco ítems, con botones grandes pensados para uso en campo (incluso con guantes).');
$blocks[] = bullet('**Panel de reproceso condicional**: aparece solo cuando hay un “No cumple” y exige justificación.');
$blocks[] = bullet('**Foto de verificación** con vista previa inmediata y captura desde la cámara del dispositivo (capture="environment").');
$blocks[] = bullet('**Geolocalización**: botón que obtiene latitud y longitud del navegador con alta precisión y muestra el margen de error en metros.');
$blocks[] = bullet('**Confirmación del dictamen** antes de enviar, con validación previa de checklist, justificación, foto y GPS.');

$blocks[] = paragraph('6.7 API REST de la funcionalidad', 'Heading2');
$blocks[] = table(
    ['Método y ruta', 'Descripción'],
    [
        ['GET /api/v1/inspections/pending', 'Cola paginada de reportes pendientes de inspección (FIFO por updated_at).'],
        ['POST /api/v1/work-orders/{workOrder}/inspections', 'Registra el dictamen con checklist, decisión, GPS y foto. Responde 201.'],
    ],
    [4300, 5060]
);
$blocks[] = paragraph('Ambos endpoints están protegidos con auth:sanctum + role:quality_inspector. La respuesta del POST devuelve la inspección creada, el estado final de la orden y el estado final del reporte, lo que permite al cliente confirmar la transición sin una segunda consulta.');
$blocks[] = paragraph('Ejemplo de solicitud:', 'Heading3');
$blocks[] = codeBlock(
    "curl -X POST http://localhost:8000/api/v1/work-orders/7/inspections \\\n"
    . "  -H \"Authorization: Bearer INSPECTOR_TOKEN\" \\\n"
    . "  -H \"Accept: application/json\" \\\n"
    . "  -F \"decision=approved\" \\\n"
    . "  -F 'checklist_results={\"superficie_uniforme\":true,\"pendiente_drenaje\":true}' \\\n"
    . "  -F \"inspector_latitude=4.570868\" \\\n"
    . "  -F \"inspector_longitude=-74.297353\" \\\n"
    . "  -F \"verification_photo=@/ruta/verificacion.jpg\""
);
$blocks[] = paragraph('Respuesta esperada (201 Created):', 'Heading3');
$blocks[] = codeBlock(
    "{\n"
    . "  \"message\": \"Obra aprobada: la incidencia REP-2026-0007 ha sido cerrada.\",\n"
    . "  \"data\": {\n"
    . "    \"inspection\": { \"id\": 5, \"decision\": \"approved\", ... },\n"
    . "    \"work_order\": { \"id\": 7, \"status\": \"completed\" },\n"
    . "    \"report\": { \"id\": 7, \"ticket_code\": \"REP-2026-0007\", \"status\": \"closed\" }\n"
    . "  }\n"
    . "}"
);

$blocks[] = paragraph('6.8 Atomicidad y consistencia de datos', 'Heading2');
$blocks[] = paragraph(
    'El registro del dictamen y la transición de estados se ejecutan dentro de **DB::transaction** en QualityInspectionRecorder. Esto garantiza que nunca exista una inspección guardada sin el cambio de estado correspondiente, ni un reporte cerrado sin su acta de inspección. Si ocurre un error durante la transacción, la foto ya almacenada se elimina mediante el ImageUploader para no dejar evidencia huérfana.'
);
$blocks[] = paragraph(
    'La transición vive en un único servicio de dominio, compartido por la consola web y por la API. Así, la regla “aprobado cierra / reproceso reabre” no está duplicada en controladores y no puede desincronizarse entre canales.'
);

$blocks[] = paragraph('6.9 Notificaciones automáticas', 'Heading2');
$blocks[] = paragraph('El ReportObserver detecta el cambio de estado y despacha correos a través de notificaciones encoladas (ShouldQueue, after_commit):');
$blocks[] = bullet('**Aprobación**: los suscriptores y el correo de contacto del ciudadano reciben el aviso de cierre con la observación de que la inspección aprobó la obra.');
$blocks[] = bullet('**Reproceso**: los suscriptores reciben el aviso de retrabajo **incluyendo el motivo del rechazo** escrito por el inspector.');
$blocks[] = bullet('Si el reporte es anónimo pero dejó citizen_email, ese correo también recibe la notificación mediante un AnonymousNotifiable.');

$blocks[] = paragraph('6.10 Evidencia de que está al 100%', 'Heading2');
$blocks[] = paragraph(
    'La suite de pruebas del proyecto se ejecutó con **php artisan test** y finalizó con **24 pruebas aprobadas y 95 aserciones**. Las pruebas relacionadas con esta funcionalidad son:'
);
$blocks[] = table(
    ['Prueba automatizada', 'Qué demuestra'],
    [
        ['inspeccion_aprobada_cierra_la_incidencia', 'El dictamen approved deja el reporte en closed y la orden en completed.'],
        ['inspeccion_reprobada_devuelve_la_orden_a_ejecucion', 'El dictamen rework deja el reporte en rework y la orden en in_execution; además habilita la reasignación.'],
        ['no_se_inspecciona_una_orden_sin_terminar', 'Una orden no completada no puede inspeccionarse (422).'],
        ['inspeccion_aprobada (notificaciones)', 'Los suscriptores reciben el aviso de cierre.'],
        ['inspeccion_rechazada (notificaciones)', 'Los suscriptores reciben el aviso de reproceso con el motivo.'],
        ['web: inspector pages are gated by role', 'La consola del inspector exige autenticación y rol correcto.'],
    ],
    [4300, 5060]
);
$blocks[] = paragraph(
    'Adicionalmente, la funcionalidad cubre los dos caminos del negocio (aprobación y reproceso), las restricciones de rol y estado, la validación de payloads, la conservación de evidencia entre reprocesos y la compensación de archivos ante fallos. No quedan tareas pendientes dentro de su alcance.'
);

$blocks[] = paragraph('6.11 Cómo probarla manualmente', 'Heading2');
$blocks[] = numbered(1, 'Ejecutar php artisan migrate:fresh --seed para cargar los datos de demostración.');
$blocks[] = numbered(2, 'Iniciar el servidor con php artisan serve y, en otra terminal, npm run dev.');
$blocks[] = numbered(3, 'Entrar a http://localhost:8000 con el usuario **inspector1@calidad.gov.co** y contraseña **password**.');
$blocks[] = numbered(4, 'Abrir “Inspecciones” en el menú y elegir una obra pendiente.');
$blocks[] = numbered(5, 'Responder el checklist, adjuntar una foto, capturar el GPS y emitir el dictamen.');
$blocks[] = numbered(6, 'Verificar en la trazabilidad del ticket que el estado cambió a cerrado (aprobación) o a retrabajo (reproceso).');
$blocks[] = callout(
    'Credenciales de prueba',
    'Inspector 1: inspector1@calidad.gov.co · Inspector 2: inspector2@calidad.gov.co · Contraseña: password. El seeder incluye obras en estado under_inspection listas para dictaminar.',
    'FEF3C7',
    'F59E0B'
);

// ---------------------------- 7. CONCLUSIÓN --------------------------------
$blocks[] = paragraph('7. Conclusión', 'Heading1');
$blocks[] = paragraph(
    'Vigía Ciudadano resuelve un problema real de gestión pública con un flujo claro y verificable. Su valor no está solo en registrar reportes, sino en **cerrar el ciclo con evidencia y control de calidad**: el ciudadano reporta, la alcaldía despacha, el contratista ejecuta y un inspector independiente decide si la obra cumple.'
);
$blocks[] = paragraph(
    'La inspección de calidad es la pieza que garantiza ese cierre. Al estar implementada con interfaz web, API, validaciones en dos capas, transiciones atómicas, notificaciones y pruebas automatizadas que cubren aprobación, reproceso y restricciones de acceso, puede considerarse una funcionalidad **terminada al 100%** y lista para demostración o despliegue.'
);
$blocks[] = paragraph('');
$blocks[] = paragraph('— Fin del documento —', 'CoverNote');

// ---------------------------------------------------------------------------
// Construcción del paquete OOXML
// ---------------------------------------------------------------------------

$documentBody = implode('', $blocks);

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<w:body>' . $documentBody
    . '<w:sectPr>'
    . '<w:pgSz w:w="11906" w:h="16838"/>'
    . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>'
    . '</w:sectPr>'
    . '</w:body></w:document>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:docDefaults><w:rPrDefault><w:rPr>'
    . '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:eastAsia="Calibri" w:cs="Calibri"/>'
    . '<w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="es-CO"/>'
    . '</w:rPr></w:rPrDefault>'
    . '<w:pPrDefault><w:pPr><w:spacing w:after="140" w:line="288" w:lineRule="auto"/></w:pPr></w:pPrDefault>'
    . '</w:docDefaults>'

    . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/>'
    . '<w:qFormat/><w:pPr><w:spacing w:after="140" w:line="288" w:lineRule="auto"/></w:pPr>'
    . '<w:rPr><w:sz w:val="22"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="CoverKicker"><w:name w:val="Cover Kicker"/>'
    . '<w:pPr><w:spacing w:before="1200" w:after="120"/></w:pPr>'
    . '<w:rPr><w:b/><w:color w:val="087B5F"/><w:sz w:val="24"/><w:spacing w:val="80"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="CoverTitle"><w:name w:val="Cover Title"/>'
    . '<w:pPr><w:spacing w:after="120"/></w:pPr>'
    . '<w:rPr><w:b/><w:color w:val="1E40AF"/><w:sz w:val="56"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="CoverSubtitle"><w:name w:val="Cover Subtitle"/>'
    . '<w:pPr><w:spacing w:after="240"/><w:pBdr><w:bottom w:val="single" w:sz="18" w:space="8" w:color="1E40AF"/></w:pBdr></w:pPr>'
    . '<w:rPr><w:color w:val="334155"/><w:sz w:val="26"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="CoverNote"><w:name w:val="Cover Note"/>'
    . '<w:pPr><w:spacing w:before="120" w:after="120"/></w:pPr>'
    . '<w:rPr><w:i/><w:color w:val="64748B"/><w:sz w:val="20"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/>'
    . '<w:next w:val="Normal"/><w:qFormat/>'
    . '<w:pPr><w:keepNext/><w:spacing w:before="360" w:after="180"/>'
    . '<w:pBdr><w:bottom w:val="single" w:sz="18" w:space="6" w:color="1E40AF"/></w:pBdr>'
    . '<w:outlineLvl w:val="0"/></w:pPr>'
    . '<w:rPr><w:b/><w:color w:val="1E40AF"/><w:sz w:val="34"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/>'
    . '<w:next w:val="Normal"/><w:qFormat/>'
    . '<w:pPr><w:keepNext/><w:spacing w:before="300" w:after="120"/><w:outlineLvl w:val="1"/></w:pPr>'
    . '<w:rPr><w:b/><w:color w:val="2563EB"/><w:sz w:val="26"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/><w:basedOn w:val="Normal"/>'
    . '<w:next w:val="Normal"/><w:qFormat/>'
    . '<w:pPr><w:keepNext/><w:spacing w:before="220" w:after="100"/><w:outlineLvl w:val="2"/></w:pPr>'
    . '<w:rPr><w:b/><w:color w:val="3B82F6"/><w:sz w:val="23"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="TableText"><w:name w:val="Table Text"/>'
    . '<w:pPr><w:spacing w:before="40" w:after="40" w:line="240" w:lineRule="auto"/></w:pPr>'
    . '<w:rPr><w:sz w:val="19"/></w:rPr></w:style>'

    . '<w:style w:type="paragraph" w:styleId="Code"><w:name w:val="Code Block"/>'
    . '<w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>'
    . '<w:ind w:left="120" w:right="120"/></w:pPr>'
    . '<w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas" w:cs="Consolas"/><w:sz w:val="18"/><w:color w:val="0F172A"/></w:rPr></w:style>'

    . '<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/>'
    . '<w:tblPr><w:tblBorders>'
    . '<w:top w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
    . '<w:left w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
    . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
    . '<w:right w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
    . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
    . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>'
    . '</w:tblBorders></w:tblPr></w:style>'

    . '</w:styles>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
    . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
    . '</Types>';

$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
    . '</Relationships>';

$documentRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
    . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
    . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
    . '<dc:title>Vigía Ciudadano — Documentación del proyecto</dc:title>'
    . '<dc:subject>Descripción del proyecto y funcionalidad de inspección de calidad</dc:subject>'
    . '<dc:creator>Vigía Ciudadano</dc:creator>'
    . '<cp:keywords>Laravel; incidencias urbanas; inspección de calidad</cp:keywords>'
    . '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
    . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:modified>'
    . '</cp:coreProperties>';

$appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
    . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
    . '<Application>Vigía Ciudadano</Application>'
    . '<Company>Vigía Ciudadano</Company>'
    . '</Properties>';

// ---------------------------------------------------------------------------
// Escritura del .docx
// ---------------------------------------------------------------------------

if (file_exists($outputFile)) {
    unlink($outputFile);
}

$zip = new ZipArchive();
if ($zip->open($outputFile, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "No se pudo crear el archivo: {$outputFile}\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rootRels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/styles.xml', $stylesXml);
$zip->addFromString('word/_rels/document.xml.rels', $documentRels);
$zip->addFromString('docProps/core.xml', $coreXml);
$zip->addFromString('docProps/app.xml', $appXml);
$zip->close();

echo "Archivo Word creado: {$outputFile}\n";
echo 'Tamaño: ' . number_format((int) filesize($outputFile)) . " bytes\n";
