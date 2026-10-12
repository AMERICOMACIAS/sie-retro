
<?php
/**
 * Plugin Name: ESRETRO - Funcionalidades propias
 * Description: Adaptaciones academicas para el proceso de compra de ESRETRO.
 * Version: 1.0.0
 * Author: Miguel
 * Text Domain: sie-retro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cargar las funcionalidades propias cuando WooCommerce esté disponible.
 */
add_action( 'plugins_loaded', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once __DIR__ .
        '/includes/class-sie-retro-events.php';

    require_once __DIR__ .
        '/includes/class-sie-retro-simulated-gateway.php';

    // Cargar e inicializar el formulario de soporte.
    require_once __DIR__ .
        '/includes/class-sie-retro-support.php';

    SIE_Retro_Support::init();

    /**
     * Registrar la creación de pedidos.
     * Compatible con checkout clásico y Checkout Blocks.
     */
    $order_created_callback = function ( $order ) {

        if ( ! $order instanceof WC_Order ) {
            return;
        }

        SIE_Retro_Events::record(
            'order.created',
            array(
                'order_id' => $order->get_id(),
                'source'   => current_filter(),
            )
        );
    };

    add_action(
        'woocommerce_checkout_order_created',
        $order_created_callback,
        10,
        1
    );

    add_action(
        'woocommerce_store_api_checkout_order_created',
        $order_created_callback,
        10,
        1
    );

    /**
     * Registrar la pasarela de pago simulada.
     */
    add_filter(
        'woocommerce_payment_gateways',
        function ( $gateways ) {

            $gateways[] = 'WC_Gateway_SIE_Retro_Simulated';

            return $gateways;
        }
    );

}, 20 );

/**
 * Registrar el inicio del checkout.
 */
add_action( 'wp', function () {

    if (
        ! function_exists( 'is_checkout' )
        || ! is_checkout()
        || is_order_received_page()
    ) {
        return;
    }

    SIE_Retro_Events::record(
        'checkout.started',
        array(
            'source' => 'woocommerce_checkout',
        )
    );
} );

/**
 * Registrar la consulta de un producto.
 */
add_action( 'wp', function () {

    if (
        ! function_exists( 'is_product' )
        || ! is_product()
    ) {
        return;
    }

    $product_id = get_queried_object_id();

    if ( ! $product_id ) {
        return;
    }

    SIE_Retro_Events::record(
        'product.viewed',
        array(
            'product_id' => $product_id,
        )
    );
} );

/**
 * Registrar artículos añadidos al carrito.
 */
add_action(
    'woocommerce_add_to_cart',
    function (
        $cart_item_key,
        $product_id,
        $quantity,
        $variation_id,
        $variation,
        $cart_item_data
    ) {

        SIE_Retro_Events::record(
            'cart.item_added',
            array(
                'product_id' => $variation_id
                    ? $variation_id
                    : $product_id,
                'parent_id'    => $product_id,
                'variation_id' => $variation_id,
                'quantity'     => $quantity,
            )
        );
    },
    10,
    6
);

/**
 * Integración del método de pago con WooCommerce Blocks.
 */
add_action( 'woocommerce_blocks_loaded', function () {

    $integration_class =
        'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType';

    if ( ! class_exists( $integration_class ) ) {
        return;
    }

    require_once __DIR__ .
        '/includes/class-sie-retro-simulated-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ( $registry ) {

            $registry->register(
                new SIE_Retro_Simulated_Blocks_Integration()
            );
        }
    );

} );

/**
 * Crear la tabla de eventos al activar el plugin.
 */
register_activation_hook(
    __FILE__,
    function () {

        require_once __DIR__ .
            '/includes/class-sie-retro-events.php';

        SIE_Retro_Events::install();
    }
);
