<?php
/**
 * Registro centralizado de eventos de ESRETRO.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SIE_Retro_Events' ) ) {

    final class SIE_Retro_Events {

        /**
         * Nombre de la tabla de eventos.
         */
        public static function table_name() {
            global $wpdb;

            return $wpdb->prefix . 'sie_retro_events';
        }

        /**
         * Crea o actualiza la tabla sin borrar los registros existentes.
         */
        public static function install() {
            global $wpdb;

            $table           = self::table_name();
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
         * Tipos de evento permitidos.
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
         * Registra un evento.
         *
         * @param string $event_type Tipo de evento.
         * @param array  $data       Datos adicionales.
         * @return int|false ID insertado o false si falla.
         */
        public static function record( $event_type, $data = array() ) {
            global $wpdb;

            if (
                ! is_string( $event_type )
                || ! in_array( $event_type, self::allowed_event_types(), true )
            ) {
                return false;
            }

            if ( ! is_array( $data ) ) {
                return false;
            }

            $product_id = isset( $data['product_id'] )
                ? absint( $data['product_id'] )
                : null;

            $order_id = isset( $data['order_id'] )
                ? absint( $data['order_id'] )
                : null;

            // Los identificadores se almacenan en sus propias columnas.
            unset( $data['product_id'], $data['order_id'] );

            $encoded_data = empty( $data )
                ? null
                : wp_json_encode( $data );

            if ( false === $encoded_data ) {
                return false;
            }

            $row = array(
                'event_type'  => $event_type,
                'occurred_at' => current_time( 'mysql' ),
                'product_id'  => $product_id ?: null,
                'order_id'    => $order_id ?: null,
                'event_data'  => $encoded_data,
            );

            $formats = array(
                '%s',
                '%s',
                null !== $row['product_id'] ? '%d' : null,
                null !== $row['order_id'] ? '%d' : null,
                null !== $row['event_data'] ? '%s' : null,
            );

            $inserted = $wpdb->insert(
                self::table_name(),
                $row,
                $formats
            );

            if ( false === $inserted ) {
                return false;
            }

            return (int) $wpdb->insert_id;
        }
    }
}