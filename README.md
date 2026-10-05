# ESRETRO — Tarea 1 de SIE

Prototipo académico de tienda de consolas antiguas y videojuegos
físicos reacondicionados. Sin actividad comercial real.

## Objetivo

Construir un proceso de compra con catálogo, carrito, checkout,
pago simulado, pedido persistente y registro de eventos.

El catálogo tendrá al menos 8 productos distintos.
Al menos 2 productos tendrán variantes.

Las fichas indicarán consola compatible, género cuando corresponda,
estado de conservación y contenido incluido: completo (CIB)
o solo cartucho/disco.

## Punto de partida

Utilizamos WordPress y WooCommerce como componentes de terceros.
El grupo desarrollará y documentará las adaptaciones necesarias.

Utilizamos ChatGPT como apoyo para configurar el entorno y comprender
los pasos. El detalle de su uso se documentará durante el proyecto.

## Entorno local comprobado inicialmente

- Windows.
- XAMPP 8.2.12.
- Apache en el puerto 8080 en el equipo de Américo.
- PHP 8.2.12.
- MariaDB 10.4.32.
- WordPress 7.1.
- WooCommerce 11.1.2.
- Tema Twenty Twenty-Five.

Cada integrante tiene su propia instalación y base de datos local.

## Instalación local inicial

1. Instalar XAMPP y arrancar Apache y MySQL.
2. Crear una base de datos vacía llamada `sie_retro`,
   con cotejamiento `utf8mb4_unicode_ci`.
3. Colocar los archivos de WordPress en:
   `C:\xampp\htdocs\sie-retro`.
4. Abrir `http://localhost:8080/sie-retro/`.
   Si Apache utiliza el puerto 80, omitir `:8080`.
5. Completar el instalador con la conexión local de cada integrante.
6. Crear un administrador exclusivo para el prototipo.
7. Instalar y activar WooCommerce.
8. Configurar el nombre ESRETRO, sector Electrónica y ordenadores,
   ubicación ficticia España — Murcia y correo admin@example.com.
9. Omitir las extensiones comerciales opcionales del asistente.

Estas instrucciones describen la instalación inicial.
Se completarán con las adaptaciones y los datos de prueba del proyecto.

## Qué contiene este repositorio

- Configuración del seguimiento con Git.
- Documentación.
- Plugin propio y tema hijo, cuando se creen.

WordPress y WooCommerce se instalan por separado.
El repositorio no incluye sus archivos, la base de datos,
las imágenes subidas ni `wp-config.php`.

Cambiar productos o ajustes desde WordPress no los sincroniza
automáticamente mediante GitHub.

## Estado actual

WordPress y WooCommerce instalados en el equipo de Américo.
Repositorio publicado e invitaciones enviadas a los compañeros.

Pendientes: catálogo, configuración de compra, pago simulado,
factura de prueba, eventos, adaptaciones, pruebas y despliegue.

La instalación todavía no constituye una tienda terminada.

## Incidencias conocidas

En el equipo de Américo, el puerto 80 estaba ocupado.
Se configuró Apache para utilizar el puerto 8080.

El funcionamiento en el hosting de DonDominio está pendiente
de comprobar, especialmente por su límite de memoria.

## Credenciales

No se incluyen contraseñas, claves ni credenciales reales.
Cada integrante conserva su configuración local fuera de Git.