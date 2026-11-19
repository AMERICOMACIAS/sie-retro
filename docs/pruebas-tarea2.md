# ESRETRO | Tarea 2: pruebas de integración y aceptación

**Proyecto:** ESRETRO (prototipo académico sin cobros reales)  
**Alcance:** WordPress/WooCommerce → HTTP JSON → n8n Cloud → Google Sheets; soporte y propuestas con IA, con revisión humana.  
**Fecha de la batería P1-P5:** 18 de noviembre de 2026.  
**Origen de la batería:** peticiones controladas desde **Hoppscotch**, no desde el checkout de WordPress.

> Este archivo complementa [`pruebas.md`](pruebas.md), que contiene las pruebas P01-P18 de la Tarea 1. No deben confundirse las pruebas P1-P5 de n8n con las comprobaciones funcionales de la tienda.

## Entornos y herramientas

- **Tienda:** https://www.esretro.onl/ (WordPress + WooCommerce).
- **n8n Cloud:** https://esretro-sie.app.n8n.cloud/.
- **Workflow de pedidos:** https://esretro-sie.app.n8n.cloud/workflow/ox9ujaSVzKULKYLJ.
- **Workflow de incidencias:** https://esretro-sie.app.n8n.cloud/workflow/Wa0KGKQNz24hLyDJ.
- **Back-office:** https://docs.google.com/spreadsheets/d/1bhPG1Q5LsSA0lHLj2yuNpP-LfUP6LDuNASpJ8b7y1Is/edit (hojas `Pedidos`, `Incidencias`, `Integraciones`, `Dashboard` y `Pruebas`).
- **Cliente HTTP de pruebas:** Hoppscotch.

Los enlaces de administración pueden requerir permisos. Nunca publicar los tokens, claves de API ni credenciales. El receptor utiliza autenticación por cabecera `X-ESRETRO-Token`.

## Matriz de aceptación

| ID | Caso | Resultado esperado | Evidencia observada | Estado |
| --- | --- | --- | --- | --- |
| P1 | Pedido nuevo | Crear una fila en `Pedidos` | Pedido con `order_id=991801`, `event_id=991801`, total `49.99` EUR | **Superada** |
| P2 | Pedido duplicado | No crear una segunda fila con el mismo evento | Se reenvió **el mismo** `event_id=991801`; se mantuvo una sola fila de pedido | **Superada** |
| P3 | Incidencia nueva | Crear una fila en `Incidencias` | Incidencia `INC-991803`, `support_event_id=991803` | **Superada** |
| P4 | Incidencia duplicada | No crear otra fila para el mismo identificador | Se reenvió el mismo `event_id=991803`; se mantuvo una sola incidencia | **Superada** |
| P5 | Error controlado | Rechazar el contrato inválido y guardar motivo en `Integraciones` | `ERR-38`; `event_id=991805`; `schema_version incorrecta` | **Superada** |

**Interpretación:** P5 se considera superada porque el **control de errores** funcionó: se rechazó el mensaje inválido y quedó registrada su causa. No significa que la petición fuera válida.

## Procedimiento y criterios de reproducción

### P1. Alta de un pedido

1. Enviar con Hoppscotch una petición `POST` al webhook de pedidos, con `Content-Type: application/json` y autenticación por cabecera.
2. Utilizar el JSON de [`order-created.json`](tarea2/payloads/order-created.json), basado en el caso probado.
3. Verificar en `Pedidos` la fila con `order_id=991801` y en `Integraciones` su registro de éxito.
4. Asociar, cuando se prepare la evidencia, la ejecución de n8n a la fila de la hoja (en la captura de referencia aparece la ejecución **34**).

### P2. Idempotencia de pedidos

1. Reenviar **exactamente** el mensaje de P1 (incluido `event_id=991801`).
2. Comparar el número de filas en `Pedidos` antes y después.
3. Comprobar que no se crea otra fila del mismo pedido por ese reenvío.

### P3. Alta de una incidencia

1. Enviar `POST` al webhook de incidencias con el JSON de [`support-requested.json`](tarea2/payloads/support-requested.json).
2. Verificar que existe `INC-991803` en `Incidencias` y su resultado en `Integraciones`.
3. Relacionar la fila con la ejecución correspondiente (en la captura de referencia aparece la ejecución **36**).

### P4. Idempotencia de incidencias

1. Reenviar **exactamente** el mensaje de P3 (incluido `event_id=991803`).
2. Comprobar que `INC-991803` sigue apareciendo una sola vez.

### P5. Rechazo de contrato erróneo

1. Enviar un `support.requested` con `event_id=991805` y `schema_version="9.9"` (versión inválida para el contrato v1.0).
2. Verificar en `Integraciones` el registro `ERR-38`, con estado `error`, ejecución **38** y mensaje `schema_version incorrecta`.
3. Comprobar que el mensaje inválido no se trata como una incidencia válida.

No volver a ejecutar estos identificadores en producción sin preparar un entorno de prueba y documentar el nuevo estado; **los datos anteriores son históricos**, no un permiso para repetirlos.

## Prueba complementaria de cambios de estado

El escenario obligatorio `order.status_changed` utiliza un payload con `order_id`, `previous_status` y `status`. **El ejemplo** [`order-status-changed.json`](tarea2/payloads/order-status-changed.json) **es ilustrativo y no constituye por sí mismo una prueba realizada**. Para comprobarlo de manera reproducible:

1. Disponer de un pedido ya registrado en la hoja `Pedidos`.
2. Enviar un evento nuevo con ese `order_id`, un `event_id` inédito y dos estados distintos.
3. Comprobar que cambia `status` en la fila existente, sin crear otra fila.
4. Guardar la ejecución de n8n y una captura del antes/después, con sus identificadores.

En las capturas de la hoja `Integraciones` se observan ejecuciones de cambios de estado correctas y errores cuando el pedido no existía todavía en el back-office. Para el vídeo conviene mostrar un caso concreto y sus registros asociados.

## Validación desde WordPress real (distinta de P1-P5)

La hoja `Integraciones` también contiene eventos generados por la tienda alojada en DonDominio, y la hoja `Incidencias` muestra casos de soporte enviados desde WordPress. Estos registros **no sustituyen** a las pruebas controladas con Hoppscotch ni demuestran por sí solos el recorrido completo de cada operación.

Para aportar evidencia de extremo a extremo, enlazar en el documento o vídeo:

1. Pedido o formulario enviado en la tienda.
2. `event_id` del registro de ESRETRO en WordPress/MariaDB.
3. Ejecución de n8n que recibió ese mismo evento.
4. Fila creada o actualizada en Google Sheets.
5. Cuando corresponda, notificación de Telegram y cambio de aprobación humana.

## Evidencias y cierre

- **Google Sheets → Pruebas:** estado P1-P5. Completar las descripciones de `actual_result` y `observations` para P2, P4 y P5 si siguen vacías.
- **Google Sheets → Pedidos:** fila de P1 con `event_id=991801` y ausencia de duplicado tras P2.
- **Google Sheets → Incidencias:** registro `INC-991803` y ausencia de duplicado tras P4.
- **Google Sheets → Integraciones:** registro del error controlado `ERR-38`, `event_id=991805`, ejecución 38.
- **n8n → Executions:** capturas de las ejecuciones relevantes, sin secretos ni datos personales reales.
- **Telegram:** evidencia de notificación y aprobación/rechazo vinculada a una incidencia, si se ha probado.

**Criterio de cierre:** una prueba solo se marca como superada tras observar el resultado correspondiente. El estado publicado aquí recoge las cinco pruebas controladas de integración descritas, no una auditoría de todas las funcionalidades de producción.
