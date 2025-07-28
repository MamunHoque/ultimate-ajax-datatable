<?php
/**
 * Tests for DataUtility class
 *
 * @package UltimateAjaxDataTable\Tests
 */

namespace UltimateAjaxDataTable\Tests\Unit;

use UltimateAjaxDataTable\Tests\TestCase;
use UltimateAjaxDataTable\API\DataUtility;

/**
 * DataUtility test class
 */
class DataUtilityTest extends TestCase
{
    /**
     * Test caching functionality
     */
    public function test_get_cached()
    {
        $key = 'test_cache_key';
        $expected_data = ['test' => 'data'];
        
        // Test cache miss - callback should be executed
        $callback_executed = false;
        $result = DataUtility::get_cached($key, function() use ($expected_data, &$callback_executed) {
            $callback_executed = true;
            return $expected_data;
        }, 60);
        
        $this->assertTrue($callback_executed, 'Callback should be executed on cache miss');
        $this->assertEquals($expected_data, $result);
        
        // Test cache hit - callback should not be executed
        $callback_executed = false;
        $result = DataUtility::get_cached($key, function() use (&$callback_executed) {
            $callback_executed = true;
            return ['different' => 'data'];
        }, 60);
        
        $this->assertFalse($callback_executed, 'Callback should not be executed on cache hit');
        $this->assertEquals($expected_data, $result);
    }

    /**
     * Test cache clearing
     */
    public function test_clear_cache()
    {
        $key = 'test_clear_key';
        $data = ['test' => 'data'];
        
        // Set cache
        DataUtility::get_cached($key, function() use ($data) {
            return $data;
        }, 60);
        
        // Clear specific cache
        DataUtility::clear_cache($key);
        
        // Should execute callback again
        $callback_executed = false;
        DataUtility::get_cached($key, function() use (&$callback_executed) {
            $callback_executed = true;
            return ['new' => 'data'];
        }, 60);
        
        $this->assertTrue($callback_executed, 'Callback should be executed after cache clear');
    }

    /**
     * Test query optimization
     */
    public function test_optimize_query_args()
    {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 10,
        ];
        
        $optimized = DataUtility::optimize_query_args($args);
        
        $this->assertArrayHasKey('no_found_rows', $optimized);
        $this->assertArrayHasKey('update_post_meta_cache', $optimized);
        $this->assertArrayHasKey('update_post_term_cache', $optimized);
        $this->assertArrayHasKey('cache_results', $optimized);
        
        $this->assertFalse($optimized['no_found_rows']);
        $this->assertFalse($optimized['update_post_meta_cache']);
        $this->assertTrue($optimized['update_post_term_cache']);
        $this->assertTrue($optimized['cache_results']);
    }

    /**
     * Test filter sanitization
     */
    public function test_sanitize_filters()
    {
        $filters = [
            'search' => '<script>alert("xss")</script>test',
            'page' => '2',
            'per_page' => '25',
            'post_type' => 'post',
            'author' => '123',
            'status' => 'publish',
            'category' => ['cat1', 'cat2'],
            'tag' => 'tag1,tag2',
            'date_from' => '2023-01-01',
            'date_to' => '2023-12-31',
            'orderby' => 'date',
            'order' => 'DESC',
            'invalid_filter' => 'should_be_removed',
        ];
        
        $sanitized = DataUtility::sanitize_filters($filters);
        
        // Check sanitization
        $this->assertEquals('test', $sanitized['search']);
        $this->assertEquals(2, $sanitized['page']);
        $this->assertEquals(25, $sanitized['per_page']);
        $this->assertEquals('post', $sanitized['post_type']);
        $this->assertEquals('123', $sanitized['author']);
        $this->assertEquals('publish', $sanitized['status']);
        $this->assertEquals(['cat1', 'cat2'], $sanitized['category']);
        $this->assertEquals('tag1,tag2', $sanitized['tag']);
        $this->assertEquals('2023-01-01', $sanitized['date_from']);
        $this->assertEquals('2023-12-31', $sanitized['date_to']);
        $this->assertEquals('date', $sanitized['orderby']);
        $this->assertEquals('DESC', $sanitized['order']);
        
        // Check that invalid filter is removed
        $this->assertArrayNotHasKey('invalid_filter', $sanitized);
        
        // Check minimum values for page and per_page
        $filters_with_invalid_numbers = [
            'page' => '0',
            'per_page' => '-5',
        ];
        
        $sanitized = DataUtility::sanitize_filters($filters_with_invalid_numbers);
        $this->assertEquals(1, $sanitized['page']);
        $this->assertEquals(1, $sanitized['per_page']);
    }

    /**
     * Test search suggestions
     */
    public function test_get_search_suggestions()
    {
        // Create test posts
        $this->factory->post->create(['post_title' => 'Searchable Post One']);
        $this->factory->post->create(['post_title' => 'Searchable Post Two']);
        $this->factory->post->create(['post_title' => 'Different Title']);
        
        $suggestions = DataUtility::get_search_suggestions('Search', 'post', 5);
        
        $this->assertIsArray($suggestions);
        
        // Should contain posts with "Search" in title
        $found_searchable = false;
        foreach ($suggestions as $suggestion) {
            if (strpos($suggestion, 'Searchable') !== false) {
                $found_searchable = true;
                break;
            }
        }
        $this->assertTrue($found_searchable, 'Should find posts with search term');
    }

    /**
     * Test cache key generation
     */
    public function test_generate_cache_key()
    {
        $filters1 = ['search' => 'test', 'page' => 1];
        $filters2 = ['page' => 1, 'search' => 'test']; // Same filters, different order
        $filters3 = ['search' => 'different', 'page' => 1];
        
        $key1 = DataUtility::generate_cache_key($filters1);
        $key2 = DataUtility::generate_cache_key($filters2);
        $key3 = DataUtility::generate_cache_key($filters3);
        
        // Same filters should generate same key regardless of order
        $this->assertEquals($key1, $key2);
        
        // Different filters should generate different keys
        $this->assertNotEquals($key1, $key3);
        
        // Keys should be strings
        $this->assertIsString($key1);
        $this->assertIsString($key2);
        $this->assertIsString($key3);
    }

    /**
     * Test performance stats
     */
    public function test_get_performance_stats()
    {
        $stats = DataUtility::get_performance_stats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('query_time', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('memory_peak', $stats);
        
        $this->assertIsInt($stats['total_queries']);
        $this->assertIsString($stats['query_time']);
        $this->assertIsString($stats['memory_usage']);
        $this->assertIsString($stats['memory_peak']);
    }

    /**
     * Test slow query logging
     */
    public function test_log_slow_query()
    {
        // Enable debug mode
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        // Capture error log
        $error_log = '';
        $original_handler = set_error_handler(function($errno, $errstr) use (&$error_log) {
            $error_log .= $errstr;
        });
        
        // Log slow query
        DataUtility::log_slow_query('SELECT * FROM test', 1.5);
        
        // Restore error handler
        if ($original_handler) {
            set_error_handler($original_handler);
        } else {
            restore_error_handler();
        }
        
        $this->assertStringContainsString('Slow Query', $error_log);
        $this->assertStringContainsString('1.500s', $error_log);
        $this->assertStringContainsString('SELECT * FROM test', $error_log);
    }

    /**
     * Test that fast queries are not logged
     */
    public function test_fast_query_not_logged()
    {
        // Capture error log
        $error_log = '';
        $original_handler = set_error_handler(function($errno, $errstr) use (&$error_log) {
            $error_log .= $errstr;
        });
        
        // Log fast query
        DataUtility::log_slow_query('SELECT * FROM test', 0.1);
        
        // Restore error handler
        if ($original_handler) {
            set_error_handler($original_handler);
        } else {
            restore_error_handler();
        }
        
        $this->assertStringNotContainsString('Slow Query', $error_log);
    }
}
