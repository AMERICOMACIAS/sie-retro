<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SIE_Retro_Support' ) ) {
    final class SIE_Retro_Support {

        /**
         * Registra los shortcodes y los manejadores del formulario.
         */
        public static function init() {
            add_shortcode( 'sie_retro_soporte', array( __CLASS__, 'render_form' ) );
            add_shortcode( 'sie_retro_support', array( __CLASS__, 'render_form' ) );

            add_action( 'admin_post_sie_retro_support', array( __CLASS__, 'handle_submission' ) );
            add_action( 'admin_post_nopriv_sie_retro_support', array( __CLASS__, 'handle_submission' ) );
        }

        /**
         * Muestra el formulario.
         *
         * @return string
         */
        public static function render_form() {
            $status  = isset( $_GET['sie_retro_support_status'] )
                ? sanitize_key( wp_unslash( $_GET['sie_retro_support_status'] ) )
                : '';
            $message = '';

            if ( 'success' === $status ) {
                $message = '<div class="notice notice-success"><p>Solicitud de soporte registrada correctamente.</p></div>';
            } elseif ( 'error' === $status ) {
                $message = '<div class="notice notice-error"><p>No se pudo registrar la solicitud. Revisa los campos e inténtalo de nuevo.</p></div>';
            }

            ob_start();
            echo wp_kses_post( $message );
            ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="sie_retro_support">

                <p>
                    <label for="sie_retro_order_id">Número de pedido (opcional)</label><br>
                    <input type="number" id="sie_retro_order_id" name="order_id" min="1">
                </p>

                <p>
                    <label for="sie_retro_subject">Asunto</label><br>
                    <input type="text" id="sie_retro_subject" name="subject" maxlength="150" required>
                </p>

                <p>
                    <label for="sie_retro_message">Describe la incidencia</label><br>
                    <textarea id="sie_retro_message" name="message" rows="5" required></textarea>
                </p>

                <?php wp_nonce_field( 'sie_retro_support_submission', 'sie_retro_support_nonce' ); ?>

                <p>
                    <button type="submit">Enviar solicitud</button>
                </p>
            </form>
            <?php
            return ob_get_clean();
        }

        /**
         * Valida y guarda la solicitud.
         */
        public static function handle_submission() {
            if (
                ! isset( $_POST['sie_retro_support_nonce'] ) ||
                ! wp_verify_nonce(
                    sanitize_text_field( wp_unslash( $_POST['sie_retro_support_nonce'] ) ),
                    'sie_retro_support_submission'
                )
            ) {
                self::redirect_with_status( 'error' );
            }

            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            $subject  = isset( $_POST['subject'] )
                ? sanitize_text_field( wp_unslash( $_POST['subject'] ) )
                : '';
            $message  = isset( $_POST['message'] )
                ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )
                : '';

            if ( '' === $subject || '' === $message ) {
                self::redirect_with_status( 'error' );
            }

            if ( $order_id && function_exists( 'wc_get_order' ) && ! wc_get_order( $order_id ) ) {
                self::redirect_with_status( 'error' );
            }

            if ( ! class_exists( 'SIE_Retro_Events' ) || ! is_callable( array( 'SIE_Retro_Events', 'record' ) ) ) {
                self::redirect_with_status( 'error' );
            }

            $event_data = array(
                'subject' => $subject,
                'message' => $message,
            );

            $recorded = SIE_Retro_Events::record(
                'support.requested',
                array_merge(
                    $event_data,
                    $order_id ? array( 'order_id' => $order_id ) : array()
                )
            );

            if ( false === $recorded ) {
                self::redirect_with_status( 'error' );
            }

            self::redirect_with_status( 'success' );
        }

        /**
         * Redirige a la página que contiene el formulario, mostrando el resultado.
         *
         * @param string $status success|error
         */
        private static function redirect_with_status( $status ) {
            $referer = wp_get_referer();
            $target  = $referer ? $referer : home_url( '/' );
            $target  = remove_query_arg( 'sie_retro_support_status', $target );
            $target  = add_query_arg( 'sie_retro_support_status', sanitize_key( $status ), $target );

            wp_safe_redirect( $target );
            exit;
        }
    }
}
