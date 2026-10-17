<?php
/**
 * Plugin Name: ESRETRO
 * Description: Funcionalidades propias del prototipo académico ESRETRO.
 * Version: 2.0.0
 * Author: Javier, Miguel y Américo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga los componentes propios cuando WordPress inicia los plugins.
 */
add_action( 'plugins_loaded', 'sie_retro_inicializar_plugin', 20 );

function sie_retro_inicializar_plugin() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-sie-retro-events.php';
    require_once __DIR__ . '/includes/class-sie-retro-simulated-gateway.php';
    require_once __DIR__ . '/includes/class-sie-retro-support.php';

    // Registro de pedidos en el checkout por bloques.
    add_action(
        'woocommerce_store_api_checkout_order_processed',
        'sie_retro_evento_pedido_creado',
        10,
        1
    );
    // Eventos de navegación y compra.
    add_action( 'wp', 'sie_retro_registrar_evento_checkout' );
    add_action( 'wp', 'sie_retro_registrar_evento_producto' );
    add_action(
        'woocommerce_add_to_cart',
        'sie_retro_registrar_evento_carrito',
        10,
        6
    );

    add_filter( 'woocommerce_payment_gateways', 'sie_retro_registrar_pasarela' );

    if (
        class_exists( 'SIE_Retro_Support' )
        && is_callable( array( 'SIE_Retro_Support', 'init' ) )
    ) {
        SIE_Retro_Support::init();
    }
}

/**
 * Crea la tabla al activar el plugin.
 */
register_activation_hook( __FILE__, 'sie_retro_activar_plugin' );

function sie_retro_activar_plugin() {
    require_once __DIR__ . '/includes/class-sie-retro-events.php';

    if ( is_callable( array( 'SIE_Retro_Events', 'install' ) ) ) {
        SIE_Retro_Events::install();
    }
}

/**
 * Registra la pasarela propia de WooCommerce.
 */
function sie_retro_registrar_pasarela( $gateways ) {
    $gateways[] = 'WC_Gateway_SIE_Retro_Simulated';

    return $gateways;
}

/**
 * Registra el evento order.created.
 */
function sie_retro_evento_pedido_creado( $order ) {
    if ( ! $order instanceof WC_Order ) {
        return;
    }

    sie_retro_guardar_evento(
        'order.created',
        array(
            'total'  => $order->get_total(),
            'moneda' => $order->get_currency(),
            'estado' => $order->get_status(),
            'origen' => current_filter(),
        ),
        $order->get_id()
    );
}

/**
 * Registra payment.simulated cuando la pasarela emite su hook.
 */
add_action(
    'sie_retro_payment_simulated',
    'sie_retro_evento_pago_simulado',
    10,
    2
);

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
 * Registra checkout.started.
 */
function sie_retro_registrar_evento_checkout() {
    if (
        ! function_exists( 'is_checkout' )
        || ! function_exists( 'is_order_received_page' )
        || ! function_exists( 'WC' )
        || ! WC()
        || ! WC()->session
    ) {
        return;
    }

    $sesion = WC()->session;

    if ( is_checkout() && ! is_order_received_page() ) {
        // Evita registrar varias veces la misma visita al checkout.
        if ( $sesion->get( 'sie_retro_checkout_started_recorded' ) ) {
            return;
        }

        $sesion->set( 'sie_retro_checkout_started_recorded', true );

        sie_retro_guardar_evento( 'checkout.started', array() );

        return;
    }

    // Al salir del checkout, permite registrar una visita posterior.
    $sesion->__unset( 'sie_retro_checkout_started_recorded' );
}

/**
 * Registra product.viewed.
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
 * Registra cart.item_added.
 */
function sie_retro_registrar_evento_carrito(
    $cart_item_key,
    $product_id,
    $quantity,
    $variation_id,
    $variation,
    $cart_item_data
) {

    $id_producto = $variation_id ? $variation_id : $product_id;

    sie_retro_guardar_evento(
        'cart.item_added',
        array(
            'parent_id'    => absint( $product_id ),
            'variation_id' => absint( $variation_id ),
            'quantity'     => absint( $quantity ),
        ),
        null,
        absint( $id_producto )
    );
}

/**
 * Centraliza las llamadas al registro de eventos.
 */
function sie_retro_guardar_evento(
    $tipo,
    $datos = array(),
    $order_id = null,
    $product_id = null
) {
    if (
        class_exists( 'SIE_Retro_Events' )
        && is_callable( array( 'SIE_Retro_Events', 'record' ) )
    ) {
        $datos_evento = is_array( $datos ) ? $datos : array();

        if ( null !== $order_id ) {
            $datos_evento['order_id'] = absint( $order_id );
        }

        if ( null !== $product_id ) {
            $datos_evento['product_id'] = absint( $product_id );
        }

        SIE_Retro_Events::record( $tipo, $datos_evento );
    }
}

/**
 * Añade el menú de administración de eventos.
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

/**
 * Muestra y filtra los eventos registrados.
 */
function sie_retro_pagina_eventos() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die(
            esc_html__(
                'No tienes permisos para ver estos eventos.',
                'sie-retro'
            )
        );
    }

    global $wpdb;

    $tabla = $wpdb->prefix . 'sie_retro_events';

    $filtro = isset( $_GET['tipo_evento'] )
        ? sanitize_text_field( wp_unslash( $_GET['tipo_evento'] ) )
        : '';

    $permitidos = SIE_Retro_Events::allowed_event_types();

    if ( $filtro && ! in_array( $filtro, $permitidos, true ) ) {
        $filtro = '';
    }

    if ( $filtro ) {
        $eventos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$tabla}
                WHERE event_type = %s
                ORDER BY occurred_at DESC
                LIMIT 500",
                $filtro
            )
        );
    } else {
        $eventos = $wpdb->get_results(
            "SELECT * FROM {$tabla}
            ORDER BY occurred_at DESC
            LIMIT 500"
        );
    }

    echo '<div class="wrap">';
    echo '<h1>Eventos ESRETRO</h1>';

    echo '<form method="get" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="page" value="sie-retro-eventos">';
    echo '<label for="tipo_evento"><strong>Filtrar por evento:</strong></label> ';
    echo '<select name="tipo_evento" id="tipo_evento">';
    echo '<option value="">Todos los eventos</option>';

    foreach ( $permitidos as $tipo ) {
        echo '<option value="' . esc_attr( $tipo ) . '"'
            . selected( $filtro, $tipo, false )
            . '>' . esc_html( $tipo ) . '</option>';
    }

    echo '</select> <button type="submit" class="button">Filtrar</button>';
    echo '</form>';

    if ( empty( $eventos ) ) {
        echo '<p>No hay eventos registrados todavía.</p></div>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>ID</th><th>Evento</th><th>Producto</th>';
    echo '<th>Pedido</th><th>Datos</th><th>Fecha</th>';
    echo '</tr></thead><tbody>';

    foreach ( $eventos as $evento ) {
        echo '<tr>';
        echo '<td>' . esc_html( $evento->id ) . '</td>';
        echo '<td>' . esc_html( $evento->event_type ) . '</td>';

        $producto_id = isset( $evento->product_id )
            ? absint( $evento->product_id )
            : 0;

        echo '<td>' . ( $producto_id ? esc_html( $producto_id ) : '' ) . '</td>';

        $order_id = isset( $evento->order_id )
            ? absint( $evento->order_id )
            : 0;

        if ( $order_id && function_exists( 'wc_get_order' ) ) {
            $pedido = wc_get_order( $order_id );

            if ( $pedido ) {
                echo '<td><a href="'
                    . esc_url( $pedido->get_edit_order_url() )
                    . '">' . esc_html( $order_id ) . '</a></td>';
            } else {
                echo '<td>' . esc_html( $order_id ) . '</td>';
            }
        } else {
            echo '<td>' . ( $order_id ? esc_html( $order_id ) : '' ) . '</td>';
        }

        echo '<td><code>'
            . esc_html( isset( $evento->event_data ) ? $evento->event_data : '' )
            . '</code></td>';

        echo '<td>'
            . esc_html( isset( $evento->occurred_at ) ? $evento->occurred_at : '' )
            . '</td>';

        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

/**
 * Registra la integración de la pasarela con WooCommerce Blocks.
 */
add_action(
    'woocommerce_blocks_loaded',
    'sie_retro_registrar_integracion_blocks'
);

function sie_retro_registrar_integracion_blocks() {
    $integration_class =
        'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType';

    if ( ! class_exists( $integration_class ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-sie-retro-simulated-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        static function ( $registry ) {
            if ( class_exists( 'SIE_Retro_Simulated_Blocks_Integration' ) ) {
                $registry->register(
                    new SIE_Retro_Simulated_Blocks_Integration()
                );
            }
        }
    );
}
