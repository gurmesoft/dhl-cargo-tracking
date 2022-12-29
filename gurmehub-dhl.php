<?php
/**
 * Plugin Name: Gurmehub DHL Tracking
 * Description: Gurmehub DHL tracking plugin
 * Plugin URI: https://gurmehub.com/
 * Version: 2.5.2
 * Author: Gurmehub
 * Author URI: https://gurmehub.com/
 * Text Domain:
 * Requires at least: 5.7
 * Requires PHP: 7.0
 */

define( 'GDHL_TRACKING_ID', 'gdhl_tracking_id' );
define( 'GDHL_SCHEDULE_DELAY_TIME', strtotime( '+4 Hours' ) );

require_once 'vendor/autoload.php';
require_once __DIR__ . '/includes/gdhl-functions.php';
require_once __DIR__ . '/includes/class-gdhl-schedule.php';
require_once __DIR__ . '/includes/class-gdhl-wordpress.php';
require_once __DIR__ . '/includes/class-gdhl-woocommerce.php';

register_activation_hook(
	__FILE__,
	function() {
		if ( ! wp_next_scheduled( 'gdhl_check_shipment_status_event' ) ) {
			wp_schedule_event( time(), 'dhl_every_four_hours', 'gdhl_check_shipment_status_event' );
		}
	}
);

new GDHL_Schedule();
new GDHL_WordPress();
new GDHL_WooCommerce();
