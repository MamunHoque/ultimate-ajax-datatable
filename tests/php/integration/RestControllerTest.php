<?php
/**
 * Integration tests for REST API Controller
 *
 * @package UltimateAjaxDataTable\Tests
 */

namespace UltimateAjaxDataTable\Tests\Integration;

use UltimateAjaxDataTable\Tests\TestCase;
use UltimateAjaxDataTable\API\RestController;

/**
 * RestController integration test class
 */
class RestControllerTest extends TestCase
{
    /**
     * REST controller instance
     *
     * @var RestController
     */
    private $controller;

    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new RestController();
        
        // Register REST routes
        do_action('rest_api_init');
    }

    /**
     * Test posts endpoint with no filters
     */
    public function test_get_posts_no_filters()
    {
        $post_ids = $this->create_test_posts(5);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts');
        $response = $this->controller->get_posts($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('pages', $data);
        $this->assertCount(5, $data['posts']);
        $this->assertEquals(5, $data['total']);
    }

    /**
     * Test posts endpoint with search filter
     */
    public function test_get_posts_with_search()
    {
        $this->create_test_posts(3, ['post_title' => 'Searchable Post']);
        $this->create_test_posts(2, ['post_title' => 'Other Post']);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'search' => 'Searchable'
        ]);
        $response = $this->controller->get_posts($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertEquals(3, $data['total']);
        
        foreach ($data['posts'] as $post) {
            $this->assertStringContainsString('Searchable', $post['title']);
        }
    }

    /**
     * Test posts endpoint with author filter
     */
    public function test_get_posts_with_author_filter()
    {
        $author1 = $this->factory->user->create();
        $author2 = $this->factory->user->create();
        
        $this->create_test_posts(2, ['post_author' => $author1]);
        $this->create_test_posts(3, ['post_author' => $author2]);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'author' => $author1
        ]);
        $response = $this->controller->get_posts($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertEquals(2, $data['total']);
        
        foreach ($data['posts'] as $post) {
            $this->assertEquals($author1, $post['author_id']);
        }
    }

    /**
     * Test posts endpoint with status filter
     */
    public function test_get_posts_with_status_filter()
    {
        $this->create_test_posts(2, ['post_status' => 'publish']);
        $this->create_test_posts(3, ['post_status' => 'draft']);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'status' => 'draft'
        ]);
        $response = $this->controller->get_posts($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertEquals(3, $data['total']);
        
        foreach ($data['posts'] as $post) {
            $this->assertEquals('draft', $post['status']);
        }
    }

    /**
     * Test posts endpoint with pagination
     */
    public function test_get_posts_with_pagination()
    {
        $this->create_test_posts(10);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'per_page' => 3,
            'page' => 2
        ]);
        $response = $this->controller->get_posts($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertEquals(10, $data['total']);
        $this->assertEquals(2, $data['current_page']);
        $this->assertEquals(3, $data['per_page']);
        $this->assertCount(3, $data['posts']);
    }

    /**
     * Test posts endpoint with date range filter
     */
    public function test_get_posts_with_date_filter()
    {
        // Create posts with specific dates
        $old_post = $this->factory->post->create([
            'post_date' => '2023-01-01 10:00:00',
            'post_title' => 'Old Post'
        ]);
        
        $new_post = $this->factory->post->create([
            'post_date' => '2023-12-01 10:00:00',
            'post_title' => 'New Post'
        ]);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts', [
            'date_from' => '2023-06-01',
            'date_to' => '2023-12-31'
        ]);
        $response = $this->controller->get_posts($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertEquals(1, $data['total']);
        $this->assertEquals('New Post', $data['posts'][0]['title']);
    }

    /**
     * Test bulk actions endpoint
     */
    public function test_bulk_actions()
    {
        $post_ids = $this->create_test_posts(3, ['post_status' => 'publish']);
        
        $request = $this->create_rest_request('POST', '/uadt/v1/posts/bulk', [
            'action' => 'draft',
            'post_ids' => $post_ids
        ]);
        $response = $this->controller->bulk_actions($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['summary']['success_count']);
        $this->assertEquals(0, $data['summary']['error_count']);
        
        // Verify posts were updated
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            $this->assertEquals('draft', $post->post_status);
        }
    }

    /**
     * Test bulk actions with invalid action
     */
    public function test_bulk_actions_invalid_action()
    {
        $post_ids = $this->create_test_posts(2);
        
        $request = $this->create_rest_request('POST', '/uadt/v1/posts/bulk', [
            'action' => 'invalid_action',
            'post_ids' => $post_ids
        ]);
        $response = $this->controller->bulk_actions($request);
        
        $this->assertRestResponseError($response, 403);
    }

    /**
     * Test filter options endpoint
     */
    public function test_get_filter_options()
    {
        // Create test data
        $author = $this->factory->user->create(['display_name' => 'Test Author']);
        $this->create_test_posts(1, ['post_author' => $author]);
        
        $category_ids = $this->create_test_categories(2);
        $tag_ids = $this->create_test_tags(2);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/filter-options', [
            'post_type' => 'post'
        ]);
        $response = $this->controller->get_filter_options($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertArrayHasKey('authors', $data);
        $this->assertArrayHasKey('statuses', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('tags', $data);
        
        // Check authors
        $this->assertNotEmpty($data['authors']);
        $author_names = array_column($data['authors'], 'name');
        $this->assertContains('Test Author', $author_names);
        
        // Check categories and tags
        $this->assertCount(2, $data['categories']);
        $this->assertCount(2, $data['tags']);
    }

    /**
     * Test search suggestions endpoint
     */
    public function test_get_search_suggestions()
    {
        $this->create_test_posts(1, ['post_title' => 'Searchable Content']);
        $this->create_test_posts(1, ['post_title' => 'Search Results']);
        $this->create_test_posts(1, ['post_title' => 'Other Content']);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/search-suggestions', [
            'query' => 'Search',
            'post_type' => 'post'
        ]);
        $response = $this->controller->get_search_suggestions($request);
        
        $this->assertRestResponseSuccess($response);
        
        $data = $response->get_data();
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertNotEmpty($data['suggestions']);
        
        foreach ($data['suggestions'] as $suggestion) {
            $this->assertStringContainsString('Search', $suggestion);
        }
    }

    /**
     * Test permission checking
     */
    public function test_permission_checking()
    {
        // Test without login
        wp_set_current_user(0);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts');
        $response = $this->controller->check_permissions($request);
        
        $this->assertFalse($response);
        
        // Test with subscriber (no edit_posts capability)
        $subscriber = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts');
        $response = $this->controller->check_permissions($request);
        
        $this->assertFalse($response);
        
        // Test with editor (has edit_posts capability)
        $editor = $this->factory->user->create(['role' => 'editor']);
        wp_set_current_user($editor);
        
        $request = $this->create_rest_request('GET', '/uadt/v1/posts');
        $response = $this->controller->check_permissions($request);
        
        $this->assertTrue($response);
    }
}
