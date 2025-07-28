<?php
/**
 * Compatibility tests for WordPress versions and popular plugins
 *
 * @package UltimateAjaxDataTable\Tests
 */

namespace UltimateAjaxDataTable\Tests\Integration;

use UltimateAjaxDataTable\Tests\TestCase;

/**
 * Compatibility test class
 */
class CompatibilityTest extends TestCase
{
    /**
     * Test WordPress version compatibility
     */
    public function test_wordpress_version_compatibility()
    {
        global $wp_version;
        
        // Plugin requires WordPress 5.8+
        $this->assertTrue(
            version_compare($wp_version, '5.8', '>='),
            'WordPress version must be 5.8 or higher'
        );
    }

    /**
     * Test PHP version compatibility
     */
    public function test_php_version_compatibility()
    {
        // Plugin requires PHP 7.4+
        $this->assertTrue(
            version_compare(PHP_VERSION, '7.4', '>='),
            'PHP version must be 7.4 or higher'
        );
    }

    /**
     * Test plugin activation
     */
    public function test_plugin_activation()
    {
        // Test that plugin can be activated without errors
        $this->assertTrue(is_plugin_active('ultimate-ajax-datatable/ultimate-ajax-datatable.php'));
        
        // Test that required options are set
        $this->assertNotFalse(get_option('uadt_enabled_post_types'));
        $this->assertNotFalse(get_option('uadt_items_per_page'));
    }

    /**
     * Test database table creation
     */
    public function test_database_table_creation()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'uadt_filter_presets';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        $this->assertTrue($table_exists, 'Filter presets table should be created');
        
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        $column_names = array_column($columns, 'Field');
        
        $expected_columns = ['id', 'user_id', 'name', 'filters', 'post_type', 'is_default', 'created_at', 'updated_at'];
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $column_names, "Column '{$column}' should exist");
        }
    }

    /**
     * Test REST API registration
     */
    public function test_rest_api_registration()
    {
        $routes = rest_get_server()->get_routes();
        
        // Check that our routes are registered
        $expected_routes = [
            '/uadt/v1/posts',
            '/uadt/v1/posts/bulk',
            '/uadt/v1/filter-options',
            '/uadt/v1/search-suggestions',
            '/uadt/v1/presets',
            '/uadt/v1/export',
        ];
        
        foreach ($expected_routes as $route) {
            $this->assertArrayHasKey($route, $routes, "Route '{$route}' should be registered");
        }
    }

    /**
     * Test admin menu registration
     */
    public function test_admin_menu_registration()
    {
        global $menu, $submenu;
        
        // Set up admin environment
        set_current_screen('dashboard');
        do_action('admin_menu');
        
        // Check main menu item
        $menu_found = false;
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'uadt-manager') {
                $menu_found = true;
                break;
            }
        }
        $this->assertTrue($menu_found, 'Main admin menu should be registered');
        
        // Check submenu items
        $this->assertArrayHasKey('uadt-manager', $submenu, 'Submenu should be registered');
    }

    /**
     * Test asset enqueuing
     */
    public function test_asset_enqueuing()
    {
        // Simulate admin page
        set_current_screen('toplevel_page_uadt-manager');
        $_GET['page'] = 'uadt-manager';
        
        do_action('admin_enqueue_scripts', 'toplevel_page_uadt-manager');
        
        // Check that scripts are enqueued
        $this->assertTrue(wp_script_is('uadt-admin-app', 'enqueued'), 'Admin app script should be enqueued');
        $this->assertTrue(wp_style_is('uadt-admin-style', 'enqueued'), 'Admin style should be enqueued');
        
        // Check script localization
        $localized_data = wp_scripts()->get_data('uadt-admin-app', 'data');
        $this->assertNotEmpty($localized_data, 'Script should be localized with data');
        $this->assertStringContainsString('uadtAdmin', $localized_data, 'Should contain uadtAdmin object');
    }

    /**
     * Test with popular themes
     */
    public function test_theme_compatibility()
    {
        // Test with Twenty Twenty-Three (default theme)
        switch_theme('twentytwentythree');
        
        // Simulate admin page load
        set_current_screen('toplevel_page_uadt-manager');
        do_action('admin_enqueue_scripts', 'toplevel_page_uadt-manager');
        
        // Should not cause any fatal errors
        $this->assertTrue(true, 'Should work with default theme');
        
        // Switch back to default
        switch_theme(WP_DEFAULT_THEME);
    }

    /**
     * Test multisite compatibility
     */
    public function test_multisite_compatibility()
    {
        if (!is_multisite()) {
            $this->markTestSkipped('Multisite not available');
        }
        
        // Test that plugin works in multisite environment
        $this->assertTrue(is_plugin_active('ultimate-ajax-datatable/ultimate-ajax-datatable.php'));
        
        // Test that each site has its own options
        $site_id = get_current_blog_id();
        $this->assertNotEmpty(get_option('uadt_enabled_post_types'));
        
        // Switch to different site if available
        $sites = get_sites(['number' => 2]);
        if (count($sites) > 1) {
            $other_site = $sites[1];
            switch_to_blog($other_site->blog_id);
            
            // Should have separate options
            $other_options = get_option('uadt_enabled_post_types');
            $this->assertNotEmpty($other_options);
            
            restore_current_blog();
        }
    }

    /**
     * Test memory usage
     */
    public function test_memory_usage()
    {
        $memory_before = memory_get_usage();
        
        // Create large dataset
        $post_ids = $this->create_test_posts(100);
        
        // Make API request
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'per_page' => 100
        ]);
        
        $controller = new \UltimateAjaxDataTable\API\RestController();
        $response = $controller->get_posts($request);
        
        $memory_after = memory_get_usage();
        $memory_used = $memory_after - $memory_before;
        
        // Should not use more than 50MB for 100 posts
        $this->assertLessThan(50 * 1024 * 1024, $memory_used, 'Memory usage should be reasonable');
    }

    /**
     * Test performance with large datasets
     */
    public function test_performance_large_dataset()
    {
        // Create large dataset
        $this->create_test_posts(1000);
        
        $start_time = microtime(true);
        
        // Make API request
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'per_page' => 25,
            'search' => 'Test'
        ]);
        
        $controller = new \UltimateAjaxDataTable\API\RestController();
        $response = $controller->get_posts($request);
        
        $execution_time = microtime(true) - $start_time;
        
        // Should complete within 2 seconds
        $this->assertLessThan(2.0, $execution_time, 'API should respond within 2 seconds');
        
        // Should return valid response
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('posts', $data);
    }
}
