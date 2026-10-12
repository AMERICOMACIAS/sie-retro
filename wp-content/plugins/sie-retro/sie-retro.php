<?php
/**
 * Plugin Name: ESRETRO
 * Description: Funcionalidades propias del prototipo académico ESRETRO.
 * Version: 1.3.0
 * Author: Javier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga los componentes propios cuando WordPress inicia los plugins.
 */
add_action( 'plugins_loaded', 'sie_retro_inicializar_plugin', 20 );

function sie_retro_inicializar_plugin() {
    // La pasarela y los eventos relacionados con pedidos requieren WooCommerce.
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-sie-retro-events.php';
    require_once __DIR__ . '/includes/class-sie-retro-simulated-gateway.php';
    require_once __DIR__ . '/includes/class-sie-retro-support.php';

    // Registro centralizado de eventos de navegación y compra.
    add_action( 'woocommerce_checkout_order_created', 'sie_retro_evento_pedido_creado', 10, 1 );
    add_action( 'woocommerce_store_api_checkout_order_created', 'sie_retro_evento_pedido_creado', 10, 1 );

    add_action( 'wp', 'sie_retro_registrar_evento_checkout' );
    add_action( 'wp', 'sie_retro_registrar_evento_producto' );
    add_action( 'woocommerce_add_to_cart', 'sie_retro_registrar_evento_carrito', 10, 6 );

    // Registrar la pasarela simulada.
    add_filter( 'woocommerce_payment_gateways', 'sie_retro_registrar_pasarela' );

    // Inicializar el formulario de soporte si la clase lo implementa.
    if ( class_exists( 'SIE_Retro_Support' ) && is_callable( array( 'SIE_Retro_Support', 'init' ) ) ) {
        SIE_Retro_Support::init();
    }
}

/**
 * Crea la tabla de eventos al activar el plugin.
 *
 * La clase de eventos es responsable de definir el esquema real de la tabla.
 */
register_activation_hook( __FILE__, 'sie_retro_activar_plugin' );

function sie_retro_activar_plugin() {
    require_once __DIR__ . '/includes/class-sie-retro-events.php';

    if ( is_callable( array( 'SIE_Retro_Events', 'install' ) ) ) {
        SIE_Retro_Events::install();
    } else {
        // Compatibilidad temporal con la función de instalación de la versión de Américo.
        sie_retro_crear_tabla_eventos();
    }
}

/**
 * Instalación de compatibilidad con la implementación anterior de Américo.
 * Se ejecuta únicamente si la clase nueva no ofrece install().
 */
function sie_retro_crear_tabla_eventos() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'sie_retro_eventos';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        tipo_evento VARCHAR(100) NOT NULL,
        order_id BIGINT UNSIGNED NULL,
        user_id BIGINT UNSIGNED NULL,
        datos LONGTEXT NULL,
        fecha DATETIME NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Añade la pasarela propia a los métodos disponibles de WooCommerce.
 */
function sie_retro_registrar_pasarela( $gateways ) {
    $gateways[] = 'WC_Gateway_SIE_Retro_Simulated';
    return $gateways;
}

/**
 * Evento order.created. El hook Store API es el usado por el checkout por bloques.
 */
function sie_retro_evento_pedido_creado( $order ) {
    if ( ! $order instanceof WC_Order ) {
        return;
    }

    $datos = array(
        'total'   => $order->get_total(),
        'moneda'  => $order->get_currency(),
        'estado'  => $order->get_status(),
        'origen'  => current_filter(),
    );

    sie_retro_guardar_evento( 'order.created', $datos, $order->get_id() );
}

/**
 * Evento payment.simulated.
 * Se escucha el hook de la pasarela para evitar registrar el mismo pago
 * tanto desde la pasarela como desde este archivo.
 */
add_action( 'sie_retro_payment_simulated', 'sie_retro_evento_pago_simulado', 10, 2 );

function sie_retro_evento_pago_simulado( $order_id, $resultado ) {
    $pedido = wc_get_order( $order_id );

    if ( ! $pedido ) {
        return;
    }

    sie_retro_guardar_evento(
        'payment.simulated',
        array(
            'resultado' => sanitize_text_field( $resultado ),
            'total'     => $pedido->get_total(),
            'moneda'    => $pedido->get_currency(),
            'estado'    => $pedido->get_status(),
        ),
        $pedido->get_id()
    );
}

/**
 * Evento checkout.started.
 */
function sie_retro_registrar_evento_checkout() {
    if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
        sie_retro_guardar_evento( 'checkout.started', array() );
    }
}

/**
 * Evento product.viewed.
 */
function sie_retro_registrar_evento_producto() {
    if ( function_exists( 'is_product' ) && is_product() ) {
        sie_retro_guardar_evento(
            'product.viewed',
            array(),
            null,
            get_queried_object_id()
        );
    }
}

/**
 * Evento cart.item_added.
 */
function sie_retro_registrar_evento_carrito( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    $id_producto = $variation_id ? $variation_id : $product_id;

    sie_retro_guardar_evento(
        'cart.item_added',
        array(
            'product_id'   => absint( $id_producto ),
            'parent_id'    => absint( $product_id ),
            'variation_id' => absint( $variation_id ),
            'quantity'     => absint( $quantity ),
        ),
        null,
        absint( $id_producto )
    );
}

/**
 * Adaptador al registrador de eventos disponible en la clase instalada.
 * No crea tablas ni inserta SQL directamente desde el archivo principal.
 */
function sie_retro_guardar_evento( $tipo, $datos = array(), $order_id = null, $product_id = null ) {
    if ( class_exists( 'SIE_Retro_Events' ) && is_callable( array( 'SIE_Retro_Events', 'record' ) ) ) {
        SIE_Retro_Events::record(
            $tipo,
            array_merge(
                is_array( $datos ) ? $datos : array(),
                array_filter(
                    array(
                        'order_id'   => $order_id,
                        'product_id' => $product_id,
                    ),
                    static function ( $value ) {
                        return null !== $value;
                    }
                )
            )
        );
        return;
    }

    // Compatibilidad con la función del archivo antiguo de Américo.
    if ( function_exists( 'sie_retro_registrar_evento' ) ) {
        sie_retro_registrar_evento( $tipo, $order_id, $datos );
    }
}

/**
 * Menú de administración para consultar eventos.
 * Se apoya en la tabla que use la clase SIE_Retro_Events.
 */
add_action( 'admin_menu', 'sie_retro_menu_admin' );

function sie_retro_menu_admin() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    add_menu_page(
        'Eventos ESRETRO',
        'ESRETRO',
        'manage_woocommerce',
        'sie-retro-eventos',
        'sie_retro_pagina_eventos',
        'dashicons-chart-area',
        56
    );
}

function sie_retro_pagina_eventos() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'No tienes permisos para ver estos eventos.', 'sie-retro' ) );
    }

    global $wpdb;

    // La implementación conocida de SIE_Retro_Events usa esta tabla y estas columnas.
    $tabla = $wpdb->prefix . 'sie_retro_events';
    $filtro = isset( $_GET['tipo_evento'] )
        ? sanitize_text_field( wp_unslash( $_GET['tipo_evento'] ) )
        : '';

    $permitidos = array(
        'product.viewed',
        'cart.item_added',
        'checkout.started',
        'order.created',
        'payment.simulated',
        'support.requested',
        'incident.created',
    );

    if ( $filtro && ! in_array( $filtro, $permitidos, true ) ) {
        $filtro = '';
    }

    if ( $filtro ) {
        $eventos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE event_type = %s ORDER BY occurred_at DESC LIMIT 500",
                $filtro
            )
        );
    } else {
        $eventos = $wpdb->get_results( "SELECT * FROM $tabla ORDER BY occurred_at DESC LIMIT 500" );
    }

    echo '<div class="wrap">';
    echo '<h1>Eventos ESRETRO</h1>';
    echo '<form method="get" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="page" value="sie-retro-eventos">';
    echo '<label for="tipo_evento"><strong>Filtrar por evento:</strong></label> ';
    echo '<select name="tipo_evento" id="tipo_evento">';
    echo '<option value="">Todos los eventos</option>';

    foreach ( $permitidos as $tipo ) {
        echo '<option value="' . esc_attr( $tipo ) . '"' . selected( $filtro, $tipo, false ) . '>' . esc_html( $tipo ) . '</option>';
    }

    echo '</select> <button type="submit" class="button">Filtrar</button>';
    echo '</form>';

    if ( empty( $eventos ) ) {
        echo '<p>No hay eventos registrados todavía.</p></div>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>ID</th><th>Evento</th><th>Producto</th><th>Pedido</th><th>Datos</th><th>Fecha</th>';
    echo '</tr></thead><tbody>';

    foreach ( $eventos as $evento ) {
        echo '<tr>';
        echo '<td>' . esc_html( $evento->id ) . '</td>';
        echo '<td>' . esc_html( $evento->event_type ) . '</td>';

        $producto_id = isset( $evento->product_id ) ? absint( $evento->product_id ) : 0;
        echo '<td>' . ( $producto_id ? esc_html( $producto_id ) : '' ) . '</td>';

        $order_id = isset( $evento->order_id ) ? absint( $evento->order_id ) : 0;
        if ( $order_id && function_exists( 'wc_get_order' ) ) {
            $pedido = wc_get_order( $order_id );
            if ( $pedido ) {
                echo '<td><a href="' . esc_url( $pedido->get_edit_order_url() ) . '">' . esc_html( $order_id ) . '</a></td>';
            } else {
                echo '<td>' . esc_html( $order_id ) . '</td>';
            }
        } else {
            echo '<td>' . ( $order_id ? esc_html( $order_id ) : '' ) . '</td>';
        }

        echo '<td><code>' . esc_html( isset( $evento->event_data ) ? $evento->event_data : '' ) . '</code></td>';
        echo '<td>' . esc_html( isset( $evento->occurred_at ) ? $evento->occurred_at : '' ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

/**
 * El formulario de soporte se implementa en class-sie-retro-support.php.
 * Se mantienen ambos shortcodes por compatibilidad con las páginas existentes.
 */
add_action( 'init', 'sie_retro_registrar_shortcode_soporte', 20 );

function sie_retro_registrar_shortcode_soporte() {
    if ( class_exists( 'SIE_Retro_Support' ) && is_callable( array( 'SIE_Retro_Support', 'init' ) ) ) {
        // La clase registra su shortcode propio durante init/plugins_loaded.
        return;
    }

    // El shortcode alternativo se registra aquí si la clase no lo ofrece.
    if ( shortcode_exists( 'sie_retro_support' ) && ! shortcode_exists( 'sie_retro_soporte' ) ) {
        add_shortcode( 'sie_retro_soporte', 'do_shortcode' );
    }
}

/**
 * Integración de la pasarela con el checkout moderno de WooCommerce Blocks.
 */
add_action( 'woocommerce_blocks_loaded', 'sie_retro_registrar_integracion_blocks' );

function sie_retro_registrar_integracion_blocks() {
    $integration_class = 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType';

    if ( ! class_exists( $integration_class ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-sie-retro-simulated-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        static function ( $registry ) {
            if ( class_exists( 'SIE_Retro_Simulated_Blocks_Integration' ) ) {
                $registry->register( new SIE_Retro_Simulated_Blocks_Integration() );
            }
        }
    );
}
