# ESRETRO — Pruebas funcionales

## 1. Objetivo

Este documento recoge las pruebas realizadas sobre el prototipo ESRETRO.

El objetivo es comprobar de forma reproducible que el catálogo,
el carrito, el checkout, el pago simulado, los pedidos, los eventos
internos y el soporte funcionan de acuerdo con las reglas de negocio.

Las pruebas se realizan utilizando exclusivamente datos ficticios.

## 2. Entorno de pruebas

El proyecto utiliza:

- WordPress.
- WooCommerce.
- Plugin propio ESRETRO.
- Instalaciones locales independientes para cada integrante.
- Base de datos local independiente en cada equipo.
- Código compartido mediante GitHub.

La instalación pública definitiva se realizará posteriormente
en DonDominio.

## 3. Catálogo

### P01 — Visualización de producto

**Procedimiento**

1. Abrir una ficha de producto.
2. Comprobar nombre, precio, stock y atributos.
3. Consultar posteriormente la pantalla de eventos de ESRETRO.

**Resultado esperado**

- La ficha se muestra correctamente.
- El precio y el stock son coherentes.
- Se registra un evento `product.viewed`.

**Estado:** Superada en entorno local.

---

### P02 — Producto con variantes

**Procedimiento**

1. Abrir un producto variable.
2. Intentar añadirlo sin seleccionar una variante.
3. Seleccionar una variante válida.
4. Añadirla al carrito.

**Resultado esperado**

- WooCommerce obliga a seleccionar una variante.
- La variante seleccionada se conserva en el carrito.
- Se utiliza el precio y stock correspondientes.

**Estado:** Pendiente de validación final.

## 4. Carrito

### P03 — Añadir producto al carrito

**Procedimiento**

1. Abrir un producto.
2. Añadirlo al carrito.
3. Consultar el carrito.
4. Consultar la pantalla de eventos ESRETRO.

**Resultado esperado**

- El producto aparece en el carrito.
- La cantidad es correcta.
- Se registra `cart.item_added`.

**Estado:** Superada en entorno local.
---

### P04 — Modificar cantidades y eliminar productos

**Procedimiento**

1. Añadir un producto al carrito.
2. Cambiar su cantidad.
3. Actualizar el carrito.
4. Eliminar posteriormente el producto.

**Resultado esperado**

- El subtotal cambia según la cantidad.
- No se permiten cantidades inválidas.
- El producto puede eliminarse correctamente.

**Estado:** Pendiente de validación final.

## 5. Impuestos y envío

### P05 — IVA

**Procedimiento**

1. Añadir un producto al carrito.
2. Acceder al checkout.
3. Revisar los importes mostrados.

**Resultado esperado**

- Se aplica un IVA simulado del 21 %.
- Los precios mostrados incluyen IVA.
- El total calculado es coherente.

**Estado:** Superada en entorno local.

---

### P06 — Envío estándar

**Procedimiento**

1. Crear un carrito con total inferior a 100 €.
2. Acceder al checkout.

**Resultado esperado**

- Se aplica un coste de envío de 4,99 €.

**Estado:** Superada en entorno local.

---

### P07 — Envío gratuito

**Procedimiento**

1. Crear un carrito con total igual o superior a 100 €.
2. Acceder al checkout.

**Resultado esperado**

- Se aplica directamente el envío gratuito.
- No se mantiene como opción activa el envío estándar de 4,99 €.

**Estado:** Superada en entorno local.

## 6. Checkout y pago

### P08 — Inicio de checkout

**Procedimiento**

1. Añadir un producto al carrito.
2. Acceder al checkout.
3. Consultar los eventos de ESRETRO.

**Resultado esperado**

- Se registra `checkout.started`.
- No debe generarse más de una vez por la misma entrada al checkout.

**Estado:** Pendiente de comprobar tras la corrección de duplicados.

---

### P09 — Validación de campos obligatorios

**Procedimiento**

1. Acceder al checkout.
2. Dejar campos obligatorios vacíos.
3. Intentar finalizar el pedido.

**Resultado esperado**

- El pedido no se completa.
- WooCommerce muestra los errores correspondientes.

**Estado:** Pendiente de validación final.

---

### P10 — Pago simulado

**Procedimiento**

1. Completar el checkout con datos ficticios.
2. Seleccionar la pasarela de pago simulada ESRETRO.
3. Finalizar el pedido.

**Resultado esperado**

- No se realiza ningún cobro real.
- Se crea un pedido.
- Se registra `payment.simulated`.
- El pedido pasa a un estado coherente con un pago aceptado.

**Estado:** Superada en entorno local.

## 7. Pedidos

### P11 — Creación de pedido

**Procedimiento**

1. Completar una compra de prueba.
2. Consultar WooCommerce → Pedidos.
3. Consultar ESRETRO → Eventos.

**Resultado esperado**

- Se crea un pedido con identificador único.
- El pedido contiene productos, cantidades, impuestos, envío y total.
- Se registra `order.created`.

**Estado:** Superada en entorno local.

---

### P12 — Reducción de stock

**Procedimiento**

1. Anotar el stock de un producto.
2. Realizar una compra.
3. Volver a consultar el stock.

**Resultado esperado**

- El stock disminuye según la cantidad comprada.

**Estado:** Superada en entorno local.

## 8. Trazabilidad

### P13 — Recorrido completo de eventos

**Procedimiento**

Realizar un recorrido completo:

`producto → carrito → checkout → pago → pedido`

**Resultado esperado**

Deben poder localizarse los eventos:

- `product.viewed`
- `cart.item_added`
- `checkout.started`
- `order.created`
- `payment.simulated`

Cuando corresponda, los eventos deben incluir los identificadores
del producto o pedido relacionados.

**Estado:** Superada en entorno local.

## 9. Soporte

### P14 — Solicitud de soporte

**Procedimiento**

1. Abrir el formulario de soporte.
2. Introducir asunto y mensaje.
3. Asociar opcionalmente un pedido válido.
4. Enviar la solicitud.
5. Consultar ESRETRO → Eventos.

**Resultado esperado**

- La solicitud se procesa correctamente.
- Se registra `support.requested`.
- Si se proporciona un pedido, queda asociado al evento.

**Estado:** Superada en entorno local.

---

### P15 — Pedido inexistente en soporte

**Procedimiento**

1. Introducir un identificador de pedido inexistente.
2. Intentar enviar la solicitud.

**Resultado esperado**

- El sistema no debe asociar la incidencia a un pedido inexistente.

**Estado:** Pendiente de validación final.

## 10. Factura y albarán

### P16 — Generación de factura

**Procedimiento**

1. Abrir un pedido válido desde administración.
2. Generar su factura.
3. Comparar los importes con el pedido.

**Resultado esperado**

- La factura se genera correctamente.
- Productos, impuestos, envío y total coinciden con el pedido.

**Estado:** Pendiente de validación conjunta.

---

### P17 — Generación de albarán

**Procedimiento**

1. Abrir un pedido válido desde administración.
2. Generar el albarán.

**Resultado esperado**

- El albarán se genera correctamente.
- Los productos y cantidades coinciden con el pedido.

**Estado:** Pendiente de validación conjunta.

## 11. Despliegue público

### P18 — Flujo completo en DonDominio

**Procedimiento**

1. Acceder a la instalación pública.
2. Realizar una compra completa con datos ficticios.
3. Revisar pedido, stock y eventos desde administración.

**Resultado esperado**

Todo el recorrido validado localmente debe funcionar también
en la instalación pública definitiva.

**Estado:** Pendiente de despliegue.

## 12. Criterio de cierre

Antes de la entrega final, los tres integrantes deberán revisar
las pruebas pendientes y sustituir cada estado por el resultado
obtenido realmente.

No se marcará una prueba como superada únicamente porque el código
correspondiente exista. Debe haberse ejecutado y comprobado.
