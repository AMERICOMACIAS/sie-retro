# ESRETRO | Registro complementario de uso de IA, Tarea 2

**Complemento de:** [`uso-ia.md`](uso-ia.md), que documenta la Tarea 1.  
**Periodo principal:** noviembre de 2026.  
**Proyecto:** ESRETRO, Tarea 2 de Soluciones Informáticas para la Empresa.

> **Transparencia:** esta relación recoge las interacciones y decisiones conocidas del proyecto. Los apartados relativos a aportaciones de otros integrantes deben revisarse por sus autores antes de entregar. No atribuye a la IA pruebas que fueron ejecutadas manualmente.

## 1. Herramientas y finalidades diferenciadas

Se utilizaron asistentes de IA conversacional, principalmente **ChatGPT**, como apoyo al **desarrollo, revisión y documentación**. Este uso debe distinguirse del **modelo de OpenAI integrado en n8n**, que interviene funcionalmente en la clasificación y propuesta de respuestas a incidencias postventa.

### IA de apoyo al desarrollo

- Interpretación del enunciado, aclaraciones posteriores y relación con los temas de la asignatura.
- Diseño del contrato de integración JSON v1.0 y discusión de identificadores, tipos de eventos y reglas de validación.
- Explicación de webhooks, HTTP POST, HTTPS, autenticación por cabecera, WP-Cron, reintentos e idempotencia.
- Apoyo en la construcción y revisión de workflows n8n para pedidos, cambios de estado e incidencias.
- Diseño de pruebas reproducibles con Hoppscotch y análisis de resultados de n8n y Google Sheets.
- Apoyo en la revisión de la memoria técnica y la matriz de decisión
- Preparación de README y documentación técnica complementaria para GitHub.

### IA como componente del prototipo

En el flujo de incidencias se utiliza un modelo de lenguaje conectado a n8n para clasificar solicitudes, asignar prioridades, explicar la clasificación y proponer una respuesta. En la exportación revisada, la salida incluye `category`, `priority`, `ai_analysis` y `ai_response`. La respuesta se almacena para revisión humana y no se envía automáticamente al cliente.

## 2. Decisiones que tomó el grupo y validación humana

- Se conservó la arquitectura de la Tarea 1 (WordPress/WooCommerce y plugin propio PHP) y se incorporó un back-office independiente en Google Sheets.
- Se escogió n8n Cloud para desplegar workflows fuera del entorno local y coordinar aplicaciones externas.
- Se decidió utilizar identificadores de evento para controlar duplicados y una pestaña `Integraciones` para registrar éxitos y errores.
- Se utilizó revisión humana para las propuestas de respuesta de la IA y, según la memoria conjunta, un flujo adicional de Telegram para aprobar o rechazar propuestas.
- Se revisaron el código y los resultados reales en GitHub, n8n, WordPress y Google Sheets antes de describirlos en la memoria.

Las decisiones de integración, publicación y pruebas fueron adoptadas y ejecutadas por el equipo. Las sugerencias de los asistentes de IA no constituyeron verificación por sí mismas.

## 3. Ejemplos de correcciones y límites detectados

- **Duplicados:** se diferenciaron las medidas del plugin (marca en metadatos del pedido) y las del receptor (consulta de eventos procesados en n8n).
- **Entrega diferida:** se comprobó el uso de WP-Cron y se documentó que una tarea prevista para unos segundos después puede ejecutarse más tarde cuando el sitio tiene poca actividad.
- **Validaciones:** en la prueba P5 se envió deliberadamente una `schema_version` incorrecta; el sistema registró `ERR-38` en `Integraciones`.
- **IA agéntica:** se distinguió una cadena LLM con salida estructurada de un agente capaz de llamar autónomamente a herramientas.
- **Telegram:** se diferenciaron creación del bot, aviso de incidencia y aprobación humana efectiva. La memoria describe botones de aprobación/rechazo; corresponde guardar las ejecuciones que acrediten su funcionamiento.
- **GitHub:** los archivos exportados pueden quedar desactualizados respecto al workflow publicado en n8n; se pidió actualizar los JSON definitivos y evitar subir secretos.

## 4. Validación realizada por las personas

Se desarrolló una batería de cinco pruebas de integración con Hoppscotch → n8n → Google Sheets, recogidas en [`pruebas-tarea2.md`](pruebas-tarea2.md): alta de pedido, deduplicación de pedido, alta de incidencia, deduplicación de incidencia y error de contrato. Las capturas de Google Sheets muestran estos cinco casos marcados como correctos.

El equipo también inspeccionó registros procedentes de la tienda WordPress. No deben confundirse estas comprobaciones con las cinco pruebas controladas ejecutadas en Hoppscotch.

Las propuestas de documentación y guion se contrastaron con el código PHP, las exportaciones n8n, el enunciado, los temas y las evidencias del proyecto.
