<?php
/**
 * REST API Controller Class
 *
 * @package UltimateAjaxDataTable\API
 * @since 1.0.0
 */

namespace UltimateAjaxDataTable\API;

use UltimateAjaxDataTable\Security\SecurityManager;
use UltimateAjaxDataTable\API\DataUtility;

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
            'args' => $this->get_export_args()
        ]);

        // Filter options endpoint
        register_rest_route(self::NAMESPACE, '/filter-options', [
            'methods' => 'GET',
            'callback' => [$this, 'get_filter_options'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'post_type' => [
                    'default' => 'post',
                    'sanitize_callback' => 'sanitize_text_field',
                ]
            ]
        ]);

        // Search suggestions endpoint
        register_rest_route(self::NAMESPACE, '/search-suggestions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_search_suggestions'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'query' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'post_type' => [
                    'default' => 'post',
                    'sanitize_callback' => 'sanitize_text_field',
                ]
            ]
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
            $start_time = microtime(true);

            $params = $request->get_params();
            $filters = DataUtility::sanitize_filters($params);

            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('UADT API Request - Params: ' . print_r($params, true));
                error_log('UADT API Request - Filters: ' . print_r($filters, true));
            }

            // Generate cache key
            $cache_key = 'posts_' . DataUtility::generate_cache_key($filters);

            // Disable caching for search queries to ensure fresh results
            $use_cache = empty($filters['search']);

            if ($use_cache) {
                // Try to get cached results
                $cached_result = DataUtility::get_cached($cache_key, function() use ($filters) {
                    return $this->execute_posts_query($filters);
                }, 300); // Cache for 5 minutes
            } else {
                // Execute query directly without caching
                $cached_result = $this->execute_posts_query($filters);
            }

            $execution_time = microtime(true) - $start_time;
            $cached_result['query_time'] = round($execution_time, 3);
            $cached_result['cached'] = $use_cache;

            // Log slow queries
            if ($execution_time > 0.5) {
                DataUtility::log_slow_query('get_posts', $execution_time);
            }

            return new \WP_REST_Response($cached_result, 200);

        } catch (\Exception $e) {
            SecurityManager::log_security_event('api_error', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', 'An error occurred while fetching posts', ['status' => 500]);
        }
    }

    /**
     * Execute posts query
     *
     * @param array $filters
     * @return array
     */
    private function execute_posts_query($filters)
    {
        // Build query args
        $query_args = $this->build_query_args($filters);
        $query_args = DataUtility::optimize_query_args($query_args);

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UADT Query Args: ' . print_r($query_args, true));
        }

        // Execute query
        $query = new \WP_Query($query_args);

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UADT Query Results: Found ' . $query->found_posts . ' posts');
            error_log('UADT Query SQL: ' . $query->request);
        }

        // Prepare response data
        $posts = [];
        foreach ($query->posts as $post) {
            $posts[] = $this->prepare_post_data($post);
        }

        return [
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $query_args['paged'],
            'per_page' => $query_args['posts_per_page'],
            'query_time' => 0 // Will be updated by caller
        ];
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
        $confirm = $request->get_param('confirm');

        // Validate action
        $allowed_actions = ['publish', 'draft', 'private', 'trash', 'delete'];
        if (!in_array($action, $allowed_actions)) {
            return new \WP_Error('invalid_action', 'Invalid bulk action', ['status' => 400]);
        }

        // Check for confirmation on destructive actions
        if (in_array($action, ['trash', 'delete']) && !$confirm) {
            return new \WP_Error('confirmation_required', 'Confirmation required for destructive actions', ['status' => 400]);
        }

        if (!SecurityManager::can_perform_bulk_action($action, $post_ids)) {
            return new \WP_Error('insufficient_permissions', 'Insufficient permissions for bulk action', ['status' => 403]);
        }

        $results = [];
        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($post_ids as $post_id) {
            $result = $this->perform_bulk_action($action, $post_id);
            $results[$post_id] = $result;

            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = [
                    'post_id' => $post_id,
                    'error' => $result['message'] ?? 'Unknown error'
                ];
            }
        }

        $message = $this->get_bulk_action_message($action, $success_count, $error_count);

        return new \WP_REST_Response([
            'success' => $error_count === 0,
            'message' => $message,
            'results' => $results,
            'summary' => [
                'success_count' => $success_count,
                'error_count' => $error_count,
                'total' => count($post_ids),
                'errors' => $errors
            ]
        ], 200);
    }

    /**
     * Get bulk action message
     *
     * @param string $action
     * @param int $success_count
     * @param int $error_count
     * @return string
     */
    private function get_bulk_action_message($action, $success_count, $error_count)
    {
        $action_labels = [
            'publish' => 'published',
            'draft' => 'moved to draft',
            'private' => 'made private',
            'trash' => 'moved to trash',
            'delete' => 'deleted permanently'
        ];

        $action_label = $action_labels[$action] ?? $action;

        if ($error_count === 0) {
            return sprintf(
                '%d post%s %s successfully.',
                $success_count,
                $success_count !== 1 ? 's' : '',
                $action_label
            );
        } else {
            return sprintf(
                '%d post%s %s successfully. %d error%s occurred.',
                $success_count,
                $success_count !== 1 ? 's' : '',
                $action_label,
                $error_count,
                $error_count !== 1 ? 's' : ''
            );
        }
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
        $columns = $request->get_param('columns') ?: ['id', 'title', 'status', 'author', 'date'];
        $filename = $request->get_param('filename') ?: 'posts_export_' . date('Y-m-d');

        // Get all filter parameters
        $filters = DataUtility::sanitize_filters($request->get_params());
        $filters['per_page'] = -1; // Export all matching posts

        if (!in_array($format, ['csv', 'excel'])) {
            return new \WP_Error('invalid_format', 'Invalid export format', ['status' => 400]);
        }

        try {
            // Get posts data
            $query_args = $this->build_query_args($filters);
            $query = new \WP_Query($query_args);

            if (!$query->have_posts()) {
                return new \WP_Error('no_posts', 'No posts found to export', ['status' => 404]);
            }

            // Prepare export data
            $export_data = [];
            $headers = $this->get_export_headers($columns);
            $export_data[] = $headers;

            foreach ($query->posts as $post) {
                $post_data = $this->prepare_post_data($post);
                $row = [];

                foreach ($columns as $column) {
                    $row[] = $this->get_export_column_value($post_data, $column);
                }

                $export_data[] = $row;
            }

            // Generate file
            $file_path = $this->generate_export_file($export_data, $format, $filename);

            if (!$file_path) {
                return new \WP_Error('export_failed', 'Failed to generate export file', ['status' => 500]);
            }

            // Return download URL
            $upload_dir = wp_upload_dir();
            $download_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);

            return new \WP_REST_Response([
                'success' => true,
                'message' => sprintf('Successfully exported %d posts', count($export_data) - 1),
                'download_url' => $download_url,
                'filename' => basename($file_path),
                'format' => $format,
                'total_posts' => count($export_data) - 1
            ], 200);

        } catch (\Exception $e) {
            return new \WP_Error('export_error', 'Export failed: ' . $e->getMessage(), ['status' => 500]);
        }
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
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                }
            ],
            'per_page' => [
                'default' => 25,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                }
            ],
            'search' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'post_type' => [
                'default' => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return post_type_exists($param);
                }
            ],
            'author' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'status' => [
                'default' => 'publish',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    $valid_statuses = ['publish', 'draft', 'private', 'pending', 'trash', 'any'];
                    return in_array($param, $valid_statuses);
                }
            ],
            'date_from' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return empty($param) || $this->validate_date($param);
                }
            ],
            'date_to' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return empty($param) || $this->validate_date($param);
                }
            ],
            'category' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'tag' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    $valid_orderby = ['date', 'title', 'author', 'modified', 'menu_order', 'ID'];
                    return in_array($param, $valid_orderby);
                }
            ],
            'order' => [
                'default' => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return in_array(strtoupper($param), ['ASC', 'DESC']);
                }
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
                'validate_callback' => function($param) {
                    return in_array($param, ['publish', 'draft', 'private', 'trash', 'delete']);
                },
            ],
            'post_ids' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_array($param) && !empty($param);
                },
            ],
            'confirm' => [
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
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
            'post_status' => !empty($filters['status']) ? $filters['status'] : ['publish', 'draft', 'private'],
            'posts_per_page' => min($filters['per_page'] ?? 25, 100),
            'paged' => $filters['page'] ?? 1,
            'orderby' => $filters['orderby'] ?? 'date',
            'order' => strtoupper($filters['order'] ?? 'DESC'),
            'no_found_rows' => false, // We need total count for pagination
            'update_post_meta_cache' => false, // Optimize performance
            'update_post_term_cache' => true, // We need terms for display
        ];

        // Add search functionality
        if (!empty($filters['search'])) {
            $search_term = trim($filters['search']);

            if (!empty($search_term)) {
                $args['s'] = $search_term;
            }
        }

        // Add author filter (support both ID and name)
        if (!empty($filters['author'])) {
            if (is_numeric($filters['author'])) {
                $args['author'] = $filters['author'];
            } else {
                $args['author_name'] = $filters['author'];
            }
        }

        // Add taxonomy filters
        $tax_query = [];

        // Category filter
        if (!empty($filters['category'])) {
            $categories = explode(',', $filters['category']);
            $tax_query[] = [
                'taxonomy' => 'category',
                'field' => is_numeric($categories[0]) ? 'term_id' : 'slug',
                'terms' => $categories,
                'operator' => 'IN'
            ];
        }

        // Tag filter
        if (!empty($filters['tag'])) {
            $tags = explode(',', $filters['tag']);
            $tax_query[] = [
                'taxonomy' => 'post_tag',
                'field' => is_numeric($tags[0]) ? 'term_id' : 'slug',
                'terms' => $tags,
                'operator' => 'IN'
            ];
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Add date filters with enhanced functionality
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $date_query = [];

            if (!empty($filters['date_from'])) {
                $date_query['after'] = $filters['date_from'];
                $date_query['inclusive'] = true;
            }

            if (!empty($filters['date_to'])) {
                $date_query['before'] = $filters['date_to'] . ' 23:59:59';
                $date_query['inclusive'] = true;
            }

            $args['date_query'] = [$date_query];
        }

        // Add caching for better performance
        $args['cache_results'] = true;
        $args['suppress_filters'] = false; // Allow other plugins to modify

        return apply_filters('uadt_query_args', $args, $filters);
    }

    /**
     * Prepare post data for API response
     *
     * @param \WP_Post $post
     * @return array
     */
    private function prepare_post_data($post)
    {
        // Get post type object for labels
        $post_type_obj = get_post_type_object($post->post_type);

        // Get categories and tags
        $categories = get_the_category($post->ID);
        $tags = get_the_tags($post->ID);

        // Get featured image
        $featured_image = '';
        if (has_post_thumbnail($post->ID)) {
            $featured_image = get_the_post_thumbnail_url($post->ID, 'thumbnail');
        }

        // Get excerpt
        $excerpt = '';
        if (!empty($post->post_excerpt)) {
            $excerpt = $post->post_excerpt;
        } else {
            $excerpt = wp_trim_words(strip_tags($post->post_content), 20);
        }

        return [
            'id' => $post->ID,
            'title' => get_the_title($post) ?: __('(No title)', 'ultimate-ajax-datatable'),
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'status_label' => $this->get_status_label($post->post_status),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'author_id' => $post->post_author,
            'date' => get_the_date('Y-m-d H:i:s', $post),
            'date_formatted' => get_the_date('M j, Y', $post),
            'modified' => get_the_modified_date('Y-m-d H:i:s', $post),
            'modified_formatted' => get_the_modified_date('M j, Y', $post),
            'post_type' => $post->post_type,
            'post_type_label' => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
            'excerpt' => $excerpt,
            'featured_image' => $featured_image,
            'categories' => array_map(function($cat) {
                return [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug
                ];
            }, $categories ?: []),
            'tags' => array_map(function($tag) {
                return [
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                ];
            }, $tags ?: []),
            'edit_link' => current_user_can('edit_post', $post->ID) ? get_edit_post_link($post->ID) : '',
            'view_link' => get_permalink($post->ID),
            'can_edit' => current_user_can('edit_post', $post->ID),
            'can_delete' => current_user_can('delete_post', $post->ID),
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

    /**
     * Validate date format
     *
     * @param string $date
     * @return bool
     */
    private function validate_date($date)
    {
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'm/d/Y', 'd/m/Y'];

        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get human-readable status label
     *
     * @param string $status
     * @return string
     */
    private function get_status_label($status)
    {
        $labels = [
            'publish' => __('Published', 'ultimate-ajax-datatable'),
            'draft' => __('Draft', 'ultimate-ajax-datatable'),
            'private' => __('Private', 'ultimate-ajax-datatable'),
            'pending' => __('Pending Review', 'ultimate-ajax-datatable'),
            'trash' => __('Trash', 'ultimate-ajax-datatable'),
            'auto-draft' => __('Auto Draft', 'ultimate-ajax-datatable'),
            'inherit' => __('Revision', 'ultimate-ajax-datatable'),
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get filter options for dropdowns
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_filter_options($request)
    {
        $post_type = $request->get_param('post_type') ?: 'post';

        if (!post_type_exists($post_type)) {
            return new \WP_Error('invalid_post_type', 'Invalid post type', ['status' => 400]);
        }

        $options = [
            'authors' => $this->get_authors_for_post_type($post_type),
            'statuses' => $this->get_post_statuses(),
            'categories' => $this->get_categories_for_post_type($post_type),
            'tags' => $this->get_tags_for_post_type($post_type),
        ];

        return new \WP_REST_Response($options, 200);
    }

    /**
     * Get authors who have posts of the specified type
     *
     * @param string $post_type
     * @return array
     */
    private function get_authors_for_post_type($post_type)
    {
        global $wpdb;

        $authors = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT u.ID, u.display_name, u.user_login
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
            WHERE p.post_type = %s AND p.post_status != 'trash'
            ORDER BY u.display_name
        ", $post_type));

        return array_map(function($author) {
            return [
                'id' => $author->ID,
                'name' => $author->display_name,
                'login' => $author->user_login
            ];
        }, $authors);
    }

    /**
     * Get available post statuses
     *
     * @return array
     */
    private function get_post_statuses()
    {
        $statuses = get_post_stati(['show_in_admin_status_list' => true], 'objects');

        $result = [];
        foreach ($statuses as $status => $obj) {
            $result[] = [
                'value' => $status,
                'label' => $obj->label
            ];
        }

        return $result;
    }

    /**
     * Get categories for post type
     *
     * @param string $post_type
     * @return array
     */
    private function get_categories_for_post_type($post_type)
    {
        if ($post_type !== 'post') {
            return [];
        }

        $categories = get_categories(['hide_empty' => false, 'number' => 100]);

        return array_map(function($cat) {
            return [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->count
            ];
        }, $categories);
    }

    /**
     * Get tags for post type
     *
     * @param string $post_type
     * @return array
     */
    private function get_tags_for_post_type($post_type)
    {
        if ($post_type !== 'post') {
            return [];
        }

        $tags = get_tags(['hide_empty' => false, 'number' => 100]);

        return array_map(function($tag) {
            return [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count
            ];
        }, $tags);
    }

    /**
     * Get search suggestions
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_search_suggestions($request)
    {
        $query = $request->get_param('query');
        $post_type = $request->get_param('post_type');

        if (strlen($query) < 2) {
            return new \WP_REST_Response(['suggestions' => []], 200);
        }

        $suggestions = DataUtility::get_search_suggestions($query, $post_type, 5);

        return new \WP_REST_Response(['suggestions' => $suggestions], 200);
    }

    /**
     * Get export arguments
     *
     * @return array
     */
    private function get_export_args()
    {
        return [
            'format' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['csv', 'excel'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'columns' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'default' => ['id', 'title', 'status', 'author', 'date'],
            ],
            'filename' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_file_name',
            ],
            // Include all filter parameters
            'search' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'post_type' => [
                'type' => 'string',
                'default' => 'post',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'author' => [
                'type' => ['string', 'array'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'status' => [
                'type' => ['string', 'array'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'category' => [
                'type' => ['string', 'array'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'tag' => [
                'type' => ['string', 'array'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'date_from' => [
                'type' => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type' => 'string',
                'format' => 'date',
            ],
        ];
    }

    /**
     * Get export headers
     *
     * @param array $columns
     * @return array
     */
    private function get_export_headers($columns)
    {
        $headers_map = [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'excerpt' => 'Excerpt',
            'status' => 'Status',
            'author' => 'Author',
            'date' => 'Date',
            'modified' => 'Modified Date',
            'categories' => 'Categories',
            'tags' => 'Tags',
            'featured_image' => 'Featured Image',
            'post_type' => 'Post Type',
            'comment_count' => 'Comment Count',
        ];

        return array_map(function($column) use ($headers_map) {
            return $headers_map[$column] ?? ucfirst(str_replace('_', ' ', $column));
        }, $columns);
    }

    /**
     * Get export column value
     *
     * @param array $post_data
     * @param string $column
     * @return string
     */
    private function get_export_column_value($post_data, $column)
    {
        switch ($column) {
            case 'id':
                return $post_data['id'];
            case 'title':
                return $post_data['title'];
            case 'content':
                return wp_strip_all_tags($post_data['content'] ?? '');
            case 'excerpt':
                return wp_strip_all_tags($post_data['excerpt'] ?? '');
            case 'status':
                return $post_data['status_label'];
            case 'author':
                return $post_data['author'];
            case 'date':
                return $post_data['date_formatted'];
            case 'modified':
                return $post_data['modified_formatted'] ?? '';
            case 'categories':
                return is_array($post_data['categories']) ? implode(', ', array_column($post_data['categories'], 'name')) : '';
            case 'tags':
                return is_array($post_data['tags']) ? implode(', ', array_column($post_data['tags'], 'name')) : '';
            case 'featured_image':
                return $post_data['featured_image_url'] ?? '';
            case 'post_type':
                return $post_data['post_type'] ?? 'post';
            case 'comment_count':
                return $post_data['comment_count'] ?? '0';
            default:
                return $post_data[$column] ?? '';
        }
    }

    /**
     * Generate export file
     *
     * @param array $data
     * @param string $format
     * @param string $filename
     * @return string|false
     */
    private function generate_export_file($data, $format, $filename)
    {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/uadt-exports';

        // Create export directory if it doesn't exist
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $file_extension = $format === 'excel' ? 'xlsx' : 'csv';
        $file_path = $export_dir . '/' . $filename . '.' . $file_extension;

        if ($format === 'csv') {
            return $this->generate_csv_file($data, $file_path);
        } else {
            return $this->generate_excel_file($data, $file_path);
        }
    }

    /**
     * Generate CSV file
     *
     * @param array $data
     * @param string $file_path
     * @return string|false
     */
    private function generate_csv_file($data, $file_path)
    {
        $file = fopen($file_path, 'w');

        if (!$file) {
            return false;
        }

        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
        return $file_path;
    }

    /**
     * Generate Excel file (simplified - creates CSV with .xlsx extension)
     * For full Excel support, would need PhpSpreadsheet library
     *
     * @param array $data
     * @param string $file_path
     * @return string|false
     */
    private function generate_excel_file($data, $file_path)
    {
        // For now, generate CSV with Excel extension
        // In production, you'd want to use PhpSpreadsheet for proper Excel files
        return $this->generate_csv_file($data, $file_path);
    }
}
