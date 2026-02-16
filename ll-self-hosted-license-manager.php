<?php
/**
 * Plugin Name: LL Self Hosted License Manager
 * Description: Self-hosted license management for WordPress plugins with validation and update delivery.
 * Version: 1.0.0
 * Author: Lievelingslinnen
 * Text Domain: ll-self-hosted-license-manager
 */

if (! defined('ABSPATH')) {
    exit;
}

define('LLSHLM_VERSION', '1.0.0');
define('LLSHLM_FILE', __FILE__);
define('LLSHLM_DIR', plugin_dir_path(__FILE__));
define('LLSHLM_URL', plugin_dir_url(__FILE__));

require_once LLSHLM_DIR . 'includes/class-roles.php';
require_once LLSHLM_DIR . 'includes/class-post-types.php';
require_once LLSHLM_DIR . 'includes/class-admin.php';
require_once LLSHLM_DIR . 'includes/class-rest.php';
require_once LLSHLM_DIR . 'includes/class-plugin.php';

\LLSHLM\Plugin::boot();
