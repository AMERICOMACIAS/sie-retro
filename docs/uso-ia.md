# Registro de uso de IA

## Sesión del 5 de octubre de 2026

### Herramienta
ChatGPT.

### Tareas asistidas
- Interpretación del enunciado y planificación del trabajo.
- Explicación de interfaz, servidor y base de datos.
- Preparación del entorno local con XAMPP.
- Resolución del conflicto del puerto 80 de Apache.
- Instalación inicial de WordPress y WooCommerce.
- Configuración del repositorio y redacción inicial del README.

### Partes relevantes asistidas
Instrucciones de instalación, archivo .gitignore, comandos de Git
y contenido inicial del README.

En ese momento todavía no se había desarrollado código funcional propio de la tienda.

### Incidencias y correcciones
Se creó el repositorio antes de añadir .gitignore.
El primer commit incorporó la instalación completa, incluido wp-config.php.

Con ayuda de la IA se retiraron los archivos del seguimiento,
se aplicaron las exclusiones y se reemplazó el commit inicial
antes de publicar el repositorio.

### Decisiones y acciones del grupo
El grupo eligió WordPress y WooCommerce porque uno de los integrantes
tiene experiencia con WordPress.

Los tres integrantes prepararon sus instalaciones locales siguiendo
las instrucciones durante la sesión.

Américo creó y publicó el único repositorio compartido del proyecto
e invitó a sus dos compañeros como colaboradores.

### Alcance de las comprobaciones
Las capturas y el informe de estado revisados con ChatGPT corresponden
al equipo de Américo.

Cada compañero debe confirmar las versiones y el funcionamiento
de su propia instalación. Las incidencias descritas no se atribuyen
a los tres equipos salvo que se hayan producido en todos ellos.

### Validación realizada
- Apache arrancó tras cambiar su puerto a 8080.
- Se abrió la bienvenida de XAMPP.
- phpMyAdmin mostró acceso a la base de datos.
- Se accedió al escritorio de WordPress y al inicio de WooCommerce.
- Se revisó el informe de estado de WooCommerce.
- Antes de publicar, git ls-files mostró únicamente
  .gitattributes y .gitignore.
- Después se añadió y publicó el README.

### Pendiente de validar
Confirmación de las versiones y comprobaciones de los otros dos equipos,
compra completa, eventos, factura de prueba y funcionamiento en DonDominio.

## Sesiones del 9 al 16 de octubre de 2026

### Herramienta
ChatGPT.

### Tareas asistidas
Durante esta fase se utilizó ChatGPT como apoyo para:

- Diseñar e implementar el plugin propio ESRETRO.
- Registrar eventos persistentes relacionados con el proceso de compra.
- Crear una vista administrativa para consultar y filtrar eventos.
- Implementar y revisar el formulario de soporte.
- Integrar la pasarela de pago simulada con WooCommerce.
- Adaptar el pago simulado al checkout por bloques de WooCommerce.
- Registrar los eventos `product.viewed`, `cart.item_added`,
  `checkout.started`, `order.created`, `payment.simulated`
  y `support.requested`.
- Revisar el funcionamiento de productos simples y variables.
- Configurar y comprobar IVA, costes de envío y envío gratuito.
- Organizar el trabajo colaborativo mediante ramas,
  commits y Pull Requests en GitHub.
- Resolver problemas surgidos durante la integración del código
  desarrollado por los tres integrantes.
- Preparar pruebas reproducibles del recorrido completo de compra.

### Partes relevantes asistidas

La IA ayudó a proponer estructuras y fragmentos de código para
el plugin ESRETRO, especialmente en el registro de eventos,
integración con hooks de WooCommerce, administración,
soporte y conexión con la pasarela de pago simulada.

Las propuestas no se incorporaron directamente sin comprobación.
El grupo revisó los fragmentos, los adaptó al proyecto y realizó
pruebas en sus instalaciones locales antes de integrarlos en `main`.

También se utilizó ChatGPT para explicar el funcionamiento de Git
y GitHub durante la integración de las ramas de los tres integrantes.

### Incidencias y correcciones

Durante el desarrollo se detectaron varias diferencias entre el
comportamiento esperado y el comportamiento real de WooCommerce.

Inicialmente el evento `order.created` no se registraba correctamente
con alguno de los hooks probados para Checkout Blocks. Se realizaron
pedidos de prueba y finalmente se comprobó que
`woocommerce_store_api_checkout_order_processed` permitía registrar
correctamente el evento en la instalación utilizada por el grupo.

Durante esta revisión la IA propuso temporalmente utilizar otro hook.
La recomendación se reconsideró al comparar la documentación,
el código existente y, especialmente, los resultados de las pruebas
realizadas por el grupo. Se mantuvo finalmente la solución que había
demostrado funcionar en el entorno del proyecto.

También se detectó que `checkout.started` podía registrarse varias
veces al recargar o procesar la página de checkout. Se preparó una
corrección para evitar eventos duplicados utilizando la sesión de
WooCommerce.

Durante la integración también fue necesario coordinar ramas que
modificaban el mismo plugin. Para evitar sobrescribir trabajo,
se adoptó el flujo rama de funcionalidad, commit, Pull Request,
prueba por otro integrante y posterior integración en `main`.

### Decisiones y acciones del grupo

El grupo mantuvo instalaciones locales independientes de WordPress
y WooCommerce. GitHub se utiliza para compartir el código propio
y la documentación, pero no las bases de datos locales.

Se decidió centralizar las funcionalidades propias en el plugin
ESRETRO en lugar de modificar directamente WooCommerce.

Las configuraciones de IVA y envío utilizadas en las pruebas son:

- IVA del 21 % incluido en los precios.
- Envío estándar de 4,99 €.
- Envío gratuito para pedidos de 100 € o más.

El pago utilizado por el proyecto es completamente simulado y
no procesa ni almacena datos bancarios reales.

### Validación realizada

Se han realizado pedidos de prueba completos en más de una instalación
local del grupo.

Se ha comprobado el recorrido:

`product.viewed` → `cart.item_added` → `checkout.started`
→ `order.created` → `payment.simulated`.

También se ha comprobado por separado el registro de
`support.requested`.

Los eventos pueden consultarse desde la pantalla administrativa
propia de ESRETRO y los eventos asociados a pedidos muestran el
identificador correspondiente.

Se comprobó que el pago simulado crea el pedido y que el estado
del pedido evoluciona de forma coherente durante el proceso.

### Pendiente de validar

Quedan pendientes la batería final de pruebas del proyecto integrado,
la revisión conjunta de factura y albarán, el despliegue público
en DonDominio y la validación completa sobre la instalación definitiva.
