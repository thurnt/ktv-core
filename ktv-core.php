<?php
/**
 * Plugin Name: KTV Core
 * Plugin URI: #
 * Description: Core functionality plugin for KTV system
 * Version: 1.0.2
 * Author: KTV Team
 * Author URI: #
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ktv-core
 * Domain Path: /languages
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KTV_CORE_VERSION', '1.0.0');
define('KTV_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KTV_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin activation hook
register_activation_hook(__FILE__, function() {
    require_once KTV_CORE_PLUGIN_DIR . 'includes/class-bluetag-token.php';
    require_once KTV_CORE_PLUGIN_DIR . 'includes/class-bluetag-roles.php';
    BlueTAG_Token::create_table();
    BlueTAG_Roles::setup_bluetag_user_role();
});

// Plugin deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Deactivation code here
});

// Initialize plugin
add_action('plugins_loaded', function() {
    // Load text domain for internationalization
    load_plugin_textdomain('ktv-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize BlueTAG functionality
    require_once KTV_CORE_PLUGIN_DIR . 'admin/class-bluetag-settings.php';
    require_once KTV_CORE_PLUGIN_DIR . 'includes/class-bluetag-token.php';
    BlueTAG_Token::init();
});