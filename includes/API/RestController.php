<?php
/**
 * REST API Controller Class
 *
 * @package UltimateAjaxDataTable\API
 * @since 1.0.0
 */

namespace UltimateAjaxDataTable\API;

use UltimateAjaxDataTable\Security\SecurityManager;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RestController class
 */
class RestController
{
    /**
     * API namespace
     */
    const NAMESPACE = 'uadt/v1';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes()
    {
        // Posts endpoint
        register_rest_route(self::NAMESPACE, '/posts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_posts'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => $this->get_posts_args()
        ]);

        // Bulk actions endpoint
        register_rest_route(self::NAMESPACE, '/posts/bulk', [
            'methods' => 'POST',
            'callback' => [$this, 'bulk_actions'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => $this->get_bulk_args()
        ]);

        // Filter presets endpoint
        register_rest_route(self::NAMESPACE, '/presets', [
            'methods' => ['GET', 'POST'],
            'callback' => [$this, 'handle_presets'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Export endpoint
        register_rest_route(self::NAMESPACE, '/export', [
            'methods' => 'POST',
            'callback' => [$this, 'export_data'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    /**
     * Check permissions for API access
     *
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function check_permissions($request)
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }

        // Check nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!SecurityManager::verify_nonce($nonce)) {
            return false;
        }

        // Check basic capability
        if (!SecurityManager::check_capability('edit_posts')) {
            return false;
        }

        // Rate limiting
        $user_id = get_current_user_id();
        if (!SecurityManager::check_rate_limit('api_user_' . $user_id, 200, 3600)) {
            return new \WP_Error('rate_limit_exceeded', 'Rate limit exceeded', ['status' => 429]);
        }

        return true;
    }

    /**
     * Get posts with filtering
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_posts($request)
    {
        try {
            $params = $request->get_params();
            $filters = SecurityManager::validate_filters($params);

            // Build query args
            $query_args = $this->build_query_args($filters);

            // Execute query
            $query = new \WP_Query($query_args);

            // Prepare response data
            $posts = [];
            foreach ($query->posts as $post) {
                $posts[] = $this->prepare_post_data($post);
            }

            $response_data = [
                'posts' => $posts,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $query_args['paged'],
                'per_page' => $query_args['posts_per_page']
            ];

            return new \WP_REST_Response($response_data, 200);

        } catch (\Exception $e) {
            SecurityManager::log_security_event('api_error', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', 'An error occurred while fetching posts', ['status' => 500]);
        }
    }

    /**
     * Handle bulk actions
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function bulk_actions($request)
    {
        $action = $request->get_param('action');
        $post_ids = $request->get_param('post_ids');

        if (!SecurityManager::can_perform_bulk_action($action, $post_ids)) {
            return new \WP_Error('insufficient_permissions', 'Insufficient permissions for bulk action', ['status' => 403]);
        }

        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($post_ids as $post_id) {
            $result = $this->perform_bulk_action($action, $post_id);
            $results[$post_id] = $result;
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        return new \WP_REST_Response([
            'success' => $error_count === 0,
            'results' => $results,
            'summary' => [
                'success_count' => $success_count,
                'error_count' => $error_count,
                'total' => count($post_ids)
            ]
        ], 200);
    }

    /**
     * Handle filter presets
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_presets($request)
    {
        if ($request->get_method() === 'GET') {
            return $this->get_presets($request);
        } else {
            return $this->save_preset($request);
        }
    }

    /**
     * Export data
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function export_data($request)
    {
        $format = $request->get_param('format');
        $filters = SecurityManager::validate_filters($request->get_params());

        if (!in_array($format, ['csv', 'excel'])) {
            return new \WP_Error('invalid_format', 'Invalid export format', ['status' => 400]);
        }

        // This will be implemented in the next phase
        return new \WP_REST_Response(['message' => 'Export functionality coming soon'], 200);
    }

    /**
     * Get arguments for posts endpoint
     *
     * @return array
     */
    private function get_posts_args()
    {
        return [
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'default' => 25,
                'sanitize_callback' => 'absint',
            ],
            'search' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'post_type' => [
                'default' => 'post',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'author' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'status' => [
                'default' => 'publish',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'date_from' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'date_to' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get arguments for bulk actions endpoint
     *
     * @return array
     */
    private function get_bulk_args()
    {
        return [
            'action' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'post_ids' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_array($param) && !empty($param);
                },
            ],
        ];
    }

    /**
     * Build WP_Query arguments from filters
     *
     * @param array $filters
     * @return array
     */
    private function build_query_args($filters)
    {
        $args = [
            'post_type' => $filters['post_type'] ?? 'post',
            'post_status' => $filters['status'] ?? 'publish',
            'posts_per_page' => min($filters['per_page'] ?? 25, 100),
            'paged' => $filters['page'] ?? 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        // Add search
        if (!empty($filters['search'])) {
            $args['s'] = $filters['search'];
        }

        // Add author filter
        if (!empty($filters['author'])) {
            $args['author'] = $filters['author'];
        }

        // Add date filters
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $date_query = [];
            
            if (!empty($filters['date_from'])) {
                $date_query['after'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $date_query['before'] = $filters['date_to'];
            }
            
            $args['date_query'] = [$date_query];
        }

        return $args;
    }

    /**
     * Prepare post data for API response
     *
     * @param \WP_Post $post
     * @return array
     */
    private function prepare_post_data($post)
    {
        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'status' => $post->post_status,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'author_id' => $post->post_author,
            'date' => get_the_date('Y-m-d H:i:s', $post),
            'modified' => get_the_modified_date('Y-m-d H:i:s', $post),
            'post_type' => $post->post_type,
            'edit_link' => get_edit_post_link($post->ID),
            'view_link' => get_permalink($post->ID),
        ];
    }

    /**
     * Perform individual bulk action
     *
     * @param string $action
     * @param int $post_id
     * @return array
     */
    private function perform_bulk_action($action, $post_id)
    {
        try {
            switch ($action) {
                case 'trash':
                    $result = wp_trash_post($post_id);
                    break;
                
                case 'delete':
                    $result = wp_delete_post($post_id, true);
                    break;
                
                case 'publish':
                case 'draft':
                case 'private':
                    $result = wp_update_post([
                        'ID' => $post_id,
                        'post_status' => $action
                    ]);
                    break;
                
                default:
                    return ['success' => false, 'error' => 'Invalid action'];
            }

            return ['success' => $result !== false, 'result' => $result];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get filter presets
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    private function get_presets($request)
    {
        // This will be implemented when we add preset functionality
        return new \WP_REST_Response(['presets' => []], 200);
    }

    /**
     * Save filter preset
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    private function save_preset($request)
    {
        // This will be implemented when we add preset functionality
        return new \WP_REST_Response(['message' => 'Preset saved'], 200);
    }
}
