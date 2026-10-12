<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SIE_Retro_Events' ) ) {
    final class SIE_Retro_Events {

        /**
         * Nombre completo de la tabla, con el prefijo configurado en WordPress.
         *
         * @return string
         */
        public static function table_name() {
            global $wpdb;
            return $wpdb->prefix . 'sie_retro_events';
        }

        /**
         * Crea o actualiza la tabla de eventos.
         *
         * No elimina la tabla existente ni los datos guardados.
         *
         * @return void
         */
        public static function install() {
            global $wpdb;

            $table          = self::table_name();
            $charset_collate = $wpdb->get_charset_collate();

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $sql = "CREATE TABLE {$table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                event_type VARCHAR(100) NOT NULL,
                occurred_at DATETIME NOT NULL,
                product_id BIGINT UNSIGNED NULL,
                order_id BIGINT UNSIGNED NULL,
                event_data LONGTEXT NULL,
                PRIMARY KEY  (id),
                KEY event_type (event_type),
                KEY occurred_at (occurred_at),
                KEY product_id (product_id),
                KEY order_id (order_id)
            ) {$charset_collate};";

            dbDelta( $sql );
        }

        /**
         * Eventos que el prototipo reconoce.
         *
         * @return array
         */
        public static function allowed_event_types() {
            return array(
                'product.viewed',
                'cart.item_added',
                'checkout.started',
                'order.created',
                'payment.simulated',
                'support.requested',
                'incident.created',
            );
        }

        /**
         * Guarda un evento en la tabla.
         *
         * La firma admite la llamada utilizada en sie-retro.php:
         * SIE_Retro_Events::record( $event_type, $data ).
         *
         * @param string $event_type Tipo de evento.
         * @param array  $data       Datos adicionales del evento.
         * @return int|false ID insertado o false si falla la validación/inserción.
         */
        public static function record( $event_type, $data = array() ) {
            global $wpdb;

            if ( ! is_string( $event_type ) || ! in_array( $event_type, self::allowed_event_types(), true ) ) {
                return false;
            }

            if ( ! is_array( $data ) ) {
                $data = array();
            }

            $product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : null;
            $order_id   = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : null;

            // Evitar que los IDs se guarden dos veces dentro del JSON.
            unset( $data['product_id'], $data['order_id'] );

            $encoded_data = empty( $data ) ? null : wp_json_encode( $data );

            $inserted = $wpdb->insert(
                self::table_name(),
                array(
                    'event_type'  => $event_type,
                    'occurred_at' => current_time( 'mysql' ),
                    'product_id'  => $product_id ?: null,
                    'order_id'    => $order_id ?: null,
                    'event_data'  => $encoded_data,
                ),
                array( '%s', '%s', '%d', '%d', '%s' )
            );

            if ( false === $inserted ) {
                return false;
            }

            return (int) $wpdb->insert_id;
        }
    }
}
