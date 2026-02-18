<?php
/**
 * Plugin Name: Post Sync Translate
 * Description: Sync posts from Host to Target with ChatGPT translation.
 * Version: 1.0.0
 * Author: Tushar Kamothi
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PST_VERSION', '1.0.0');
define('PST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PST_PLUGIN_URL', plugin_dir_url(__FILE__));

class PST_Plugin {

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {

        require_once PST_PLUGIN_DIR . 'includes/class-settings.php';
        require_once PST_PLUGIN_DIR . 'includes/class-rest.php';
        require_once PST_PLUGIN_DIR . 'includes/class-host.php';
        require_once PST_PLUGIN_DIR . 'includes/class-target.php';
        require_once PST_PLUGIN_DIR . 'includes/class-auth.php';
        require_once PST_PLUGIN_DIR . 'includes/class-translate.php';
        require_once PST_PLUGIN_DIR . 'includes/class-logger.php';

        PST_Settings::init();
        PST_REST::init();
        PST_Host::init();
        PST_Target::init();
    }
}

new PST_Plugin();
