<?php
/**
 * Plugin Name: DHL Tracking
 * Description: Gurmehub DHL tracking plugin
 * Plugin URI: https://gurmehub.com/
 * Version: 1.3.0
 * Author: Gurmehub
 * Author URI: https://gurmehub.com/
 * Text Domain:
 * Requires at least: 5.7
 * Requires PHP: 7.0
 */

require_once 'vendor/autoload.php';

require_once __DIR__ . '/includes/class-dhl-twilio.php';
require_once __DIR__ . '/includes/class-dhl-track.php';
require_once __DIR__ . '/includes/class-dhl-status.php';


new DHL_Track();
new DHL_Status();
