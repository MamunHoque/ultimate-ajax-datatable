<?php
/**
 * Tests for SecurityManager class
 *
 * @package UltimateAjaxDataTable\Tests
 */

namespace UltimateAjaxDataTable\Tests\Unit;

use UltimateAjaxDataTable\Tests\TestCase;
use UltimateAjaxDataTable\Security\SecurityManager;

/**
 * SecurityManager test class
 */
class SecurityManagerTest extends TestCase
{
    /**
     * Test nonce verification
     */
    public function test_verify_nonce()
    {
        $nonce = wp_create_nonce('wp_rest');
        
        $this->assertTrue(SecurityManager::verify_nonce($nonce, 'wp_rest'));
        $this->assertFalse(SecurityManager::verify_nonce('invalid_nonce', 'wp_rest'));
        $this->assertFalse(SecurityManager::verify_nonce($nonce, 'wrong_action'));
    }

    /**
     * Test capability checking
     */
    public function test_check_capability()
    {
        // Test with current user (admin)
        $this->assertTrue(SecurityManager::check_capability('edit_posts'));
        $this->assertTrue(SecurityManager::check_capability('manage_options'));
        $this->assertFalse(SecurityManager::check_capability('nonexistent_capability'));
        
        // Test with specific user
        $editor = $this->factory->user->create(['role' => 'editor']);
        $this->assertTrue(SecurityManager::check_capability('edit_posts', $editor));
        $this->assertFalse(SecurityManager::check_capability('manage_options', $editor));
        
        // Test with invalid user
        $this->assertFalse(SecurityManager::check_capability('edit_posts', 99999));
    }

    /**
     * Test filter input sanitization
     */
    public function test_sanitize_filter_input()
    {
        // Test text sanitization
        $this->assertEquals('test', SecurityManager::sanitize_filter_input('test', 'text'));
        $this->assertEquals('test', SecurityManager::sanitize_filter_input('<script>test</script>', 'text'));
        
        // Test integer sanitization
        $this->assertEquals(123, SecurityManager::sanitize_filter_input('123', 'int'));
        $this->assertEquals(0, SecurityManager::sanitize_filter_input('abc', 'int'));
        
        // Test float sanitization
        $this->assertEquals(12.34, SecurityManager::sanitize_filter_input('12.34', 'float'));
        $this->assertEquals(0.0, SecurityManager::sanitize_filter_input('abc', 'float'));
        
        // Test email sanitization
        $this->assertEquals('test@example.com', SecurityManager::sanitize_filter_input('test@example.com', 'email'));
        $this->assertEquals('', SecurityManager::sanitize_filter_input('invalid-email', 'email'));
        
        // Test URL sanitization
        $this->assertEquals('https://example.com', SecurityManager::sanitize_filter_input('https://example.com', 'url'));
        
        // Test array sanitization
        $input = ['test1', '<script>test2</script>', 'test3'];
        $expected = ['test1', 'test2', 'test3'];
        $this->assertEquals($expected, SecurityManager::sanitize_filter_input($input, 'array'));
        
        // Test JSON sanitization
        $json_input = '{"key": "value"}';
        $expected = ['key' => 'value'];
        $this->assertEquals($expected, SecurityManager::sanitize_filter_input($json_input, 'json'));
        
        // Test invalid JSON
        $this->assertEquals([], SecurityManager::sanitize_filter_input('invalid json', 'json'));
    }

    /**
     * Test filter validation
     */
    public function test_validate_filters()
    {
        $filters = [
            'search' => 'test query',
            'author' => '123',
            'status' => 'publish',
            'invalid_filter' => 'should be removed',
            'category' => 'test-category',
        ];
        
        $validated = SecurityManager::validate_filters($filters);
        
        $this->assertArrayHasKey('search', $validated);
        $this->assertArrayHasKey('author', $validated);
        $this->assertArrayHasKey('status', $validated);
        $this->assertArrayHasKey('category', $validated);
        $this->assertArrayNotHasKey('invalid_filter', $validated);
        
        $this->assertEquals('test query', $validated['search']);
        $this->assertEquals('123', $validated['author']);
        $this->assertEquals('publish', $validated['status']);
        $this->assertEquals('test-category', $validated['category']);
    }

    /**
     * Test bulk action capability checking
     */
    public function test_can_perform_bulk_action()
    {
        $post_ids = $this->create_test_posts(3);
        
        // Test valid actions with admin user
        $this->assertTrue(SecurityManager::can_perform_bulk_action('publish', $post_ids));
        $this->assertTrue(SecurityManager::can_perform_bulk_action('draft', $post_ids));
        $this->assertTrue(SecurityManager::can_perform_bulk_action('delete', $post_ids));
        
        // Test invalid action
        $this->assertFalse(SecurityManager::can_perform_bulk_action('invalid_action', $post_ids));
        
        // Test empty post IDs
        $this->assertFalse(SecurityManager::can_perform_bulk_action('publish', []));
        
        // Test with non-array
        $this->assertFalse(SecurityManager::can_perform_bulk_action('publish', 'not_array'));
        
        // Test with non-existent post
        $this->assertFalse(SecurityManager::can_perform_bulk_action('publish', [99999]));
    }

    /**
     * Test rate limiting
     */
    public function test_check_rate_limit()
    {
        $key = 'test_key';
        
        // First request should pass
        $this->assertTrue(SecurityManager::check_rate_limit($key, 2, 60));
        
        // Second request should pass
        $this->assertTrue(SecurityManager::check_rate_limit($key, 2, 60));
        
        // Third request should fail (limit is 2)
        $this->assertFalse(SecurityManager::check_rate_limit($key, 2, 60));
        
        // Different key should pass
        $this->assertTrue(SecurityManager::check_rate_limit('different_key', 2, 60));
    }

    /**
     * Test security event logging
     */
    public function test_log_security_event()
    {
        // Enable debug mode for testing
        $original_debug = defined('WP_DEBUG') ? WP_DEBUG : false;
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        // Capture error log
        $error_log = '';
        $original_handler = set_error_handler(function($errno, $errstr) use (&$error_log) {
            $error_log .= $errstr;
        });
        
        SecurityManager::log_security_event('test_event', ['key' => 'value']);
        
        // Restore error handler
        if ($original_handler) {
            set_error_handler($original_handler);
        } else {
            restore_error_handler();
        }
        
        $this->assertStringContainsString('test_event', $error_log);
        $this->assertStringContainsString('key', $error_log);
    }
}
