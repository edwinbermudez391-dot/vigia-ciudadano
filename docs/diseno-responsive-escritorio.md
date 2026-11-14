# Diseño responsive de escritorio

## Diagnóstico

- Tailwind 4 conserva `md: 48rem`, `lg: 64rem` y `xl: 80rem`, declarados explícitamente en `resources/css/app.css`.
- Antes de esta corrección, Chrome cargaba el CSS de `public/build/manifest.json` correctamente. No había un archivo `public/hot` apuntando a un servidor de Vite antiguo.
- La portada ya mostraba navegación horizontal y dos columnas a partir de 1024 px al 100 %. No se reprodujo el fallo del menú público en esas condiciones.
- Los paneles internos, en cambio, usaban 1200 px como límite para el menú móvil, tanto en CSS como en JavaScript. A 1024 px mantenían la distribución móvil.
- Las reglas globales `main > .max-w-*` sobrescribían los anchos de las utilidades. Las vistas de formularios y detalles carecían de una estructura propia para aprovechar el escritorio.
- `Reportar es sencillo` siempre apilaba sus pasos. Las listas de trabajo también conservaban una única columna.

## Estructura implementada

`resources/css/layout.css` concentra los contenedores y las distribuciones. Se retiraron las reglas anteriores que competían por estas mismas propiedades; no se redujo globalmente la tipografía.

| Ancho CSS efectivo | Navegación pública | Paneles internos |
| --- | --- | --- |
| Menos de 1024 px | Hamburguesa | Menú desplegable |
| 1024–1279 px | Horizontal | Horizontal |
| Desde 1280 px | Horizontal | Barra lateral plegable |

- Contenedores centrados de hasta 1280 px.
- Portada de dos columnas desde 1024 px; título de 38–44 px y tres tarjetas para los pasos.
- Formulario de reporte: categorías de tres/cuatro columnas, foto y ubicación en paralelo, detalles y contacto en paralelo. En móvil conserva el orden de una columna.
- Detalle del reporte: información y evidencia junto al historial y seguimiento.
- Auditoría: evidencia junto al formulario, con etiquetas y botones de verificación accesibles.
- Órdenes e inspecciones: dos tarjetas por fila en escritorio.
- Dashboard: cuatro indicadores y tres tarjetas de seguimiento desde 1024 px.
- Tablas, mapa, estadísticas y acceso conservan sus funciones y adaptación a móvil.

## Recursos y comprobación

```sh
npm run build
php artisan test
git diff --check
```

La compilación produce nombres de recursos con hash. Blade utiliza `@vite`, sin URLs de CSS fijadas manualmente. Si una pestaña conserva recursos antiguos, una recarga completa permite comprobar el nuevo hash en la pestaña Red de Chrome.

Las pruebas PHP utilizan SQLite en memoria y no modifican la base de datos de la aplicación.

### Zoom

El breakpoint depende del ancho CSS disponible, no del ancho físico del monitor. Por ejemplo, una ventana de 1366 px al 200 % dispone de aproximadamente 683 px CSS: en ese caso corresponde el menú móvil, para evitar desbordamientos y pérdida de contenido.

La comprobación automatizada al 200 % utiliza un viewport de la mitad del ancho/alto físico y escala de dispositivo 2. Esto comprueba el reflujo equivalente, no acciona el control de zoom de la interfaz de Chrome.

## Resultados de verificación

- `npm run build`: correcto.
- `php artisan test`: 39 pruebas y 232 comprobaciones aprobadas. PHP muestra un aviso de entorno por la carga duplicada de OpenSSL, sin fallos de pruebas.
- `git diff --check`: sin errores de formato.
- Chrome con Playwright: 12 pantallas × 6 resoluciones × 2 escalas = 144 comprobaciones, sin desbordamiento horizontal, errores JavaScript ni respuestas HTTP incorrectas.
- Anchos físicos comprobados: 390, 768, 1024, 1366, 1440 y 1920 px. Escalas: 100 % y reflujo equivalente al 200 %.
- Tras revisar las capturas se corrigieron el margen entre secciones del formulario y el espacio vacío de la auditoría. Se repitieron 24 comprobaciones de estas dos vistas, sin desbordamientos. Chrome cargó el último CSS del manifiesto.
- Verificadas las variantes `md:`, `lg:` y `xl:` con sus estilos calculados; navegación pública horizontal desde 1024 px; pasos en tres columnas; título de 38–44 px; botones principales de 44–48 px.
- Comprobados menús, Escape, foco del modal de consulta, pestañas por teclado, selección de categoría, plegado de la barra lateral, cambio de foco al redimensionar, asignación, registro y validación del formulario de inspección. No se enviaron asignaciones, registros ni dictámenes reales.
- Axe: sin infracciones automáticas WCAG A/AA detectadas en las diez pantallas revisadas. No sustituye una evaluación manual completa.
- Capturas revisadas de portada móvil/escritorio, portada con reflujo al 200 %, dashboard a 1024 px, creación de reporte, cola de inspecciones y auditoría de escritorio.

Las comprobaciones de inicio de sesión se ejecutan de forma secuencial para respetar el límite existente de diez intentos por minuto; no se cambió dicho límite.

## Archivos de esta corrección

- `resources/css/app.css`
- `resources/css/layout.css` (nuevo)
- `resources/js/app.js`
- `resources/views/welcome.blade.php`
- `resources/views/reports/create.blade.php`
- `resources/views/reports/show.blade.php`
- `resources/views/contractor/orders.blade.php`
- `resources/views/inspector/queue.blade.php`
- `resources/views/inspector/audit.blade.php`
- `tests/Feature/InstitutionalUiTest.php`
- `docs/diseno-responsive-escritorio.md` (nuevo)

Se conservaron los cambios previos del proyecto. No se modificaron controladores, rutas, autenticación, permisos ni lógica de negocio.
