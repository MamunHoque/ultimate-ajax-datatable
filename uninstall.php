<?php
/**
 * Uninstall Ultimate Ajax DataTable
 *
 * @package UltimateAjaxDataTable
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options_to_delete = [
    'uadt_activated',
    'uadt_version',
    'uadt_enabled_post_types',
    'uadt_items_per_page',
    'uadt_enable_search',
    'uadt_enable_filters',
    'uadt_enable_bulk_actions',
    'uadt_cache_duration',
    'uadt_max_items_per_page',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Delete plugin tables
global $wpdb;

$table_name = $wpdb->prefix . 'uadt_filter_presets';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Clear any cached data
wp_cache_flush();

// Clear scheduled events
wp_clear_scheduled_hook('uadt_cleanup_cache');
