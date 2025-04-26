<?php
/*
Plugin Name: Waitlist For Tutor LMS
Plugin URI: https://cxrana.wordpress.com/2025/02/22/waitlist-for-tutor-lms/
Description: Adds waitlist functionality to Tutor LMS courses, enabling admins to notify waitlisted students when a spot becomes available.
Version: 1.0
Author: Anowar Hossain Rana
Author URI: https://cxrana.wordpress.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: waitlist-for-tutor-lms
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'WFTL_VERSION', '1.0' );
define( 'WFTL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WFTL_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once WFTL_PATH . 'includes/class-waitlist-handler.php';
require_once WFTL_PATH . 'includes/class-admin-interface.php';

// Initialize the plugin
function wftl_init() {
    if ( ! function_exists( 'tutor' ) ) {
        return;
    }
    
    $waitlist_handler = new WFTL_Waitlist_Handler();
    $admin_interface = new WFTL_Admin_Interface();
}
add_action( 'plugins_loaded', 'wftl_init' );

// Activation hook
function wftl_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tutor_waitlist';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        course_id bigint(20) NOT NULL,
        user_email varchar(255) NOT NULL,
        date_added datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'waiting',
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'wftl_activate' );