# Experiencia ciudadana accesible

## Alcance

Cambios de presentación en inicio, formulario público, consulta por radicado, detalle y comprobante. Los paneles administrativos, de contratistas e inspectores conservan sus vistas, tamaños y controles. Los nuevos estilos están limitados a `.citizen-ui`; la preferencia de texto no se aplica a las áreas institucionales.

No se modificaron controladores, rutas, peticiones de validación, permisos, modelos ni servicios. La creación sigue siendo un POST multipart a la ruta existente, con CSRF, categoría real, foto obligatoria, título, descripción y coordenadas. El comprobante solo presenta un reporte que el servidor ya guardó.

## Decisiones de interfaz

- Inicio con dos acciones: Reportar un problema y Consultar mi reporte. La consulta está en la misma página, sin modal. Mapa, resultados y explicación del proceso siguen disponibles en una sección desplegable.
- Tres pasos, sin autoavance al elegir categoría, con botones Anterior/Continuar y foco en el título de cada paso. Solo se ocultan secciones cuando JavaScript funciona; sin él se muestra el formulario completo.
- Las categorías más habituales aparecen primero; las demás permanecen en «Ver otros tipos de problema». Los nombres cotidianos son etiquetas de presentación: se envía el mismo ID del catálogo. No se inventó «Otro» si no existe en el sistema.
- Texto principal de 18 px (20 px con el control de lectura), secundarios de al menos 16 px en los componentes ciudadanos, campos y acciones principales de unos 56 px. El zoom del navegador sigue funcionando.
- Errores al lado del campo, referencia accesible y foco en el dato que necesita corrección. Al volver del servidor se conserva el texto; la foto debe volver a seleccionarse por seguridad del navegador.
- Foto con vista previa y cambio, tipo/tamaño/dimensiones comprobados también en el cliente. En pantallas táctiles hay una acción de cámara; el selector de archivos sigue disponible para fotos guardadas. La prueba de Chrome emula esa selección, no una cámara física. La validación del servidor sigue siendo la autoridad y comprueba el contenido real del archivo. No se comprimen ni se alteran las evidencias automáticamente.
- Confirmación con número real seleccionable, copia con manejo de fallo y comprobante imprimible sin menús.
- Los estados públicos son etiquetas: `reported → Recibido`, `in_progress/rework → En reparación`, `under_inspection → En revisión`, `closed → Solucionado`. Los valores internos no cambian.
- El detalle público no presenta nombres ni datos de contacto de ciudadanos, contratistas o inspectores, ni descripciones internas del trabajo o motivos internos de rechazo. La vista institucional original permanece disponible para los roles autorizados. Las fotos y la descripción ciudadana siguen siendo públicas: se advierte que no incluyan rostros ni datos personales; no hay anonimización automática de imágenes.

## Ubicación, red y privacidad

El servidor exige coordenadas: escribir una dirección de referencia **no basta** ni crea coordenadas ficticias.

1. Usar mi ubicación es voluntario; solo pide el permiso al pulsar el botón.
2. Buscar dirección consulta Nominatim/OpenStreetMap únicamente al pulsar Buscar dirección. Se informa del envío de la dirección al proveedor, sin mandar datos de contacto, cookies de sesión ni CSRF. La persona debe elegir el resultado; no se acepta automáticamente el primero.
3. El mapa permite clic/toque y un botón para elegir el centro después de moverlo con las flechas. Arrastrar no es obligatorio.
4. Las coordenadas manuales permanecen disponibles, incluso sin JavaScript.

Nominatim y las teselas requieren conexión y disponibilidad de un tercero. Si fallan, la pantalla ofrece los otros métodos; sin GPS, mapa ni geocodificador, hacen falta coordenadas conocidas para continuar. No se cambió esta exigencia del backend.

La consulta utiliza el HTML público existente, no la API de detalle que exige autenticación. Un número inexistente mantiene HTTP 404 y muestra una explicación amable. Las API de reportes cercanos y suscripción continúan requiriendo sesión; no se llama a ellas como invitado ni se promete una suscripción anónima que el backend no permite. Las actualizaciones por correo opcional al crear el reporte permanecen disponibles.

## Pruebas reproducibles

```sh
npm run build
php artisan test
git diff --check
```

`tests/Feature/CitizenExperienceTest.php` comprueba alta pública real en SQLite en memoria, foto, ausencia de datos personales obligatorios, errores sin éxito falso, estado de dominio intacto, privacidad del HTML público, comprobante con radicado guardado y conservación de controles institucionales.

### Chrome / Playwright (herramientas opcionales fuera del proyecto)

Con la aplicación local iniciada y un reporte de prueba ya guardado:

```powershell
npm install --prefix C:\ruta\temporal\herramientas playwright axe-core
$env:VIGIA_BROWSER_TOOLING='C:\ruta\temporal\herramientas\package.json'
$env:VIGIA_BROWSER_PATH='C:\Program Files\Google\Chrome\Application\chrome.exe'
$env:VIGIA_BASE_URL='http://127.0.0.1:8015'
$env:VIGIA_ARTIFACTS='C:\ruta\temporal\capturas-ciudadano'
node tests/Browser/citizen-experience.mjs
```

Opcional: `VIGIA_TICKET` indica un radicado real específico; si se omite se toma uno del mapa público. Las capturas y resultados se guardan fuera del código. No se añaden dependencias al paquete de producción.

La suite recorre inicio, los tres pasos, detalle, comprobante y 404; prueba teclado, GPS rechazado, dirección con respuesta controlada, cambio de foto, retroceso, consulta real y fallida, copia, impresión y alternativa sin JavaScript. **No envía incidencias ficticias a la base local**: el POST del navegador se previene deliberadamente; la creación real se prueba por separado en la base de pruebas aislada.

El zoom al 200 % se comprueba por reflujo equivalente: mitad del ancho/alto CSS y escala de dispositivo 2, no accionando el menú de zoom de Chrome. El geocodificador se simula en la prueba funcional para no depender de un tercero ni enviar datos personales durante la automatización.

## Evaluación con personas

Ver [protocolo-usabilidad-ciudadana.md](protocolo-usabilidad-ciudadana.md). El protocolo está preparado, pero no se realizaron entrevistas ni sesiones con adultos mayores. Las pruebas automáticas y las capturas no sustituyen esa evaluación ni una auditoría manual completa de WCAG 2.2 AA.
