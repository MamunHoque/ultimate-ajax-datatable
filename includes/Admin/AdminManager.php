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
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            margin-top: 20px;
            overflow: hidden;
        }

        .uadt-posts-header {
            padding: 24px;
            border-bottom: 1px solid #e1e1e1;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
            border-radius: 8px 8px 0 0;
        }

        .uadt-header-title {
            font-size: 18px;
            font-weight: 600;
            color: #1d2327;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .uadt-header-title::before {
            content: "⚡";
            font-size: 20px;
        }

        .uadt-search-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .uadt-search-input {
            padding: 12px 16px 12px 44px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            width: 320px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            transition: all 0.2s ease;
        }

        .uadt-search-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0,115,170,.1);
            outline: none;
        }

        .uadt-search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #8c8f94;
            font-size: 16px;
        }

        .uadt-search-input:focus + .uadt-search-icon {
            color: #0073aa;
        }

        .uadt-table-container {
            overflow: hidden;
        }

        .uadt-posts-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 14px;
        }

        .uadt-posts-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
            border-bottom: 2px solid #e1e1e1;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #1d2327;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .uadt-posts-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: top;
        }

        .uadt-posts-table tbody tr {
            transition: all 0.2s ease;
        }

        .uadt-posts-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .uadt-post-title {
            font-weight: 600;
            color: #1d2327;
            text-decoration: none;
            font-size: 15px;
            line-height: 1.4;
            display: block;
            margin-bottom: 4px;
            transition: color 0.2s ease;
        }

        .uadt-post-title:hover {
            color: #0073aa;
        }

        .uadt-post-excerpt {
            color: #646970;
            font-size: 13px;
            line-height: 1.5;
            margin-top: 6px;
            opacity: 0.9;
        }

        .uadt-post-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 12px;
            color: #8c8f94;
        }

        .uadt-post-id {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        .uadt-status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }

        .uadt-status-publish {
            background: linear-gradient(135deg, #00a32a 0%, #00d084 100%);
            color: white;
        }

        .uadt-status-publish::before {
            content: "●";
            color: #90ee90;
        }

        .uadt-status-draft {
            background: linear-gradient(135deg, #dba617 0%, #ffb900 100%);
            color: white;
        }

        .uadt-status-draft::before {
            content: "◐";
            color: #fff3cd;
        }

        .uadt-status-private {
            background: linear-gradient(135deg, #2271b1 0%, #0073aa 100%);
            color: white;
        }

        .uadt-status-private::before {
            content: "🔒";
            font-size: 10px;
        }

        .uadt-actions-container {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .uadt-action-button {
            padding: 8px 14px;
            border: 2px solid transparent;
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }

        .uadt-action-button:hover {
            background: linear-gradient(135deg, #005177 0%, #003d5c 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
        }

        .uadt-action-button.secondary {
            background: linear-gradient(135deg, #f6f7f7 0%, #e8e9ea 100%);
            border-color: #c3c4c7;
            color: #2c3338;
        }

        .uadt-action-button.secondary:hover {
            background: linear-gradient(135deg, #e8e9ea 0%, #dcdcde 100%);
            border-color: #8c8f94;
            color: #2c3338;
        }

        .uadt-action-button::before {
            font-size: 10px;
        }

        .uadt-action-button:not(.secondary)::before {
            content: "✏️";
        }

        .uadt-action-button.secondary::before {
            content: "👁️";
        }

        .uadt-pagination {
            padding: 20px 24px;
            border-top: 2px solid #e1e1e1;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0 0 8px 8px;
        }

        .uadt-pagination-info {
            color: #646970;
            font-size: 14px;
            font-weight: 500;
        }

        .uadt-pagination-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .uadt-pagination-button {
            padding: 10px 16px;
            border: 2px solid #e1e1e1;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            color: #2c3338;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }

        .uadt-pagination-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
            border-color: #0073aa;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
        }

        .uadt-pagination-button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f1f1f1;
            border-color: #ddd;
        }

        .uadt-page-info {
            padding: 8px 12px;
            background: rgba(0,115,170,.1);
            border-radius: 4px;
            font-weight: 600;
            color: #0073aa;
        }

        .uadt-loading-container {
            padding: 80px 20px;
            text-align: center;
            color: #646970;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        }

        .uadt-loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e1e1e1;
            border-top: 4px solid #0073aa;
            border-radius: 50%;
            animation: uadt-spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes uadt-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .uadt-error-container {
            padding: 40px 20px;
            text-align: center;
            color: #d63638;
            background: linear-gradient(135deg, #fcf0f1 0%, #fde7e9 100%);
            border: 2px solid #f0a5a8;
            border-radius: 8px;
            margin: 20px;
        }

        .uadt-empty-container {
            padding: 80px 20px;
            text-align: center;
            color: #646970;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
        }

        .uadt-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .uadt-stats-container {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
        }

        .uadt-stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: rgba(0,115,170,.1);
            border-radius: 4px;
            color: #0073aa;
            font-weight: 500;
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
                        React.createElement('h2', { className: 'uadt-header-title' }, 'Enhanced Posts Manager'),
                        React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                            React.createElement('div', { className: 'uadt-search-container' },
                                React.createElement('input', {
                                    type: 'text',
                                    placeholder: 'Search posts by title, content, or author...',
                                    value: filters.search,
                                    onChange: handleSearchChange,
                                    className: 'uadt-search-input'
                                }),
                                React.createElement('span', { className: 'uadt-search-icon' }, '🔍'),
                                React.createElement('div', { className: 'uadt-stats-container' },
                                    React.createElement('div', { className: 'uadt-stat-item' },
                                        React.createElement('span', null, '📊'),
                                        `${total} items`
                                    ),
                                    React.createElement('div', { className: 'uadt-stat-item' },
                                        React.createElement('span', null, '📄'),
                                        `Page ${filters.page}`
                                    )
                                )
                            ),
                            React.createElement('a', {
                                href: window.location.pathname + window.location.search.replace(/[?&]uadt_mode=enhanced/, ''),
                                className: 'button button-secondary',
                                style: { textDecoration: 'none' }
                            }, '← Standard View')
                        )
                    ),

                    // Content
                    loading ?
                        React.createElement('div', { className: 'uadt-loading-container' },
                            React.createElement('div', { className: 'uadt-loading-spinner' }),
                            React.createElement('div', { style: { fontSize: '16px', fontWeight: '500' } }, 'Loading posts...'),
                            React.createElement('div', { style: { fontSize: '14px', marginTop: '8px', opacity: '0.7' } }, 'Please wait while we fetch your content')
                        ) :
                    error ?
                        React.createElement('div', { className: 'uadt-error-container' },
                            React.createElement('div', { style: { fontSize: '24px', marginBottom: '12px' } }, '⚠️'),
                            React.createElement('div', { style: { fontSize: '16px', fontWeight: '600', marginBottom: '8px' } }, 'Error Loading Posts'),
                            React.createElement('div', null, error)
                        ) :
                        React.createElement('div', { className: 'uadt-table-container' },
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
                                            React.createElement('td', { colSpan: 5, className: 'uadt-empty-container' },
                                                React.createElement('div', { className: 'uadt-empty-icon' }, '📝'),
                                                React.createElement('div', { style: { fontSize: '16px', fontWeight: '500', marginBottom: '8px' } }, 'No posts found'),
                                                React.createElement('div', { style: { fontSize: '14px', opacity: '0.7' } }, 'Try adjusting your search criteria')
                                            )
                                        ) :
                                        posts.map(post =>
                                            React.createElement('tr', { key: post.id },
                                                React.createElement('td', { style: { width: '45%' } },
                                                    React.createElement('a', {
                                                        href: post.edit_link || '#',
                                                        className: 'uadt-post-title'
                                                    }, post.title || '(No title)'),
                                                    post.excerpt ? React.createElement('div', { className: 'uadt-post-excerpt' },
                                                        post.excerpt.substring(0, 140) + (post.excerpt.length > 140 ? '...' : '')
                                                    ) : null,
                                                    React.createElement('div', { className: 'uadt-post-meta' },
                                                        React.createElement('span', { className: 'uadt-post-id' }, `ID: ${post.id}`),
                                                        React.createElement('span', null, '•'),
                                                        React.createElement('span', null, `${post.post_type || 'post'}`)
                                                    )
                                                ),
                                                React.createElement('td', { style: { width: '15%' } },
                                                    React.createElement('div', { style: { fontWeight: '500' } }, post.author)
                                                ),
                                                React.createElement('td', { style: { width: '12%' } },
                                                    React.createElement('span', {
                                                        className: `uadt-status-badge uadt-status-${post.status}`
                                                    }, post.status_label)
                                                ),
                                                React.createElement('td', { style: { width: '15%' } },
                                                    React.createElement('div', { style: { fontSize: '13px' } }, post.date_formatted),
                                                    React.createElement('div', { style: { fontSize: '12px', color: '#8c8f94', marginTop: '2px' } },
                                                        `Modified: ${post.modified_formatted || 'N/A'}`
                                                    )
                                                ),
                                                React.createElement('td', { style: { width: '13%' } },
                                                    React.createElement('div', { className: 'uadt-actions-container' },
                                                        post.edit_link ?
                                                            React.createElement('a', {
                                                                href: post.edit_link,
                                                                className: 'uadt-action-button',
                                                                title: 'Edit this post'
                                                            }, 'Edit') : null,
                                                        post.view_link ?
                                                            React.createElement('a', {
                                                                href: post.view_link,
                                                                className: 'uadt-action-button secondary',
                                                                target: '_blank',
                                                                title: 'View this post'
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
                                    `Showing ${((filters.page - 1) * filters.per_page) + 1}-${Math.min(filters.page * filters.per_page, total)} of ${total} items`
                                ),
                                React.createElement('div', { className: 'uadt-pagination-controls' },
                                    React.createElement('button', {
                                        className: 'uadt-pagination-button',
                                        onClick: () => handlePageChange(filters.page - 1),
                                        disabled: filters.page <= 1,
                                        title: 'Previous page'
                                    }, '← Previous'),
                                    React.createElement('span', { className: 'uadt-page-info' },
                                        `Page ${filters.page} of ${totalPages}`
                                    ),
                                    React.createElement('button', {
                                        className: 'uadt-pagination-button',
                                        onClick: () => handlePageChange(filters.page + 1),
                                        disabled: filters.page >= totalPages,
                                        title: 'Next page'
                                    }, 'Next →')
                                )
                            ) : React.createElement('div', { className: 'uadt-pagination' },
                                React.createElement('div', { className: 'uadt-pagination-info' },
                                    `Showing all ${total} items`
                                ),
                                React.createElement('div', null)
                            )
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
