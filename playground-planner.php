<?php
/**
 * Plugin Name: Playground Planner
 * Description: Interactive playground product browser and concept planner. Uses existing WordPress products as the source of truth.
 * Version: 0.1.0
 * Author: Your Company
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PP_VERSION', '0.1.0' );
define( 'PP_PATH', plugin_dir_path( __FILE__ ) );
define( 'PP_URL', plugin_dir_url( __FILE__ ) );

require_once PP_PATH . 'includes/class-playground-planner.php';

function pp_playground_planner() {
    return Playground_Planner::instance();
}

pp_playground_planner();
