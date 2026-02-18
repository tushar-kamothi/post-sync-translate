<?php
/**
 * Plugin Name: Post Sync Translate
 * Description: Sync posts from Host to Target with ChatGPT translation.
 * Version: 1.0.0
 * Author: Tushar Kamothi
 */

if (!defined('ABSPATH')) exit;

define('PST_VERSION', '1.0.0');
define('PST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PST_LOG_TABLE', $GLOBALS['wpdb']->prefix . 'pst_logs');

require_once PST_PLUGIN_DIR . 'includes/class-settings.php';
require_once PST_PLUGIN_DIR . 'includes/class-auth.php';
require_once PST_PLUGIN_DIR . 'includes/class-logger.php';
require_once PST_PLUGIN_DIR . 'includes/class-rest.php';
require_once PST_PLUGIN_DIR . 'includes/class-host.php';
require_once PST_PLUGIN_DIR . 'includes/class-target.php';
require_once PST_PLUGIN_DIR . 'includes/class-translate.php';

register_activation_hook(__FILE__, ['PST_Logger', 'create_table']);

add_action('plugins_loaded', function(){

    PST_Settings::init();
    PST_REST::init();

    if (get_option('pst_mode') === 'host') {
        PST_Host::init();
    }

});
