
<?php
/**
 * Formulario de soporte de ESRETRO.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SIE_Retro_Support {

    public static function init() {
        add_shortcode(
            'sie_retro_support',
            array( __CLASS__, 'render_form' )
        );

        add_action(
            'admin_post_nopriv_sie_retro_support',
            array( __CLASS__, 'handle_submission' )
        );

        add_action(
            'admin_post_sie_retro_support',
            array( __CLASS__, 'handle_submission' )
        );
    }

    /**
     * Mostrar el formulario mediante un shortcode.
     */
    public static function render_form() {
        $status = isset( $_GET['support_status'] )
            ? sanitize_key( wp_unslash( $_GET['support_status'] ) )
            : '';

        ob_start();
        ?>
        <div class="sie-retro-support">
            <h2>Contactar con soporte</h2>

            <?php if ( 'success' === $status ) : ?>
                <p role="status">
                    Tu consulta se ha registrado correctamente.
                </p>
            <?php elseif ( 'error' === $status ) : ?>
                <p role="alert">
                    No se ha podido registrar la consulta.
                    Revisa los campos e inténtalo de nuevo.
                </p>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="sie_retro_support"
                >

                <?php
                wp_nonce_field(
                    'sie_retro_support',
                    'sie_retro_support_nonce'
                );
                ?>

                <p>
                    <label for="sie-support-subject">
                        Asunto
                    </label>
                    <input
                        id="sie-support-subject"
                        type="text"
                        name="subject"
                        maxlength="120"
                        required
                    >
                </p>

                <p>
                    <label for="sie-support-message">
                        Consulta
                    </label>
                    <textarea
                        id="sie-support-message"
                        name="message"
                        maxlength="2000"
                        rows="5"
                        required
                    ></textarea>
                </p>

                <button type="submit">
                    Enviar consulta
                </button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Validar la solicitud y registrar el evento.
     */
    public static function handle_submission() {
        if (
            ! isset( $_POST['sie_retro_support_nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash( $_POST['sie_retro_support_nonce'] )
                ),
                'sie_retro_support'
            )
        ) {
            wp_die(
                'No se ha podido validar la solicitud.',
                'Solicitud no válida',
                array( 'response' => 403 )
            );
        }

        $subject = isset( $_POST['subject'] )
            ? sanitize_text_field( wp_unslash( $_POST['subject'] ) )
            : '';

        $message = isset( $_POST['message'] )
            ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) )
            : '';

        if (
            '' === trim( $subject )
            || '' === trim( $message )
            || strlen( $subject ) > 120
            || strlen( $message ) > 2000
        ) {
            self::redirect_with_status( 'error' );
        }

        $event_saved = SIE_Retro_Events::record(
            'support.requested',
            array(
                'subject' => $subject,
                'message' => $message,
            )
        );

        self::redirect_with_status(
            $event_saved ? 'success' : 'error'
        );
    }

    private static function redirect_with_status( $status ) {
        $referer = wp_get_referer();

        if ( ! $referer ) {
            $referer = home_url( '/' );
        }

        $url = add_query_arg(
            'support_status',
            $status,
            $referer
        );

        wp_safe_redirect( $url );
        exit;
    }
}
