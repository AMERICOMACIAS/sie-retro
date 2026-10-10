
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* Comprobación
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class SIE_Retro_Simulated_Blocks_Integration
    extends AbstractPaymentMethodType {
*/
class SIE_Retro_Simulated_Blocks_Integration
    extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
    protected $name = 'sie_retro_simulated';

    public function initialize() {
        $this->settings = get_option(
            'woocommerce_' . $this->name . '_settings',
            array()
        );
    }

    public function is_active() {
        return isset( $this->settings['enabled'] )
            && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'sie-retro-payment-method',
            plugins_url( '../assets/payment-method.js', __FILE__ ),
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
            ),
            '1.0.0',
            true
        );

        return array( 'sie-retro-payment-method' );
    }

    public function get_payment_method_data() {
        return array(
            'title'       => $this->get_setting(
                'title',
                'Pago simulado ESRETRO'
            ),
            'description' => $this->get_setting(
                'description',
                'No se realizará ningún cobro real.'
            ),
            'supports'    => array( 'products' ),
        );
    }
}
