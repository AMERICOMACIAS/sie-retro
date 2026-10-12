
<?php
/**
 * Registro de eventos internos de ESRETRO.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SIE_Retro_Events {

    const TABLE_SUFFIX = 'sie_retro_events';

    /**
     * Nombre completo de la tabla.
     */
    public static function table_name() {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    /**
     * Crea la tabla durante la activación del plugin.
     */
    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(64) NOT NULL,
            occurred_at datetime NOT NULL,
            product_id bigint(20) unsigned DEFAULT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            event_data longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY order_id (order_id),
            KEY occurred_at (occurred_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Guarda un evento interno.
     */
    public static function record(
        $event_type,
        $data = array()
    ) {
        global $wpdb;

        $allowed_events = array(
            'product.viewed',
            'cart.item_added',
            'checkout.started',
            'order.created',
            'payment.simulated',
            'support.requested',
            'incident.created',
        );

        if ( ! in_array( $event_type, $allowed_events, true ) ) {
            return false;
        }

        $product_id = isset( $data['product_id'] )
            ? absint( $data['product_id'] )
            : null;

        $order_id = isset( $data['order_id'] )
            ? absint( $data['order_id'] )
            : null;

        $extra_data = $data;

        unset(
            $extra_data['product_id'],
            $extra_data['order_id']
        );

        $encoded_data = wp_json_encode( $extra_data );

        if ( false === $encoded_data ) {
            $encoded_data = '{}';
        }

        $result = $wpdb->insert(
            self::table_name(),
            array(
                'event_type'  => $event_type,
                'occurred_at' => current_time( 'mysql', true ),
                'product_id'  => $product_id,
                'order_id'    => $order_id,
                'event_data'  => $encoded_data,
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
            )
        );

        return false !== $result;
    }
}
