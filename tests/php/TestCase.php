<?php
/**
 * Base test case for Ultimate Ajax DataTable tests
 *
 * @package UltimateAjaxDataTable\Tests
 */

namespace UltimateAjaxDataTable\Tests;

use WP_UnitTestCase;
use WP_User;

/**
 * Base test case class
 */
class TestCase extends WP_UnitTestCase
{
    /**
     * Test user
     *
     * @var WP_User
     */
    protected $test_user;

    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Create test user with admin capabilities
        $this->test_user = $this->factory->user->create_and_get([
            'role' => 'administrator',
        ]);
        
        wp_set_current_user($this->test_user->ID);
        
        // Set up plugin options
        update_option('uadt_enabled_post_types', ['post', 'page']);
        update_option('uadt_items_per_page', 25);
        update_option('uadt_enable_search', true);
        update_option('uadt_enable_filters', true);
        update_option('uadt_enable_bulk_actions', true);
    }

    /**
     * Clean up after test
     */
    public function tearDown(): void
    {
        // Clean up options
        delete_option('uadt_enabled_post_types');
        delete_option('uadt_items_per_page');
        delete_option('uadt_enable_search');
        delete_option('uadt_enable_filters');
        delete_option('uadt_enable_bulk_actions');
        
        parent::tearDown();
    }

    /**
     * Create test posts
     *
     * @param int $count Number of posts to create
     * @param array $args Additional post arguments
     * @return array Array of post IDs
     */
    protected function create_test_posts($count = 5, $args = [])
    {
        $post_ids = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $post_args = array_merge([
                'post_title' => "Test Post {$i}",
                'post_content' => "Content for test post {$i}",
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => $this->test_user->ID,
            ], $args);
            
            $post_ids[] = $this->factory->post->create($post_args);
        }
        
        return $post_ids;
    }

    /**
     * Create test categories
     *
     * @param int $count Number of categories to create
     * @return array Array of category IDs
     */
    protected function create_test_categories($count = 3)
    {
        $category_ids = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $category_ids[] = $this->factory->category->create([
                'name' => "Test Category {$i}",
                'slug' => "test-category-{$i}",
            ]);
        }
        
        return $category_ids;
    }

    /**
     * Create test tags
     *
     * @param int $count Number of tags to create
     * @return array Array of tag IDs
     */
    protected function create_test_tags($count = 3)
    {
        $tag_ids = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $tag_ids[] = $this->factory->tag->create([
                'name' => "Test Tag {$i}",
                'slug' => "test-tag-{$i}",
            ]);
        }
        
        return $tag_ids;
    }

    /**
     * Assert that a REST response is successful
     *
     * @param \WP_REST_Response $response
     */
    protected function assertRestResponseSuccess($response)
    {
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Assert that a REST response is an error
     *
     * @param \WP_REST_Response|\WP_Error $response
     * @param int $expected_status
     */
    protected function assertRestResponseError($response, $expected_status = 400)
    {
        if ($response instanceof \WP_Error) {
            $this->assertInstanceOf('WP_Error', $response);
        } else {
            $this->assertInstanceOf('WP_REST_Response', $response);
            $this->assertEquals($expected_status, $response->get_status());
        }
    }

    /**
     * Create a mock REST request
     *
     * @param string $method HTTP method
     * @param string $route Route path
     * @param array $params Request parameters
     * @return \WP_REST_Request
     */
    protected function create_rest_request($method = 'GET', $route = '/uadt/v1/posts', $params = [])
    {
        $request = new \WP_REST_Request($method, $route);
        
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }
        
        // Set nonce header
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        
        return $request;
    }
}
