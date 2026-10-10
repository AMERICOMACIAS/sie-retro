
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
}

class WC_Gateway_SIE_Retro_Simulated extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'sie_retro_simulated';
        $this->method_title       = 'Pago simulado ESRETRO';
        $this->method_description = 'Método de pago ficticio para pruebas académicas.';
        $this->has_fields         = false;
        $this->supports           = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option( 'enabled', 'no' );
        $this->title = $this->get_option(
            'title',
            'Pago simulado ESRETRO'
        );

        $this->description = $this->get_option(
            'description',
            'No se realizará ningún cobro real.'
        );

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array( $this, 'process_admin_options' )
        );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Activar o desactivar',
                'type'    => 'checkbox',
                'label'   => 'Activar pago simulado ESRETRO',
                'default' => 'no',
            ),
            'title' => array(
                'title'   => 'Título',
                'type'    => 'text',
                'default' => 'Pago simulado ESRETRO',
            ),
            'description' => array(
                'title'   => 'Descripción',
                'type'    => 'textarea',
                'default' => 'Pago ficticio para el prototipo académico. No se realizará ningún cobro real.',
            ),
        );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wc_add_notice(
                'No se ha podido recuperar el pedido.',
                'error'
            );

            return array( 'result' => 'failure' );
        }

        // Este método solo simula el resultado de un pago.
        $order->payment_complete();

        $order->add_order_note(
            'Pago simulado ESRETRO aceptado. No se ha realizado ningún cobro real.'
        );

        do_action(
            'sie_retro_payment_simulated',
            $order->get_id(),
            'accepted'
        );

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }
}
