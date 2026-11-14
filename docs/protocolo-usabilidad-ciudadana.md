# Protocolo de usabilidad con personas mayores

**Estado:** preparado para ejecutar; todavía no se han realizado sesiones con ciudadanos. Las pruebas automatizadas no demuestran por sí solas que la plataforma sea fácil de usar.

## Participantes y condiciones

- Primera ronda: 6–8 personas, con diversidad de edad (50–59, 60–69, 70 o más), experiencia digital, visión y destreza. No atribuir dificultades únicamente a la edad.
- Sesiones individuales de unos 25–35 minutos; permitir descansos o detener la sesión sin justificarlo.
- Probar con su teléfono o computador habitual cuando sea posible. Conservar sus lentes, ampliación de pantalla y otras ayudas preferidas.
- Pedir consentimiento. Aclarar: «Estamos evaluando la aplicación, no tus capacidades». No guardar nombres, voz ni imágenes sin autorización específica.
- Usar un entorno de pruebas separado, categorías reales, imágenes sin personas ni datos privados y radicados que el servidor realmente haya creado allí. No enviar incidencias ficticias a producción.
- Una persona facilita; otra anota si es posible. No cambiar interfaz ni dar instrucciones durante una tarea salvo que la persona pida ayuda o se bloquee.

## Guion neutral

«Imagina que encontraste un problema en tu barrio. Usa esta página para comunicarlo. Puedes decir en voz alta lo que estás buscando, si te resulta cómodo. Puedes parar cuando quieras».

No señalar botones ni explicar por adelantado el proceso. Registrar cuándo pide ayuda. Graduar la ayuda: repetir el objetivo → sugerencia general → indicación concreta → demostración. Distinguir siempre éxito independiente de éxito con ayuda.

## Tareas

| Tarea | Consigna, sin indicar el botón | Observar |
| --- | --- | --- |
| Encontrar la acción | «Quieres avisar de un hueco en la calle. ¿Cómo empezarías?» | Si distingue reportar de consultar y de ingresar con cuenta. |
| Elegir categoría | «Selecciona el tipo de problema que corresponde». Repetir con una categoría dentro de «otros tipos». | Si entiende los nombres y encuentra alternativas sin perderse. |
| Ubicar el problema | «El problema está en esta dirección de prueba». En una variante, rechazar el permiso de ubicación. | Si puede continuar buscando la dirección o señalando el mapa; si entiende que debe elegir un resultado. |
| Adjuntar evidencia | «Cuenta qué pasó y añade esta fotografía. Después cámbiala por esta otra». | Si encuentra Agregar/Cambiar foto y comprende la vista previa. |
| Retroceder | «Quieres revisar la ubicación antes de enviar». | Si encuentra Anterior y comprueba que la foto y el texto siguen guardados. |
| Enviar | «Cuando estés conforme, envía el reporte». | Si entiende los errores y corrige el campo; si espera una confirmación del servidor. |
| Guardar comprobante | «Guarda el número para usarlo mañana». Ofrecer impresión como alternativa, sin imponerla. | Si distingue número real de ejemplo y entiende Copiar número. |
| Consultar | «Vuelve al inicio y averigua cómo va ese reporte». | Si encuentra la consulta, introduce el número y comprende el estado. |
| Recuperarse de un error | Dar un número de prueba que no exista o simular pérdida de conexión. | Si el mensaje explica qué ocurrió y qué hacer a continuación. |

Alternar las tareas entre móvil y escritorio en distintas sesiones, no cambiar de dispositivo inesperadamente a mitad de una tarea. Probar texto aumentado y zoom al 200 % con quienes los utilicen o deseen probarlos. Incluir al menos una sesión con navegación por teclado y, si se cuenta con participantes que lo usan, lector de pantalla.

## Registro por tarea

- Resultado: independiente / con ayuda general / con ayuda concreta / no completada.
- Tiempo aproximado, número de errores, retrocesos y lugares donde se detiene.
- Qué esperaba que ocurriera frente a lo que ocurrió. Anotar sus palabras, sin reinterpretarlas como un problema de la edad.
- Confianza de 1 a 5 y pregunta abierta: «¿Qué cambiarías para sentirte más seguro al hacer esto?».
- No pedir que revele datos personales. No fotografiar pantallas con información privada.

Plantilla: `Participante anónimo | dispositivo/ayudas | tarea | resultado | tiempo | errores | ayuda | comentario | posible mejora`.

## Criterios de decisión

- Meta inicial, no resultado obtenido: al menos 80 % de las tareas principales completadas sin indicaciones concretas.
- Ningún bloqueo sin alternativa al rechazar GPS. Ninguna confirmación falsa ni pérdida de la información al retroceder.
- Todos deben poder explicar con sus palabras qué significa el estado mostrado y cómo volver a consultar.
- Priorizar primero bloqueos, pérdida de confianza y errores repetidos; después ajustes estéticos.
- Documentar resultados por tarea y dispositivo, no solo un promedio. Con muestras pequeñas no hacer afirmaciones estadísticas sobre todos los adultos mayores.
- Corregir y repetir una segunda ronda con 4–6 participantes, incluyendo personas nuevas. No declarar «accesible» o «fácil para mayores» solo por aprobar pruebas de código.
