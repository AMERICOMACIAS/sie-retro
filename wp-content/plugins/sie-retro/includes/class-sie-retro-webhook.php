
<?php
/**
 * Envío de eventos ESRETRO a n8n.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SIE_Retro_Webhook' ) ) {

    final class SIE_Retro_Webhook {

        const CRON_HOOK = 'sie_retro_send_webhook_event';

        const MAX_ATTEMPTS = 5;

        /**
         * Registra el manejador de WP-Cron.
         */
        public static function init() {
            add_action(
                self::CRON_HOOK,
                array( __CLASS__, 'send_event' ),
                10,
                2
            );
        }

        /**
         * Programa el envío de un evento.
         *
         * @param int $event_id Identificador del evento en MariaDB.
         */
        
/**
 * Programa el envío de un evento.
 *
 * @param int $event_id Identificador del evento en MariaDB.
 */
public static function schedule_event( $event_id ) {
    $event_id = absint( $event_id );

    if ( ! $event_id ) {
        return false;
    }

    $args = array( $event_id, 1 );

    // Evitar programar dos veces el mismo primer intento.
    if ( wp_next_scheduled( self::CRON_HOOK, $args ) ) {
        return true;
    }

    $scheduled = wp_schedule_single_event(
        time() + 5,
        self::CRON_HOOK,
        $args
    );

    if ( is_wp_error( $scheduled ) || false === $scheduled ) {
        self::log(
            'No se pudo programar el envío mediante WP-Cron.',
            $event_id,
            1
        );

        return false;
    }

    return true;
}


        /**
         * Envía un evento a n8n.
         *
         * @param int $event_id Identificador del evento.
         * @param int $attempt  Número de intento.
         */
        public static function send_event( $event_id, $attempt = 1 ) {
            global $wpdb;

            $event_id = absint( $event_id );
            $attempt  = absint( $attempt );

            if ( ! $event_id || ! $attempt ) {
                return false;
            }

            // La configuración se define fuera del repositorio.
            if (
                ! defined( 'SIE_RETRO_N8N_WEBHOOK_URL' )
                || ! defined( 'SIE_RETRO_N8N_TOKEN' )
            ) {
                self::log(
                    'Falta configurar la URL o el token de n8n.',
                    $event_id,
                    $attempt
                );

                return false;
            }

            $url   = SIE_RETRO_N8N_WEBHOOK_URL;
            $token = SIE_RETRO_N8N_TOKEN;

            // Solo permitimos conexiones HTTPS.
            if (
                ! is_string( $url )
                || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME )
                || ! is_string( $token )
                || '' === trim( $token )
            ) {
                self::log(
                    'La configuración de n8n no es válida.',
                    $event_id,
                    $attempt
                );

                return false;
            }

            // Recuperar el evento original de MariaDB.
            $tabla = SIE_Retro_Events::table_name();

            $evento = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$tabla} WHERE id = %d",
                    $event_id
                )
            );

            if ( ! $evento ) {
                self::log(
                    'No se ha encontrado el evento en MariaDB.',
                    $event_id,
                    $attempt
                );

                return false;
            }

            $datos = array();

            if ( ! empty( $evento->event_data ) ) {
                $datos = json_decode( $evento->event_data, true );

                if ( ! is_array( $datos ) ) {
                    self::log(
                        'Los datos del evento no contienen JSON válido.',
                        $event_id,
                        $attempt
                    );

                    return false;
                }
            }

            // Convertir la fecha local de WordPress a ISO 8601.
            try {
                $fecha = new DateTimeImmutable(
                    $evento->occurred_at,
                    wp_timezone()
                );

                $occurred_at = $fecha->format( DATE_ATOM );

            } catch ( Exception $e ) {
                self::log(
                    'No se ha podido convertir la fecha del evento.',
                    $event_id,
                    $attempt
                );

                return false;
            }

            // Contrato JSON v1.0.
            $payload = array(
                'schema_version' => '1.0',
                'event_id'       => (int) $evento->id,
                'event_type'     => $evento->event_type,
                'occurred_at'    => $occurred_at,
                'order_id'       => null !== $evento->order_id
                    ? (int) $evento->order_id
                    : null,
                'product_id'     => null !== $evento->product_id
                    ? (int) $evento->product_id
                    : null,
                'data'           => (object) $datos,
            );

            $body = wp_json_encode( $payload );

            if ( false === $body ) {
                self::log(
                    'No se ha podido generar el JSON del evento.',
                    $event_id,
                    $attempt
                );

                return false;
            }

            // Envío HTTP a n8n.
            $response = wp_remote_post(
                $url,
                array(
                    'method'      => 'POST',
                    'timeout'     => 15,
                    'redirection' => 0,
                    'blocking'    => true,
                    'headers'     => array(
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'X-ESRETRO-Token' => $token,
                    ),
                    'body'        => $body,
                    'data_format' => 'body',
                )
            );

            if ( is_wp_error( $response ) ) {
                self::log(
                    'Error HTTP: ' . $response->get_error_code(),
                    $event_id,
                    $attempt
                );

                self::schedule_retry( $event_id, $attempt );

                return false;
            }

            $status = wp_remote_retrieve_response_code( $response );

            // Cualquier respuesta 2xx confirma la recepción.
            if ( $status >= 200 && $status < 300 ) {
                self::log(
                    'Evento enviado correctamente. HTTP ' . $status,
                    $event_id,
                    $attempt
                );

                return true;
            }

            self::log(
                'Respuesta HTTP no satisfactoria: ' . $status,
                $event_id,
                $attempt
            );

            // Reintentar errores temporales, no errores de validación.
            if (
                408 === $status
                || 429 === $status
                || $status >= 500
            ) {
                self::schedule_retry( $event_id, $attempt );
            }

            return false;
        }

        /**
         * Programa un nuevo intento con espera progresiva.
         */
        private static function schedule_retry( $event_id, $attempt ) {
            if ( $attempt >= self::MAX_ATTEMPTS ) {
                self::log(
                    'Se ha alcanzado el máximo de intentos.',
                    $event_id,
                    $attempt
                );

                return false;
            }

            $delays = array(
                60,
                300,
                900,
                1800,
            );

            $delay = $delays[ $attempt - 1 ];

            $next_attempt = $attempt + 1;
            $args = array( $event_id, $next_attempt );

            if ( ! wp_next_scheduled( self::CRON_HOOK, $args ) ) {
                wp_schedule_single_event(
                    time() + $delay,
                    self::CRON_HOOK,
                    $args
                );
            }

            return true;
        }

        /**
         * Registra información técnica sin revelar el token.
         */
        private static function log( $message, $event_id, $attempt ) {
            if ( function_exists( 'wc_get_logger' ) ) {
                wc_get_logger()->info(
                    $message,
                    array(
                        'source'   => 'sie-retro-webhook',
                        'event_id' => $event_id,
                        'attempt'  => $attempt,
                    )
                );
            }
        }
    }
}
