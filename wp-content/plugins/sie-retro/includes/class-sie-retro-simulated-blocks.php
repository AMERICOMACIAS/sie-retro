<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! class_exists( 'SIE_Retro_Simulated_Blocks_Integration' ) && class_exists( AbstractPaymentMethodType::class ) ) {
    final class SIE_Retro_Simulated_Blocks_Integration extends AbstractPaymentMethodType {

        /**
         * Identificador interno de la pasarela. Debe coincidir con el gateway PHP y JS.
         *
         * @var string
         */
        protected $name = 'sie_retro_simulated';

        public function initialize() {
            $this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
        }

        public function is_active() {
            return 'yes' === $this->get_setting( 'enabled', 'yes' );
        }

        public function get_payment_method_script_handles() {
            $script_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/payment-method.js';
            $script_url  = plugins_url( '../assets/payment-method.js', __FILE__ );

            if ( ! file_exists( $script_path ) ) {
                return array();
            }

            wp_register_script(
                'sie-retro-simulated-payment-method',
                $script_url,
                array(
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                ),
                filemtime( $script_path ),
                true
            );

            return array( 'sie-retro-simulated-payment-method' );
        }

        public function get_payment_method_data() {
            return array(
                'title'       => $this->get_setting( 'title', 'Tarjeta simulada' ),
                'description' => $this->get_setting(
                    'description',
                    'Pago simulado para el prototipo académico. No se realiza ningún cargo.'
                ),
                'supports'    => array( 'products' ),
            );
        }
    }
}
