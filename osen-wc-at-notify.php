<?php
/*
Plugin Name: WooCommerce Notifications via Africa's Talking
Plugin URI: https://osen.co.ke
Description: Notify your customers when WooCommerce order status changes
Version: 0.20.40
Author: Osen Concepts
Author URI: https://osen.co.ke
License: GNU
*/

// initialize plugin

use Osen\Notify\Notifications\Alert;
use Osen\Notify\Settings\Admin;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

if (function_exists('add_action') && function_exists('register_activation_hook')) {
    add_action('plugins_loaded', function () {
        // Load admin settings
        new Admin;
    });

    add_action('init', function () {
        // Load our alert class
        new Alert;
    });
}
