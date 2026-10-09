# ESRETRO — Reglas de negocio

## 1. Objetivo

ESRETRO es un prototipo académico de comercio electrónico dedicado a consolas clásicas y videojuegos físicos reacondicionados. No se realizarán ventas ni cobros reales.

Estas reglas definen el comportamiento esperado del proceso de compra y servirán como referencia para la implementación y las pruebas.

## 2. Catálogo y variantes

- La tienda dispone de un catálogo de al menos ocho productos distintos.
- Al menos dos productos ofrecen variantes seleccionables.
- Las variantes deben conservar su identificación y las opciones elegidas durante todo el proceso de compra.
- La información de conservación debe describir correctamente el estado de la caja, el manual y el contenido incluido.
- Los productos y sus variantes deben mostrar un precio y una disponibilidad coherentes.

## 3. Carrito

- El cliente puede añadir productos al carrito, modificar cantidades y eliminar artículos.
- La cantidad de cada artículo debe ser un número entero positivo.
- Los productos con variantes deben conservar la opción seleccionada.
- El subtotal se calcula a partir del precio aplicado y la cantidad.
- No se permite continuar con un carrito vacío.

## 4. Checkout

- El cliente utilizará exclusivamente datos ficticios.
- Los campos obligatorios deben validarse antes de crear el pedido.
- El servidor debe comprobar los productos, las cantidades, las variantes y los precios antes de confirmar la compra.
- Los importes definitivos deben calcularse a partir de los datos del servidor, no de los valores enviados por el navegador.

## 5. Impuestos y envío

Propuesta pendiente de aprobación por el grupo:

- Moneda: euro (EUR).
- País de destino de las pruebas: España.
- IVA simulado: 21 %.
- Envío estándar: 4,99 €.
- Envío gratuito para pedidos que alcancen 100 €.

Antes de implementar estas condiciones, el grupo debe decidir si los precios del catálogo incluyen IVA y cómo se aplican los umbrales de envío gratuito.

## 6. Pago simulado

- El prototipo no realizará cobros reales.
- Se utilizará un método de pago simulado propio del proyecto.
- El sistema registrará el resultado de la simulación.
- No se solicitarán ni almacenarán números de tarjeta reales ni credenciales bancarias.

## 7. Pedidos

- Cada compra confirmada tendrá un identificador único.
- El pedido conservará los datos ficticios necesarios, las líneas de compra, las variantes elegidas, las cantidades y los precios aplicados.
- Se registrarán los importes correspondientes a productos, impuestos, envío y total.
- El pedido tendrá un estado coherente con el resultado del proceso.
- Los pedidos podrán consultarse desde el área de administración.

## 8. Confirmación y factura

- Tras completar correctamente la compra, el cliente llegará a una página de agradecimiento.
- La confirmación mostrará el identificador del pedido y el resumen de la compra.
- Se generará una factura de prueba con datos ficticios y los importes correspondientes.
- La información de la factura debe coincidir con la registrada en el pedido.

## 9. Eventos internos

El sistema deberá registrar, como mínimo, los siguientes tipos de eventos:

- `product.viewed`: consulta de un producto.
- `cart.item_added`: artículo añadido al carrito.
- `checkout.started`: inicio del checkout.
- `order.created`: creación del pedido.
- `payment.simulated`: resultado del pago simulado.
- `support.requested` o `incident.created`: solicitud de soporte o incidencia.

Los eventos deben permitir reconstruir el recorrido de una compra de prueba.

## 10. Validación de las reglas

Cada regla deberá comprobarse mediante pruebas reproducibles. Los resultados reales de esas pruebas se documentarán por separado.

Las reglas comerciales propuestas en el apartado 5 no se considerarán definitivas hasta que las apruebe el grupo.

