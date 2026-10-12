<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Gateway_SIE_Retro_Simulated' ) ) {
    class WC_Gateway_SIE_Retro_Simulated extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'sie_retro_simulated';
            $this->method_title       = 'Tarjeta simulada ESRETRO';
            $this->method_description = 'Método de pago de prueba. No realiza cargos reales.';
            $this->has_fields         = false;
            $this->supports           = array( 'products' );

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option( 'title', 'Tarjeta simulada' );
            $this->description = $this->get_option( 'description', 'Pago simulado para el prototipo académico. No se realiza ningún cargo.' );
            $this->enabled     = $this->get_option( 'enabled', 'yes' );

            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array( $this, 'process_admin_options' )
            );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Activar/desactivar',
                    'type'    => 'checkbox',
                    'label'   => 'Activar pago simulado',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'       => 'Título',
                    'type'        => 'text',
                    'description' => 'Nombre que verá el cliente en el checkout.',
                    'default'     => 'Tarjeta simulada',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Descripción',
                    'type'        => 'textarea',
                    'description' => 'Aclara que no se realizará ningún cargo real.',
                    'default'     => 'Pago simulado para el prototipo académico. No se realiza ningún cargo.',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Procesa el pedido como pago de prueba aceptado.
         *
         * @param int $order_id ID del pedido.
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                wc_add_notice( 'No se ha podido localizar el pedido para el pago simulado.', 'error' );
                return array( 'result' => 'failure' );
            }

            // Evitar volver a emitir el evento si el método se ejecuta otra vez para el mismo pedido.
            $already_simulated = 'yes' === $order->get_meta( '_sie_retro_simulated_payment_recorded' );

            $order->payment_complete();

            $order->add_order_note(
                'Pago simulado aceptado. No se ha realizado ningún cargo real.'
            );

            $order->update_meta_data( '_sie_retro_simulated_payment_recorded', 'yes' );
            $order->save();

            if ( ! $already_simulated ) {
                /**
                 * El archivo principal escucha este hook y registra payment.simulated.
                 */
                do_action( 'sie_retro_payment_simulated', $order->get_id(), 'accepted' );
            }

            // Vaciar el carrito y redirigir a la página de confirmación.
            if ( WC()->cart ) {
                WC()->cart->empty_cart();
            }

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }
    }
}
