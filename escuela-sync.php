<?php
/*
Plugin Name: Escuela Store Sync
Description: Sync products from Escuela API.
Version: 1.0
Author: Your Name
*/

// Include necessary files
include_once(plugin_dir_path(__FILE__) . 'includes/escuela-functions.php');
include_once(plugin_dir_path(__FILE__) . 'includes/escuela-admin-menu.php');

// Register hooks for activation and deactivation
register_activation_hook(__FILE__, 'escuela_store_sync_activate');
register_deactivation_hook(__FILE__, 'escuela_store_sync_deactivate');

// Initialize the plugin
add_action('init', 'escuela_store_sync_init');

// Enqueue scripts
//add_action('admin_enqueue_scripts', 'escuela_store_sync_enqueue_scripts');

// Add AJAX handlers
//add_action('wp_ajax_escuela_store_sync', 'escuela_store_sync_ajax_handler');
