<?php

/**
 * Plugin Name: ESRETRO
 * Description: Funcionalidades propias del prototipo académico ESRETRO.
 * Version: 0.1.0
 * Author: Equipo ESRETRO
 */

//evita que alguien intente ejecutar directamente el archivo PHP fuera de WordPress.
if (!defined('ABSPATH')) {
    exit;
}

//ejecuta una función cuando se activa el plugin.
register_activation_hook(__FILE__, 'sie_retro_crear_tabla_eventos');

function sie_retro_crear_tabla_eventos() {
    global $wpdb;//es la conexión a la base de datos que WordPress ya tiene preparada.

    $tabla = $wpdb->prefix . 'sie_retro_eventos'; //usa el prefijo de tablas de WordPress, normalmente wp_, para crear la tabla o actualizarla.
    $charset_collate = $wpdb->get_charset_collate();

    //Aquí se guarda la query para llamarla mas abajo
    $sql = "CREATE TABLE $tabla (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        tipo_evento VARCHAR(100) NOT NULL,
        order_id BIGINT UNSIGNED NULL,
        user_id BIGINT UNSIGNED NULL,
        datos LONGTEXT NULL,
        fecha DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql); //aquí se crea o actualiza la tabla sin tener que escribir SQL manualmente en phpMyAdmin.
}