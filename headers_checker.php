<?php

/**
 * Plugin Name: Headers Checker
 * Description: Test project.
 * Version: 1.0
 * Author: André Abdalla
 * Text Domain: headers-checker
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//define the plugin file
define('HEADERS_CHECKER_FILE', __FILE__);


//add the class
require_once plugin_dir_path(__FILE__) . 'HeadersChecker.php';


//instanciate the class
$headersChecker = new HeadersChecker();

//activation hook
register_activation_hook(__FILE__, array($headersChecker, 'headers_checker_activate'));
register_deactivation_hook(__FILE__, array($headersChecker, 'headers_checker_deactivate'));

//init the plugin
$headersChecker->init();
