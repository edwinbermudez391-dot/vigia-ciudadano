# Auditoría UX/UI — segunda revisión

## Alcance y conservación del sistema

Se revisaron todas las vistas existentes, sus componentes y las rutas asociadas.
Se conservaron Laravel, Blade, Tailwind y JavaScript, sin añadir dependencias de
ejecución ni modificar rutas, roles, permisos, migraciones o servicios de negocio.
Las diferencias entre acceso público web y autenticación de la API se mantienen.

No existen pantallas independientes de configuración, edición genérica o bandeja
de notificaciones. Las notificaciones actuales son correos; se revisaron sus dos
plantillas. No se añadieron enlaces a módulos inexistentes.

## Problemas encontrados y correcciones

| Hallazgo de la primera versión | Corrección implementada |
| --- | --- |
| Navegación horizontal y sin opción de contracción | Menú lateral institucional de 280 px, contraíble a 88 px, con iconos SVG, nombres accesibles y preferencia local |
| Controles del menú situados al final de páginas largas | Barra lateral con posición sticky y desplazamiento propio; menú móvil en el flujo, sin superposición |
| Selectores CSS por fragmentos aplicaban estilos de hover incluso en reposo | Alias de colores por clase exacta; las categorías permanecen blancas y la selección se diferencia explícitamente |
| Campos, etiquetas y filas inconsistentes | Texto principal de 18 px, etiquetas de 17 px, campos de al menos 54 px y botones de al menos 48 px |
| Dashboard sin accesos operativos destacados | Cuatro indicadores amplios, accesos directos, filtros y acciones textuales en reportes |
| Listados de cuadrillas, órdenes e inspecciones poco estructurados | Tarjetas operativas, contacto agrupado, búsqueda local con alcance explícito y estados vacíos explicativos |
| Paginación en inglés con un atributo ARIA inválido | Componente de paginación en español con Anterior, Siguiente, página y total de resultados |
| Checklist comprimido y botones con texto cortado | Opciones de respuesta más amplias, grupos con nombre y estados aria-pressed |
| Contrastes insuficientes en textos de alerta, indicadores y botón de reproceso | Tonos más oscuros y eliminación de transparencia en instrucciones importantes |
| Marcadores Leaflet sin nombre accesible | Título y nombre del reporte en los puntos del mapa; instrucciones de teclado |
| Fotografías sin jerarquía o estado de ausencia consistente | Componente reutilizable de evidencia con encabezado, descripción y fallback |
| Formulario de contratistas repetía controles sin asociación consistente de errores | Componente de campo con etiqueta, ayuda, required, aria-invalid y aria-describedby |
| Cálculo de plazo en la vista podía alterar la fecha del objeto mostrado | Cálculo sobre una copia de la fecha; prueba que conserva la fecha original |

## Inventario de pantallas revisadas

- `resources/views/welcome.blade.php`: portada, instrucciones de reporte,
  consulta por radicado, mapa, transparencia, pestañas, métricas y enlaces.
- `resources/views/auth/login.blade.php`: acceso, ayuda, errores y distribución
  del formulario; panel institucional separado en escritorio.
- `resources/views/admin/dashboard.blade.php`: indicadores, accesos directos,
  reportes, filtros, tabla, paginación y asignación.
- `resources/views/admin/contractors.blade.php`: listado, contacto, búsqueda y
  formulario de registro con agrupación de credenciales.
- `resources/views/admin/analytics.blade.php`: filtros separados del encabezado,
  indicadores, gráficas, alternativa tabular y tabla de rendimiento.
- `resources/views/reports/create.blade.php`: categoría, confirmación, foto,
  ubicación, descripción, contacto y advertencia de duplicados conservada.
- `resources/views/reports/show.blade.php`: datos, plazo, evidencias, historial,
  seguimiento y acciones de contratista/alcaldía según permisos.
- `resources/views/reports/success.blade.php`: confirmación, radicado, copia,
  resumen y siguientes pasos.
- `resources/views/contractor/orders.blade.php`: agenda, estado, fecha y acceso
  al formulario operativo existente.
- `resources/views/inspector/queue.blade.php`: cola, comparativa de evidencia,
  búsqueda, inspecciones anteriores y acceso a revisión.
- `resources/views/inspector/audit.blade.php`: comparativa, checklist, foto, GPS,
  justificación y confirmación del dictamen.
- `resources/views/map.blade.php`: mapa público, filtros, marcadores y detalle.
- `resources/views/emails/report-status.blade.php` y
  `resources/views/emails/work-order-assigned.blade.php`: legibilidad del correo.
- Layout, navegación, botones, indicadores, etiquetas, fotografías, errores,
  campos, búsquedas, asignación y paginación compartidos.

## Verificación ejecutada

### Pruebas de código

- `php artisan test --compact`: **37 pruebas, 184 comprobaciones aprobadas**.
- `npm run build`: compilación de Vite correcta.
- `php artisan view:cache`: plantillas Blade compiladas correctamente.
- `node --check resources/js/app.js`: sintaxis correcta.
- `git diff --check`: sin errores de espacios en los cambios.

Las pruebas cubren navegación por rol, permisos, acceso público web frente a API
autenticada, filtros y paginación, validaciones, asignación, inicio y terminación
de trabajos, inspección aprobada/rechazada, retrabajo, cierre y notificaciones.
Se añadieron comprobaciones específicas para menú lateral, asociación de errores,
paginación accesible en español y conservación de la fecha original.

### Navegador real

Chrome instalado en Windows, controlado con Playwright Core. Playwright y axe-core
se instalaron únicamente en una carpeta temporal, fuera de las dependencias del
proyecto. El servidor de prueba utilizó una base SQLite temporal con seeders;
no se reinició ni se sembró la base de datos del proyecto.

- 80 capturas anteriores y 80 posteriores: variantes públicas, administrativas,
  de contratista e inspector, en 1920 × 1080, 1366 × 768, 768 × 1024,
  390 × 844 y una ventana de reflow de 683 × 384.
- Sin desbordamiento horizontal del documento en las 80 comprobaciones
  posteriores. Las tablas extensas tienen desplazamiento local controlado.
- axe ejecutado en las variantes de portátil de las pantallas examinadas:
  **sin infracciones detectadas** para las etiquetas WCAG A/AA examinadas.
- 28 comprobaciones de interacción aprobadas: contracción/ampliación del menú,
  persistencia, navegación móvil, Escape, restauración y contención del foco,
  búsquedas, selección de categoría, carga de foto por teclado y marcadores.
- Asignación, registro, confirmación de categoría y consulta por radicado:
  revisión automática adicional de accesibilidad con los diálogos abiertos.
- Zoom **real del navegador al 200 %**, configurado desde la apariencia de
  Chrome, no mediante CSS: ventana de 1366 px, viewport de 674 px y DPR de 2.
  Se comprobaron dashboard, contratistas, estadísticas, creación y mapa, sin
  desbordamiento horizontal del documento. La navegación pasa a su modo compacto.
- Ocho capturas adicionales de los dos correos renderizados en los cuatro anchos,
  sin enviar correos ni detectar desbordamientos.

Los artefactos de esta ejecución están en
`C:\Users\edwin\AppData\Local\Temp\opencode\vigia-ui-audit\`:
`before`, `after` e `interactions`, con capturas y resultados JSON. Son temporales,
no forman parte de los archivos de producción.

## Accesibilidad: límites y pendientes

Los resultados automáticos y la inspección visual no constituyen una certificación
completa de WCAG 2.2 AA.

| Aspecto | Resultado o límite |
| --- | --- |
| Contraste de texto (1.4.3) | Se corrigieron los fallos detectados por axe; faltan combinaciones no ejercitadas de datos/estados |
| Reflow y ampliación (1.4.4, 1.4.10) | Comprobados anchos adaptables y zoom real al 200 %; no se verificó zoom al 400 % |
| Teclado y ausencia de trampas (2.1.1, 2.1.2) | Comprobaciones en menús, diálogos y selección/carga de archivos; no equivalen a un recorrido exhaustivo de todas las funciones |
| Foco y orden (2.4.3, 2.4.7, 2.4.11) | Foco visible, contención/restauración de diálogos y cierre con Escape verificados; pendiente prueba asistida completa |
| Nombre, rol y valor (4.1.2) | Controles y marcadores revisados automáticamente; pendiente NVDA/JAWS/VoiceOver real |
| Identificación e instrucciones (3.3.1, 3.3.2) | Etiquetas y errores vinculados; falta validar todos los mensajes con usuarios finales |
| Mensajes de estado (4.1.3) | Regiones de estado en búsquedas, confirmaciones y copia; pendiente escucha con lector de pantalla |
| Tamaño de objetivos (2.5.8) | Controles principales de al menos 48 px; no se certifican todos los controles de terceros |
| GPS, cámara y mapa | Carga del mapa y selección de archivo verificadas; no se probó precisión GPS ni cámara en dispositivos físicos |
| Correo | HTML revisado en Chrome; pendientes Outlook, Gmail y otros clientes, y comprobación de entrega SMTP |

Los seeders hacen referencia a fotografías que no están en el almacenamiento de
esta base temporal. Se verificó el fallback «Foto no disponible» de la aplicación;
no se inventaron ni reemplazaron evidencias de los usuarios.
