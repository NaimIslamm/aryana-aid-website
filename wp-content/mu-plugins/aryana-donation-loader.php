<?php
/**
 * Plugin Name: Aryana Donation System - Permanent Loader & Auto-Reactivator
 * Description: Ensures Aryana Donation System remains permanently active across backups, migrations, and restores.
 * Version: 1.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Locate the main plugin file across standard plugin and mu-plugin directories
 */
function ads_find_plugin_entry() {
    $candidates = [
        WP_PLUGIN_DIR . '/aryana-donation-system/aryana-donation-system.php',
        WPMU_PLUGIN_DIR . '/aryana-donation-system/aryana-donation-system.php',
    ];

    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            return $candidate;
        }
    }
    return false;
}

// 2. Early load if not already loaded by WordPress core
$ads_entry_file = ads_find_plugin_entry();
if ($ads_entry_file && !defined('ADS_VERSION')) {
    require_once $ads_entry_file;
}

/**
 * 3. Auto-reactivate in database if WordPress deactivated it during a restore
 */
add_action('init', function () {
    $plugins = [
        'woocommerce/woocommerce.php',
        'woo-stripe-payment/woo-stripe-payment.php',
        'woocommerce-paypal-payments/woocommerce-paypal-payments.php',
        'aryana-donation-system/aryana-donation-system.php',
    ];

    $active_plugins = (array) get_option('active_plugins', []);
    $updated = false;

    foreach ($plugins as $plugin_slug) {
        $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
        if (file_exists($plugin_file) && !in_array($plugin_slug, $active_plugins, true)) {
            $active_plugins[] = $plugin_slug;
            $updated = true;
        }
    }

    if ($updated) {
        sort($active_plugins);
        update_option('active_plugins', $active_plugins);
    }
}, 1);
