
<?php

/**
 * Plugin Name: ESRETRO
 * Description: Funcionalidades propias del prototipo académico ESRETRO.
 * Version: 1.2.0
 * Author: Américo
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

//función para registrar los eventos de la página
//le pasamos el tipo, un pedido opcional y datos extra
function sie_retro_registrar_evento($tipo_evento, $order_id = null, $datos = null) {
    global $wpdb;

    //guardar la tabla en una variable
    $tabla = $wpdb->prefix . 'sie_retro_eventos';

    //WordPress inserta la fila en la tabla.
    $wpdb->insert(
        $tabla,
        array('tipo_evento' => $tipo_evento, 'order_id' => $order_id, 'user_id' => get_current_user_id() ?: null,
            'datos' => $datos ? wp_json_encode($datos) : null, 'fecha' => current_time('mysql'),),
        array('%s', '%d', '%d', '%s', '%s',)
    );
}

//conectamos WooCommerce
//WooCommerce crea pedido --> woocommerce_checkout_order_created
add_action('woocommerce_checkout_order_created', 'sie_retro_evento_pedido_creado');

//función para registrar el evento al crear un pedido
function sie_retro_evento_pedido_creado($order) {
    //si no hay ninguna instancia de WC_Order sale de la función
    if (!$order instanceof WC_Order) {
        return;
    }

    //aqui se llama a la función de antes pasándole los parámetros que toca
    sie_retro_registrar_evento('order.created', $order->get_id(), array('total' => $order->get_total(),
            'moneda' => $order->get_currency(), 'estado' => $order->get_status(), ));
}

//Cuando WP está generando el menú de administración, ejecuta mi función sie_retro_menu_admin de mas abajo
add_action('admin_menu', 'sie_retro_menu_admin');

//función que crea el nuevo elemento del menú.
function sie_retro_menu_admin() {

    //añadir una nueva sección al menú lateral del panel de WordPress.
    //Eventos ESRETRO --> nombre interno
    //ESRETRO --> nombre en la barra lateral
    //manage_woocommerce --> usuarios con permisos
    // sie-retro-eventos --> identificador interno (URL)
    // sie_retro_pagina_eventos --> función de mas abajo que construye la pantalla
    // dashicons-chart-area --> icono
    // 56 --> posición dentro dle menu lateral
    add_menu_page('Eventos ESRETRO', 'ESRETRO', 'manage_woocommerce', 'sie-retro-eventos',
        'sie_retro_pagina_eventos', 'dashicons-chart-area', 56);
}

//función principal para el panel de eventos
function sie_retro_pagina_eventos() {
    global $wpdb; //objeto/conexión para trabajar con la bbdd de WP

    $tabla = $wpdb->prefix . 'sie_retro_eventos'; //guardamos el nombre de la tabla

    //comprobar si se ha seleccionado un tipo de evento para filtrar
    $filtro_evento = isset($_GET['tipo_evento']) ? sanitize_text_field(wp_unslash($_GET['tipo_evento'])) : '';

    //si hay filtro buscamos solo ese tipo de evento
    if (!empty($filtro_evento)) {

        $eventos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabla WHERE tipo_evento = %s ORDER BY fecha DESC", $filtro_evento));

    } else {

        //si no hay filtro mostramos todos los eventos
        $eventos = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha DESC");
    }

    //ponerlo bonito
    echo '<div class="wrap">';
    echo '<h1>Eventos ESRETRO</h1>';

    //formulario para filtrar eventos por tipo
    echo '<form method="get" style="margin-bottom: 20px;">';

    //campo obligatorio para mantenernos dentro de la página ESRETRO
    echo '<input type="hidden" name="page" value="sie-retro-eventos">';

    echo '<label for="tipo_evento"><strong>Filtrar por evento:</strong></label> ';
    echo '<select name="tipo_evento" id="tipo_evento">';

    echo '<option value="">Todos los eventos</option>';

    echo '<option value="product.viewed"'
        . selected($filtro_evento, 'product.viewed', false)
        . '>product.viewed</option>';

    echo '<option value="cart.item_added"'
        . selected($filtro_evento, 'cart.item_added', false)
        . '>cart.item_added</option>';

    echo '<option value="checkout.started"'
        . selected($filtro_evento, 'checkout.started', false)
        . '>checkout.started</option>';

    echo '<option value="order.created"'
        . selected($filtro_evento, 'order.created', false)
        . '>order.created</option>';

    echo '<option value="payment.simulated"'
        . selected($filtro_evento, 'payment.simulated', false)
        . '>payment.simulated</option>';

    echo '<option value="support.requested"'
        . selected($filtro_evento, 'support.requested', false)
        . '>support.requested</option>';

    echo '</select> ';

    echo '<button type="submit" class="button">Filtrar</button>';

    echo '</form>';

    //comprobar si hay eventos y controlar la salida
    if (empty($eventos)) {
        echo '<p>No hay eventos registrados todavía.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat striped">';//empezar tabla
    echo '<thead>';//cabecera
    echo '<tr>';//fila
    //columnas
    echo '<th>ID</th>';
    echo '<th>Evento</th>';
    echo '<th>Pedido</th>';
    echo '<th>Usuario</th>';
    echo '<th>Datos</th>';
    echo '<th>Fecha</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    //para cada evento insertamos una fila en la tabla
    foreach ($eventos as $evento) {
        echo '<tr>';

        //el esc_html hace que el uso del texto sea seguro
        echo '<td>' . esc_html($evento->id) . '</td>';
        echo '<td>' . esc_html($evento->tipo_evento) . '</td>';

        //si el evento tiene un pedido asociado intentamos buscarlo en WooCommerce
        if ($evento->order_id) {
            $pedido = wc_get_order($evento->order_id);

            //si el pedido existe ponemos un enlace directo a su pantalla de edición
            if ($pedido) {
                echo '<td><a href="' . esc_url($pedido->get_edit_order_url()) . '">' . esc_html($evento->order_id) . '</a></td>';
            } else {
                echo '<td>' . esc_html($evento->order_id) . '</td>';
            }
        } else {
            echo '<td></td>';
        }

        echo '<td>' . esc_html($evento->user_id ?? '') . '</td>';
        echo '<td><code>' . esc_html($evento->datos ?? '') . '</code></td>'; //datos extra
        echo '<td>' . esc_html($evento->fecha) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

//crea un shortcode de WordPress. cuando escribamos [sie_retro_soporte] en un bloque de tipo shortcode
//se ejecutará la función de mas abajo para mostrar un formulario hy enviarlo
add_shortcode('sie_retro_soporte', 'sie_retro_formulario_soporte');

//función hecha con IA
function sie_retro_formulario_soporte() {
    $mensaje_resultado = '';//variable para guardar el mensaje

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sie_retro_soporte_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sie_retro_soporte_nonce'])),
            'sie_retro_enviar_soporte')) {
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field(wp_unslash($_POST['mensaje'])):'';

        if (empty($mensaje)) {
            $mensaje_resultado = '<p>Debes escribir una descripción de la incidencia.</p>';

        } elseif ($order_id && !wc_get_order($order_id)) {
            $mensaje_resultado = '<p>El número de pedido indicado no existe.</p>';

        } else {
            sie_retro_registrar_evento('support.requested', $order_id ?: null, array('mensaje' => $mensaje));

        $mensaje_resultado = '<p>Solicitud de soporte registrada correctamente.</p>';
        }
    }

    ob_start();

    echo $mensaje_resultado;
    ?>

    <form method="post"> <!-- pulsar enviar manda los datos al servidor -->

        <p>
            <label for="order_id">
                Número de pedido (opcional)
            </label>
            <br>

            <input
                type="number"
                id="order_id"
                name="order_id"
                min="1"
            >
        </p>

        <p>
            <label for="mensaje">
                Describe la incidencia
            </label>
            <br>

            <textarea
                id="mensaje"
                name="mensaje"
                rows="5"
                required
            ></textarea>
        </p>

        <?php
        wp_nonce_field('sie_retro_enviar_soporte', 'sie_retro_soporte_nonce');
        ?>

        <p>
            <button type="submit">
                Enviar solicitud
            </button>
        </p>

    </form>

    <?php

    return ob_get_clean();
}

//cargar la pasarela simulada cuando WooCommerce esté disponible
add_action( 'plugins_loaded', function () {

    //si WooCommerce no está activo no seguimos
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-sie-retro-simulated-gateway.php';

    //añadir nuestra pasarela a los métodos de pago de WooCommerce
    add_filter('woocommerce_payment_gateways', function ( $gateways ) {
        $gateways[] = 'WC_Gateway_SIE_Retro_Simulated';

        return $gateways;
        }
    );
}, 20 );

//integración de la pasarela con el checkout moderno de WooCommerce Blocks
add_action( 'woocommerce_blocks_loaded', function () {
    $integration_class = 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType';

    //si WooCommerce Blocks no dispone de esta clase no seguimos
    if ( ! class_exists( $integration_class ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-sie-retro-simulated-blocks.php';

    add_action('woocommerce_blocks_payment_method_type_registration', 
        function ( $registry ) {
            $registry->register(new SIE_Retro_Simulated_Blocks_Integration());
        });
});

