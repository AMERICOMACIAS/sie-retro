
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

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once __DIR__ .
        '/includes/class-sie-retro-simulated-gateway.php';

    add_filter(
        'woocommerce_payment_gateways',
        function ( $gateways ) {
            $gateways[] = 'WC_Gateway_SIE_Retro_Simulated';

            return $gateways;
        }
    );
}, 20 );

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
