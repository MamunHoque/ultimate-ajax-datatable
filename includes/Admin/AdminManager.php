<?php
/**
 * Admin Manager Class
 *
 * @package UltimateAjaxDataTable\Admin
 * @since 1.0.0
 */

namespace UltimateAjaxDataTable\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AdminManager class
 */
class AdminManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_init', [$this, 'handle_test_data_actions']);

        // Add data table to standard WordPress posts page
        add_action('load-edit.php', [$this, 'maybe_replace_posts_table']);
        add_action('admin_footer-edit.php', [$this, 'add_posts_page_integration']);
    }

    /**
     * Initialize admin functionality
     */
    public function admin_init()
    {
        // Register settings
        register_setting('uadt_settings', 'uadt_enabled_post_types');
        register_setting('uadt_settings', 'uadt_items_per_page');
        register_setting('uadt_settings', 'uadt_enable_search');
        register_setting('uadt_settings', 'uadt_enable_filters');
        register_setting('uadt_settings', 'uadt_enable_bulk_actions');
        register_setting('uadt_settings', 'uadt_max_items_per_page');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('DataTable Manager', 'ultimate-ajax-datatable'),
            __('DataTable Manager', 'ultimate-ajax-datatable'),
            'manage_options',
            'uadt-manager',
            [$this, 'admin_page'],
            'dashicons-list-view',
            30
        );

        add_submenu_page(
            'uadt-manager',
            __('Settings', 'ultimate-ajax-datatable'),
            __('Settings', 'ultimate-ajax-datatable'),
            'manage_options',
            'uadt-settings',
            [$this, 'settings_page']
        );

        add_submenu_page(
            'uadt-manager',
            __('Test Data', 'ultimate-ajax-datatable'),
            __('Test Data', 'ultimate-ajax-datatable'),
            'manage_options',
            'uadt-test-data',
            [$this, 'render_test_data_page']
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Load on our admin pages or edit.php pages for enabled post types
        $should_load = false;

        if (strpos($hook, 'uadt-') !== false) {
            $should_load = true;
        } elseif ($hook === 'edit.php') {
            global $typenow;
            $enabled_post_types = get_option('uadt_enabled_post_types', ['post']);
            if (in_array($typenow, $enabled_post_types)) {
                $should_load = true;
            }
        }

        if (!$should_load) {
            return;
        }

        // Enqueue WordPress media scripts
        wp_enqueue_media();

        // Enqueue React and ReactDOM from CDN
        wp_enqueue_script(
            'react',
            'https://unpkg.com/react@18/umd/react.production.min.js',
            [],
            '18.2.0',
            true
        );

        wp_enqueue_script(
            'react-dom',
            'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',
            ['react'],
            '18.2.0',
            true
        );

        // Enqueue our admin styles
        wp_enqueue_style(
            'uadt-admin-style',
            UADT_ASSETS_URL . 'css/admin.css',
            [],
            UADT_VERSION
        );

        // Enqueue our React app
        wp_enqueue_script(
            'uadt-admin-app',
            UADT_ASSETS_URL . 'js/admin-app.js',
            ['react', 'react-dom'],
            UADT_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('uadt-admin-app', 'uadtAdmin', [
            'apiUrl' => rest_url('uadt/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'currentUser' => wp_get_current_user()->ID,
            'capabilities' => [
                'edit_posts' => current_user_can('edit_posts'),
                'delete_posts' => current_user_can('delete_posts'),
                'manage_options' => current_user_can('manage_options'),
            ],
            'settings' => [
                'enabledPostTypes' => get_option('uadt_enabled_post_types', ['post', 'page']),
                'itemsPerPage' => get_option('uadt_items_per_page', 25),
                'enableSearch' => get_option('uadt_enable_search', true),
                'enableFilters' => get_option('uadt_enable_filters', true),
                'enableBulkActions' => get_option('uadt_enable_bulk_actions', true),
                'maxItemsPerPage' => get_option('uadt_max_items_per_page', 100),
            ],
            'strings' => [
                'loading' => __('Loading...', 'ultimate-ajax-datatable'),
                'error' => __('An error occurred', 'ultimate-ajax-datatable'),
                'noResults' => __('No results found', 'ultimate-ajax-datatable'),
                'search' => __('Search...', 'ultimate-ajax-datatable'),
                'filters' => __('Filters', 'ultimate-ajax-datatable'),
                'clearFilters' => __('Clear Filters', 'ultimate-ajax-datatable'),
                'bulkActions' => __('Bulk Actions', 'ultimate-ajax-datatable'),
                'selectAll' => __('Select All', 'ultimate-ajax-datatable'),
                'export' => __('Export', 'ultimate-ajax-datatable'),
            ]
        ]);
    }

    /**
     * Main admin page
     */
    public function admin_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DataTable Manager', 'ultimate-ajax-datatable'); ?></h1>
            <div id="uadt-admin-app"></div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        $enabled_post_types = get_option('uadt_enabled_post_types', ['post', 'page']);
        $items_per_page = get_option('uadt_items_per_page', 25);
        $enable_search = get_option('uadt_enable_search', true);
        $enable_filters = get_option('uadt_enable_filters', true);
        $enable_bulk_actions = get_option('uadt_enable_bulk_actions', true);
        $max_items_per_page = get_option('uadt_max_items_per_page', 100);

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DataTable Settings', 'ultimate-ajax-datatable'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('uadt_settings_nonce', 'uadt_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enabled Post Types', 'ultimate-ajax-datatable'); ?></th>
                        <td>
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            foreach ($post_types as $post_type) {
                                $checked = in_array($post_type->name, $enabled_post_types) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="uadt_enabled_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '> ' . esc_html($post_type->label) . '</label><br>';
                            }
                            ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Items Per Page', 'ultimate-ajax-datatable'); ?></th>
                        <td>
                            <select name="uadt_items_per_page">
                                <option value="25" <?php selected($items_per_page, 25); ?>>25</option>
                                <option value="50" <?php selected($items_per_page, 50); ?>>50</option>
                                <option value="100" <?php selected($items_per_page, 100); ?>>100</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Search', 'ultimate-ajax-datatable'); ?></th>
                        <td>
                            <input type="checkbox" name="uadt_enable_search" value="1" <?php checked($enable_search); ?>>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Filters', 'ultimate-ajax-datatable'); ?></th>
                        <td>
                            <input type="checkbox" name="uadt_enable_filters" value="1" <?php checked($enable_filters); ?>>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Bulk Actions', 'ultimate-ajax-datatable'); ?></th>
                        <td>
                            <input type="checkbox" name="uadt_enable_bulk_actions" value="1" <?php checked($enable_bulk_actions); ?>>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings()
    {
        if (!wp_verify_nonce($_POST['uadt_settings_nonce'], 'uadt_settings_nonce')) {
            wp_die(__('Security check failed', 'ultimate-ajax-datatable'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page', 'ultimate-ajax-datatable'));
        }

        $enabled_post_types = isset($_POST['uadt_enabled_post_types']) ? array_map('sanitize_text_field', $_POST['uadt_enabled_post_types']) : [];
        $items_per_page = isset($_POST['uadt_items_per_page']) ? intval($_POST['uadt_items_per_page']) : 25;
        $enable_search = isset($_POST['uadt_enable_search']) ? 1 : 0;
        $enable_filters = isset($_POST['uadt_enable_filters']) ? 1 : 0;
        $enable_bulk_actions = isset($_POST['uadt_enable_bulk_actions']) ? 1 : 0;

        update_option('uadt_enabled_post_types', $enabled_post_types);
        update_option('uadt_items_per_page', $items_per_page);
        update_option('uadt_enable_search', $enable_search);
        update_option('uadt_enable_filters', $enable_filters);
        update_option('uadt_enable_bulk_actions', $enable_bulk_actions);

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'ultimate-ajax-datatable') . '</p></div>';
        });
    }

    /**
     * Check if we should replace the posts table on edit.php
     */
    public function maybe_replace_posts_table()
    {
        global $typenow;

        // Only apply to enabled post types
        $enabled_post_types = get_option('uadt_enabled_post_types', ['post']);

        if (!in_array($typenow, $enabled_post_types)) {
            return;
        }

        // Check if user wants to use our data table (add a URL parameter to toggle)
        if (isset($_GET['uadt_mode']) && $_GET['uadt_mode'] === 'enhanced') {
            // Add our enhanced mode
            add_action('admin_notices', [$this, 'show_enhanced_mode_notice']);
        } else {
            // Add option to switch to enhanced mode
            add_action('admin_notices', [$this, 'show_enhanced_mode_option']);
        }
    }

    /**
     * Add our data table integration to the posts page
     */
    public function add_posts_page_integration()
    {
        global $typenow;

        // Only apply to enabled post types
        $enabled_post_types = get_option('uadt_enabled_post_types', ['post']);

        if (!in_array($typenow, $enabled_post_types)) {
            return;
        }

        // Only show enhanced mode if requested
        if (!isset($_GET['uadt_mode']) || $_GET['uadt_mode'] !== 'enhanced') {
            return;
        }

        ?>
        <style>
        .uadt-posts-page-app {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }

        .uadt-posts-header {
            padding: 20px;
            border-bottom: 1px solid #c3c4c7;
            background: #f6f7f7;
            border-radius: 4px 4px 0 0;
        }

        .uadt-search-input {
            padding: 8px 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 14px;
            width: 300px;
            box-shadow: 0 0 0 transparent;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .uadt-search-input:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: 2px solid transparent;
        }

        .uadt-posts-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        .uadt-posts-table th {
            background: #f6f7f7;
            border-bottom: 1px solid #c3c4c7;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #1d2327;
        }

        .uadt-posts-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #c3c4c7;
            vertical-align: top;
        }

        .uadt-posts-table tbody tr:hover {
            background: #f6f7f7;
        }

        .uadt-post-title {
            font-weight: 600;
            color: #1d2327;
            text-decoration: none;
            font-size: 14px;
        }

        .uadt-post-title:hover {
            color: #135e96;
        }

        .uadt-post-excerpt {
            color: #646970;
            font-size: 13px;
            margin-top: 4px;
            line-height: 1.4;
        }

        .uadt-status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .uadt-status-publish {
            background: #00a32a;
            color: white;
        }

        .uadt-status-draft {
            background: #dba617;
            color: white;
        }

        .uadt-status-private {
            background: #2271b1;
            color: white;
        }

        .uadt-action-button {
            padding: 4px 8px;
            border: 1px solid #2271b1;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }

        .uadt-action-button:hover {
            background: #135e96;
            border-color: #135e96;
            color: white;
        }

        .uadt-action-button.secondary {
            background: #f6f7f7;
            border-color: #c3c4c7;
            color: #2c3338;
        }

        .uadt-action-button.secondary:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
            color: #2c3338;
        }

        .uadt-pagination {
            padding: 15px 20px;
            border-top: 1px solid #c3c4c7;
            background: #f6f7f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 4px 4px;
        }

        .uadt-pagination-info {
            color: #646970;
            font-size: 14px;
        }

        .uadt-pagination-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .uadt-pagination-button {
            padding: 6px 12px;
            border: 1px solid #c3c4c7;
            background: #f6f7f7;
            color: #2c3338;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            cursor: pointer;
        }

        .uadt-pagination-button:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
            color: #2c3338;
        }

        .uadt-pagination-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .uadt-loading-container {
            padding: 60px 20px;
            text-align: center;
            color: #646970;
        }

        .uadt-error-container {
            padding: 40px 20px;
            text-align: center;
            color: #d63638;
            background: #fcf0f1;
            border: 1px solid #f0a5a8;
            border-radius: 4px;
            margin: 20px;
        }

        .uadt-empty-container {
            padding: 60px 20px;
            text-align: center;
            color: #646970;
        }
        </style>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Hide the default posts table and related elements
            $('.wp-list-table').hide();
            $('.tablenav').hide();
            $('.search-box').hide();
            $('.subsubsub').hide();

            // Add our enhanced data table container
            $('.wrap h1').after('<div id="uadt-posts-integration"></div>');

            // Initialize our React app in the new container
            if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined') {
                initializePostsPageApp();
            }
        });

        function initializePostsPageApp() {
            const { useState, useEffect } = React;
            const container = document.getElementById('uadt-posts-integration');

            function PostsPageApp() {
                const [posts, setPosts] = useState([]);
                const [loading, setLoading] = useState(true);
                const [error, setError] = useState(null);
                const [filters, setFilters] = useState({
                    search: '',
                    page: 1,
                    per_page: 25,
                    post_type: '<?php echo esc_js($typenow); ?>'
                });
                const [totalPages, setTotalPages] = useState(1);
                const [total, setTotal] = useState(0);

                useEffect(() => {
                    loadPosts();
                }, [filters]);

                const loadPosts = async () => {
                    try {
                        setLoading(true);
                        const response = await window.uadtAPI.getPosts(filters);
                        setPosts(response.posts || []);
                        setTotalPages(response.pages || 1);
                        setTotal(response.total || 0);
                        setError(null);
                    } catch (err) {
                        setError('Failed to load posts: ' + err.message);
                        console.error('Error loading posts:', err);
                    } finally {
                        setLoading(false);
                    }
                };

                const handleSearchChange = (e) => {
                    setFilters(prev => ({
                        ...prev,
                        search: e.target.value,
                        page: 1
                    }));
                };

                const handlePageChange = (newPage) => {
                    setFilters(prev => ({
                        ...prev,
                        page: newPage
                    }));
                };

                return React.createElement('div', { className: 'uadt-posts-page-app' },
                    // Header with search
                    React.createElement('div', { className: 'uadt-posts-header' },
                        React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                            React.createElement('div', { style: { display: 'flex', alignItems: 'center', gap: '15px' } },
                                React.createElement('input', {
                                    type: 'text',
                                    placeholder: 'Search posts...',
                                    value: filters.search,
                                    onChange: handleSearchChange,
                                    className: 'uadt-search-input'
                                }),
                                React.createElement('span', { className: 'uadt-pagination-info' },
                                    `${total} items found`
                                )
                            ),
                            React.createElement('a', {
                                href: window.location.pathname + window.location.search.replace(/[?&]uadt_mode=enhanced/, ''),
                                className: 'button',
                                style: { textDecoration: 'none' }
                            }, 'Switch to Standard View')
                        )
                    ),

                    // Content
                    loading ?
                        React.createElement('div', { className: 'uadt-loading-container' },
                            React.createElement('div', { style: { fontSize: '16px' } }, 'Loading posts...')
                        ) :
                    error ?
                        React.createElement('div', { className: 'uadt-error-container' }, error) :
                        React.createElement('div', null,
                            // Table
                            React.createElement('table', { className: 'uadt-posts-table' },
                                React.createElement('thead', null,
                                    React.createElement('tr', null,
                                        React.createElement('th', { style: { width: '50%' } }, 'Title'),
                                        React.createElement('th', null, 'Author'),
                                        React.createElement('th', null, 'Status'),
                                        React.createElement('th', null, 'Date'),
                                        React.createElement('th', null, 'Actions')
                                    )
                                ),
                                React.createElement('tbody', null,
                                    posts.length === 0 ?
                                        React.createElement('tr', null,
                                            React.createElement('td', { colSpan: 5, className: 'uadt-empty-container' }, 'No posts found')
                                        ) :
                                        posts.map(post =>
                                            React.createElement('tr', { key: post.id },
                                                React.createElement('td', null,
                                                    React.createElement('a', {
                                                        href: post.edit_link || '#',
                                                        className: 'uadt-post-title'
                                                    }, post.title || '(No title)'),
                                                    post.excerpt ? React.createElement('div', { className: 'uadt-post-excerpt' },
                                                        post.excerpt.substring(0, 120) + (post.excerpt.length > 120 ? '...' : '')
                                                    ) : null
                                                ),
                                                React.createElement('td', null, post.author),
                                                React.createElement('td', null,
                                                    React.createElement('span', {
                                                        className: `uadt-status-badge uadt-status-${post.status}`
                                                    }, post.status_label)
                                                ),
                                                React.createElement('td', null, post.date_formatted),
                                                React.createElement('td', null,
                                                    React.createElement('div', { style: { display: 'flex', gap: '5px' } },
                                                        post.edit_link ?
                                                            React.createElement('a', {
                                                                href: post.edit_link,
                                                                className: 'uadt-action-button'
                                                            }, 'Edit') : null,
                                                        post.view_link ?
                                                            React.createElement('a', {
                                                                href: post.view_link,
                                                                className: 'uadt-action-button secondary',
                                                                target: '_blank'
                                                            }, 'View') : null
                                                    )
                                                )
                                            )
                                        )
                                )
                            ),

                            // Pagination
                            totalPages > 1 ? React.createElement('div', { className: 'uadt-pagination' },
                                React.createElement('div', { className: 'uadt-pagination-info' },
                                    `Showing page ${filters.page} of ${totalPages} (${total} total items)`
                                ),
                                React.createElement('div', { className: 'uadt-pagination-controls' },
                                    React.createElement('button', {
                                        className: 'uadt-pagination-button',
                                        onClick: () => handlePageChange(filters.page - 1),
                                        disabled: filters.page <= 1
                                    }, '‹ Previous'),
                                    React.createElement('span', { style: { margin: '0 10px', fontSize: '14px' } },
                                        `Page ${filters.page} of ${totalPages}`
                                    ),
                                    React.createElement('button', {
                                        className: 'uadt-pagination-button',
                                        onClick: () => handlePageChange(filters.page + 1),
                                        disabled: filters.page >= totalPages
                                    }, 'Next ›')
                                )
                            ) : null
                        )
                );
            }

            const root = ReactDOM.createRoot(container);
            root.render(React.createElement(PostsPageApp));
        }
        </script>
        <?php
    }

    /**
     * Show notice when in enhanced mode
     */
    public function show_enhanced_mode_notice()
    {
        echo '<div class="notice notice-info"><p>';
        echo '<strong>Enhanced DataTable Mode Active</strong> - You are viewing posts with the Ultimate Ajax DataTable enhanced interface. ';
        echo '<a href="' . esc_url(remove_query_arg('uadt_mode')) . '">Switch back to standard view</a>';
        echo '</p></div>';
    }

    /**
     * Show option to switch to enhanced mode
     */
    public function show_enhanced_mode_option()
    {
        echo '<div class="notice notice-info is-dismissible"><p>';
        echo '<strong>Ultimate Ajax DataTable Available</strong> - Try our enhanced posts management interface with advanced filtering and search. ';
        echo '<a href="' . esc_url(add_query_arg('uadt_mode', 'enhanced')) . '" class="button button-primary">Try Enhanced View</a>';
        echo '</p></div>';
    }

    /**
     * Render test data page
     */
    public function render_test_data_page()
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Test Data Generator', 'ultimate-ajax-datatable'); ?></h1>

            <?php
            // Show success/error messages
            if (isset($_GET['message'])) {
                $message = sanitize_text_field($_GET['message']);
                if ($message === 'posts_created') {
                    echo '<div class="notice notice-success"><p>Test posts created successfully!</p></div>';
                } elseif ($message === 'posts_deleted') {
                    echo '<div class="notice notice-success"><p>Test posts deleted successfully!</p></div>';
                } elseif ($message === 'error') {
                    echo '<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>';
                }
            }
            ?>

            <div class="card" style="max-width: 800px;">
                <h2>Generate Test Posts</h2>
                <p>Create sample posts to test the DataTable functionality. This will help you see how the plugin works with a larger dataset.</p>

                <form method="post" action="">
                    <?php wp_nonce_field('uadt_test_data', 'uadt_test_data_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Number of Posts</th>
                            <td>
                                <input type="number" name="post_count" value="50" min="1" max="200" class="regular-text" />
                                <p class="description">Number of test posts to create (1-200)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Include Categories & Tags</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_terms" value="1" checked />
                                    Create test categories and tags
                                </label>
                                <p class="description">This will create sample categories and tags and assign them to posts</p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="create_test_posts" class="button button-primary" value="Create Test Posts" />
                    </p>
                </form>

                <hr />

                <h3>Cleanup Test Data</h3>
                <p>Remove all test posts created by this tool. This will only delete posts marked as test data.</p>

                <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete all test posts? This action cannot be undone.');">
                    <?php wp_nonce_field('uadt_test_data', 'uadt_test_data_nonce'); ?>
                    <p class="submit">
                        <input type="submit" name="cleanup_test_posts" class="button button-secondary" value="Delete All Test Posts" />
                    </p>
                </form>

                <hr />

                <h3>Current Status</h3>
                <?php
                $test_posts = get_posts([
                    'post_type' => 'post',
                    'post_status' => 'any',
                    'numberposts' => -1,
                    'meta_key' => '_test_post',
                    'meta_value' => true,
                ]);

                $total_posts = wp_count_posts('post');
                ?>
                <p><strong>Total Posts:</strong> <?php echo esc_html($total_posts->publish + $total_posts->draft + $total_posts->private); ?></p>
                <p><strong>Test Posts:</strong> <?php echo esc_html(count($test_posts)); ?></p>

                <?php if (count($test_posts) > 0): ?>
                    <p><a href="<?php echo esc_url(admin_url('edit.php?uadt_mode=enhanced')); ?>" class="button">View Posts in Enhanced Mode</a></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Handle test data actions
     */
    public function handle_test_data_actions()
    {
        if (!isset($_POST['uadt_test_data_nonce']) || !wp_verify_nonce($_POST['uadt_test_data_nonce'], 'uadt_test_data')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['create_test_posts'])) {
            $post_count = isset($_POST['post_count']) ? intval($_POST['post_count']) : 50;
            $post_count = max(1, min(200, $post_count)); // Limit between 1-200

            $include_terms = isset($_POST['include_terms']);

            try {
                // Include the DataSeeder class
                require_once UADT_PLUGIN_DIR . 'includes/Utils/DataSeeder.php';

                if ($include_terms) {
                    \UltimateAjaxDataTable\Utils\DataSeeder::create_test_categories();
                    \UltimateAjaxDataTable\Utils\DataSeeder::create_test_tags();
                }

                $created_posts = \UltimateAjaxDataTable\Utils\DataSeeder::create_test_posts($post_count);

                if (count($created_posts) > 0) {
                    wp_redirect(admin_url('admin.php?page=uadt-test-data&message=posts_created'));
                    exit;
                }
            } catch (Exception $e) {
                wp_redirect(admin_url('admin.php?page=uadt-test-data&message=error'));
                exit;
            }
        }

        if (isset($_POST['cleanup_test_posts'])) {
            try {
                require_once UADT_PLUGIN_DIR . 'includes/Utils/DataSeeder.php';
                $deleted_count = \UltimateAjaxDataTable\Utils\DataSeeder::cleanup_test_posts();

                wp_redirect(admin_url('admin.php?page=uadt-test-data&message=posts_deleted'));
                exit;
            } catch (Exception $e) {
                wp_redirect(admin_url('admin.php?page=uadt-test-data&message=error'));
                exit;
            }
        }
    }
}
