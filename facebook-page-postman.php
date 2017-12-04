<?php
/**
* @package FacebookPagePostman
*/

/*
Plugin Name: Facebook Page Postman
Plugin URI: http://senk.eu
Description: Plugin for automatically getting posts from a Facebook page and publishing them to a Wordpress site.
Version: 0.1
Author: Simon Shenk
Author URI: http://senk.eu
License: GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fbpp-textd
*/

/*
Facebook Page Postman is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Facebook Page Postman is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Facebook Page Postman. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if (!defined('ABSPATH')) {
    die;
}

// Display notice if Init class is defined before it gets required (eg. namespace already in use)
function fbpp_error_notice() {
    echo    '<div class="error notice is-dismissible"><p>Facebook Page Postman: ' . 
            __('Plugin namespace/class might conflict with other plugin or theme.', 'fbpp-textd') . '</p></div>';
}
if (class_exists('FBPPostman\\Init')) {
    add_action('admin_notices', 'fbpp_error_notice');
}

// Define global plugin constants
define('FBPP__PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FBPP__PLUGIN_URL', plugin_dir_url(__FILE__));
define('FBPP__PLUGIN_NAME', plugin_basename(__FILE__));
define('FBPP__GRAPH_VERSION', 'v2.10');

// Require ./inc once with Composer Autoload
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Add wp-cron schedule interval
function fbpp_add_cron_intervals() {
    if (!isset($schedules['fbpp_30min'])) {
        $schedules['fbpp_30min'] = array(
            'interval' => 1800,
            'display' => __('Every 30 Minutes')
        );
    }
    return $schedules;
}
add_filter('cron_schedules', 'fbpp_add_cron_intervals');

// Include code for activation and deactivation
function fbpp_activate_plugin() {
    FBPPostman\Base\Activate::activate();
}
register_activation_hook(__FILE__, 'fbpp_activate_plugin');

function fbpp_deactivate_plugin() {
    FBPPostman\Base\Deactivate::deactivate();
}
register_deactivation_hook(__FILE__, 'fbpp_deactivate_plugin');

// Initialize core classes of the plugin
if (class_exists('FBPPostman\\Init')) {
    FBPPostman\Init::register_services();
}
