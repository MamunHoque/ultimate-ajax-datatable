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
        // General Settings
        register_setting('uadt_settings', 'uadt_enabled_post_types', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_post_types'],
            'default' => ['post']
        ]);

        register_setting('uadt_settings', 'uadt_items_per_page', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_items_per_page'],
            'default' => 25
        ]);

        register_setting('uadt_settings', 'uadt_max_items_per_page', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_max_items_per_page'],
            'default' => 100
        ]);

        // Feature Settings
        register_setting('uadt_settings', 'uadt_enable_search', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('uadt_settings', 'uadt_enable_filters', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('uadt_settings', 'uadt_enable_bulk_actions', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('uadt_settings', 'uadt_enable_export', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('uadt_settings', 'uadt_enable_presets', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        // Column Visibility Settings
        register_setting('uadt_settings', 'uadt_visible_columns', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_visible_columns'],
            'default' => ['title', 'author', 'categories', 'tags', 'date', 'status']
        ]);

        // Default Filter Settings
        register_setting('uadt_settings', 'uadt_default_status', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('uadt_settings', 'uadt_default_author', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);

        register_setting('uadt_settings', 'uadt_default_orderby', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'date'
        ]);

        register_setting('uadt_settings', 'uadt_default_order', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_order'],
            'default' => 'DESC'
        ]);

        // Performance Settings
        register_setting('uadt_settings', 'uadt_enable_caching', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('uadt_settings', 'uadt_cache_duration', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_cache_duration'],
            'default' => 300
        ]);

        // UI Settings
        register_setting('uadt_settings', 'uadt_show_enhanced_notice', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ]);

        register_setting('uadt_settings', 'uadt_auto_enhanced_mode', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        // Add top-level menu page for settings
        add_menu_page(
            __('Ultimate Ajax DataTable', 'ultimate-ajax-datatable'),
            __('Ajax DataTable', 'ultimate-ajax-datatable'),
            'manage_options',
            'uadt-settings',
            [$this, 'settings_page'],
            'dashicons-list-view',
            30
        );

        // Add submenu for test data
        add_submenu_page(
            'uadt-settings',
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
     * Settings page
     */
    public function settings_page()
    {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        // Get all settings with defaults
        $settings = $this->get_all_settings();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ultimate Ajax DataTable', 'ultimate-ajax-datatable'); ?></h1>

            <?php $this->render_settings_tabs($active_tab); ?>

            <form method="post" action="" id="uadt-settings-form">
                <?php wp_nonce_field('uadt_settings_nonce', 'uadt_settings_nonce'); ?>
                <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

                <div class="uadt-settings-content">
                    <?php
                    switch ($active_tab) {
                        case 'general':
                            $this->render_general_settings($settings);
                            break;
                        case 'features':
                            $this->render_feature_settings($settings);
                            break;
                        case 'columns':
                            $this->render_column_settings($settings);
                            break;
                        case 'defaults':
                            $this->render_default_settings($settings);
                            break;
                        case 'performance':
                            $this->render_performance_settings($settings);
                            break;
                        case 'advanced':
                            $this->render_advanced_settings($settings);
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(__('Save Settings', 'ultimate-ajax-datatable'), 'primary', 'submit', true, ['id' => 'uadt-save-settings']); ?>
            </form>
        </div>

        <style>
        .uadt-settings-content {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 0;
            padding: 20px;
        }

        .uadt-setting-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .uadt-setting-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .uadt-setting-section h3 {
            margin-top: 0;
            color: #23282d;
            font-size: 16px;
        }

        .uadt-setting-description {
            color: #666;
            font-style: italic;
            margin-bottom: 15px;
        }

        .uadt-checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .uadt-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .uadt-help-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .uadt-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            color: #856404;
        }

        .uadt-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            color: #0c5460;
        }
        </style>
        <?php
    }

    /**
     * Get all settings with defaults
     */
    private function get_all_settings()
    {
        return [
            // General Settings
            'enabled_post_types' => get_option('uadt_enabled_post_types', ['post']),
            'items_per_page' => get_option('uadt_items_per_page', 25),
            'max_items_per_page' => get_option('uadt_max_items_per_page', 100),

            // Feature Settings
            'enable_search' => get_option('uadt_enable_search', true),
            'enable_filters' => get_option('uadt_enable_filters', true),
            'enable_bulk_actions' => get_option('uadt_enable_bulk_actions', true),
            'enable_export' => get_option('uadt_enable_export', true),
            'enable_presets' => get_option('uadt_enable_presets', true),

            // Column Settings
            'visible_columns' => get_option('uadt_visible_columns', ['title', 'author', 'categories', 'tags', 'date', 'status']),

            // Default Filter Settings
            'default_status' => get_option('uadt_default_status', ''),
            'default_author' => get_option('uadt_default_author', ''),
            'default_orderby' => get_option('uadt_default_orderby', 'date'),
            'default_order' => get_option('uadt_default_order', 'DESC'),

            // Performance Settings
            'enable_caching' => get_option('uadt_enable_caching', true),
            'cache_duration' => get_option('uadt_cache_duration', 300),

            // UI Settings
            'show_enhanced_notice' => get_option('uadt_show_enhanced_notice', true),
            'auto_enhanced_mode' => get_option('uadt_auto_enhanced_mode', false),
        ];
    }

    /**
     * Render settings tabs
     */
    private function render_settings_tabs($active_tab)
    {
        $tabs = [
            'general' => __('General', 'ultimate-ajax-datatable'),
            'features' => __('Features', 'ultimate-ajax-datatable'),
            'columns' => __('Columns', 'ultimate-ajax-datatable'),
            'defaults' => __('Defaults', 'ultimate-ajax-datatable'),
            'performance' => __('Performance', 'ultimate-ajax-datatable'),
            'advanced' => __('Advanced', 'ultimate-ajax-datatable'),
        ];

        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $tab_key => $tab_label) {
            $active_class = $active_tab === $tab_key ? ' nav-tab-active' : '';
            $url = add_query_arg(['page' => 'uadt-settings', 'tab' => $tab_key], admin_url('admin.php'));
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . $active_class . '">' . esc_html($tab_label) . '</a>';
        }
        echo '</nav>';
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

        // Save all settings
        $this->save_general_settings();
        $this->save_feature_settings();
        $this->save_column_settings();
        $this->save_default_settings();
        $this->save_performance_settings();
        $this->save_advanced_settings();

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'ultimate-ajax-datatable') . '</p></div>';
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

        // Get plugin settings
        $auto_enhanced_mode = get_option('uadt_auto_enhanced_mode', false);
        $show_enhanced_notice = get_option('uadt_show_enhanced_notice', true);

        // Check if user explicitly wants standard mode
        $force_standard_mode = isset($_GET['uadt_mode']) && $_GET['uadt_mode'] === 'standard';

        // Determine if we should show enhanced mode
        $should_show_enhanced = false;

        if (!$force_standard_mode) {
            if ($auto_enhanced_mode) {
                // Auto enhanced mode is enabled - show enhanced interface unless explicitly overridden
                $should_show_enhanced = true;
            } elseif (isset($_GET['uadt_mode']) && $_GET['uadt_mode'] === 'enhanced') {
                // Manual enhanced mode via URL parameter
                $should_show_enhanced = true;
            }
        }

        if ($should_show_enhanced) {
            // Show enhanced mode without any notices
            // The enhanced interface will be loaded by add_posts_page_integration()
            return;
        }

        // If auto enhanced mode is disabled and no manual request, show standard WordPress interface
        // Only show the "Try Enhanced View" notice if the setting allows it
        if (!$auto_enhanced_mode && $show_enhanced_notice) {
            add_action('admin_notices', [$this, 'show_enhanced_mode_option']);
        }

        // If show_enhanced_notice is disabled, show nothing - just standard WordPress interface
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

        // Get plugin settings
        $auto_enhanced_mode = get_option('uadt_auto_enhanced_mode', false);

        // Check if user explicitly wants standard mode
        $force_standard_mode = isset($_GET['uadt_mode']) && $_GET['uadt_mode'] === 'standard';

        // Determine if we should show enhanced mode
        $should_show_enhanced = false;

        if (!$force_standard_mode) {
            if ($auto_enhanced_mode) {
                // Auto enhanced mode is enabled - show enhanced interface unless explicitly overridden
                $should_show_enhanced = true;
            } elseif (isset($_GET['uadt_mode']) && $_GET['uadt_mode'] === 'enhanced') {
                // Manual enhanced mode via URL parameter
                $should_show_enhanced = true;
            }
        }

        // Only show enhanced mode if conditions are met
        if (!$should_show_enhanced) {
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

        .uadt-top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 4px;
        }

        .uadt-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .uadt-page-title {
            font-size: 23px;
            font-weight: 400;
            margin: 0;
            color: #1d2327;
            line-height: 1.3;
        }

        .uadt-add-new-button {
            display: inline-block;
            padding: 4px 8px;
            font-size: 13px;
            line-height: 2.15384615;
            text-align: center;
            color: #2271b1;
            border: 1px solid #2271b1;
            border-radius: 3px;
            background: #f6f7f7;
            font-weight: 400;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .uadt-add-new-button:hover {
            background: #2271b1;
            color: #fff;
            border-color: #135e96;
        }

        .uadt-enhanced-badge {
            font-size: 12px;
            color: #646970;
            background: rgba(0,115,170,.1);
            padding: 4px 8px;
            border-radius: 3px;
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

            // Hide the original Add New button temporarily
            $('.page-title-action').hide();

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
                    // Top Header with Title and Add New Button
                    React.createElement('div', { className: 'uadt-top-header' },
                        React.createElement('div', { className: 'uadt-title-section' },
                            React.createElement('h1', { className: 'uadt-page-title' }, 'Posts'),
                            React.createElement('a', {
                                href: 'post-new.php',
                                className: 'uadt-add-new-button'
                            }, 'Add New Post')
                        ),
                        React.createElement('a', {
                            href: window.location.pathname + window.location.search.replace(/[?&]uadt_mode=enhanced/, '') + (window.location.search.includes('?') ? '&' : '?') + 'uadt_mode=standard',
                            className: 'button button-secondary',
                            style: { textDecoration: 'none' }
                        }, '← Standard View')
                    ),

                    // Enhanced Header with search
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
                            React.createElement('div', { style: { display: 'flex', gap: '8px', alignItems: 'center' } },
                                React.createElement('span', { className: 'uadt-enhanced-badge' }, '⚡ Enhanced Mode')
                            )
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

    /**
     * Render general settings tab
     */
    private function render_general_settings($settings)
    {
        ?>
        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Post Type Configuration', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Select which post types should use the enhanced data table interface.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enabled Post Types', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <div class="uadt-checkbox-group">
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            foreach ($post_types as $post_type) {
                                $checked = in_array($post_type->name, $settings['enabled_post_types']) ? 'checked' : '';
                                echo '<div class="uadt-checkbox-item">';
                                echo '<input type="checkbox" name="uadt_enabled_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' id="post_type_' . esc_attr($post_type->name) . '">';
                                echo '<label for="post_type_' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <p class="uadt-help-text"><?php esc_html_e('Select at least one post type to enable the enhanced interface.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Pagination Settings', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Configure how many items are displayed per page.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Default Items Per Page', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_items_per_page">
                            <option value="10" <?php selected($settings['items_per_page'], 10); ?>>10</option>
                            <option value="25" <?php selected($settings['items_per_page'], 25); ?>>25</option>
                            <option value="50" <?php selected($settings['items_per_page'], 50); ?>>50</option>
                            <option value="100" <?php selected($settings['items_per_page'], 100); ?>>100</option>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('Number of posts to display per page by default.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Maximum Items Per Page', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_max_items_per_page">
                            <option value="50" <?php selected($settings['max_items_per_page'], 50); ?>>50</option>
                            <option value="100" <?php selected($settings['max_items_per_page'], 100); ?>>100</option>
                            <option value="200" <?php selected($settings['max_items_per_page'], 200); ?>>200</option>
                            <option value="500" <?php selected($settings['max_items_per_page'], 500); ?>>500</option>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('Maximum number of posts that can be displayed per page. Higher values may impact performance.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render feature settings tab
     */
    private function render_feature_settings($settings)
    {
        ?>
        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Core Features', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Enable or disable specific features of the data table.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Search Functionality', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_enable_search" value="1" <?php checked($settings['enable_search']); ?>>
                            <?php esc_html_e('Enable real-time search', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('Allow users to search through posts in real-time.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Advanced Filters', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_enable_filters" value="1" <?php checked($settings['enable_filters']); ?>>
                            <?php esc_html_e('Enable advanced filtering options', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('Provide dropdown filters for author, status, categories, tags, and date ranges.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Bulk Actions', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_enable_bulk_actions" value="1" <?php checked($settings['enable_bulk_actions']); ?>>
                            <?php esc_html_e('Enable bulk operations', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('Allow users to perform actions on multiple posts at once.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Export Functionality', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_enable_export" value="1" <?php checked($settings['enable_export']); ?>>
                            <?php esc_html_e('Enable data export (CSV/Excel)', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('Allow users to export filtered data to CSV or Excel formats.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Filter Presets', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_enable_presets" value="1" <?php checked($settings['enable_presets']); ?>>
                            <?php esc_html_e('Enable filter presets', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('Allow users to save and load filter combinations.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render column settings tab
     */
    private function render_column_settings($settings)
    {
        $available_columns = [
            'title' => __('Title', 'ultimate-ajax-datatable'),
            'author' => __('Author', 'ultimate-ajax-datatable'),
            'categories' => __('Categories', 'ultimate-ajax-datatable'),
            'tags' => __('Tags', 'ultimate-ajax-datatable'),
            'date' => __('Date', 'ultimate-ajax-datatable'),
            'modified' => __('Modified Date', 'ultimate-ajax-datatable'),
            'status' => __('Status', 'ultimate-ajax-datatable'),
            'comment_count' => __('Comments', 'ultimate-ajax-datatable'),
            'featured_image' => __('Featured Image', 'ultimate-ajax-datatable'),
            'excerpt' => __('Excerpt', 'ultimate-ajax-datatable'),
            'post_id' => __('Post ID', 'ultimate-ajax-datatable'),
            'post_type' => __('Post Type', 'ultimate-ajax-datatable'),
        ];

        ?>
        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Column Visibility', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Choose which columns are visible in the data table by default.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Visible Columns', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <div class="uadt-checkbox-group">
                            <?php foreach ($available_columns as $column_key => $column_label): ?>
                                <div class="uadt-checkbox-item">
                                    <input type="checkbox"
                                           name="uadt_visible_columns[]"
                                           value="<?php echo esc_attr($column_key); ?>"
                                           <?php checked(in_array($column_key, $settings['visible_columns'])); ?>
                                           id="column_<?php echo esc_attr($column_key); ?>">
                                    <label for="column_<?php echo esc_attr($column_key); ?>"><?php echo esc_html($column_label); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="uadt-help-text"><?php esc_html_e('Select which columns should be visible by default. Users can still show/hide columns in the interface.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render default settings tab
     */
    private function render_default_settings($settings)
    {
        ?>
        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Default Filter Values', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Set default values for filters when the data table loads.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Default Post Status', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_default_status">
                            <option value="" <?php selected($settings['default_status'], ''); ?>><?php esc_html_e('All Statuses', 'ultimate-ajax-datatable'); ?></option>
                            <option value="publish" <?php selected($settings['default_status'], 'publish'); ?>><?php esc_html_e('Published', 'ultimate-ajax-datatable'); ?></option>
                            <option value="draft" <?php selected($settings['default_status'], 'draft'); ?>><?php esc_html_e('Draft', 'ultimate-ajax-datatable'); ?></option>
                            <option value="private" <?php selected($settings['default_status'], 'private'); ?>><?php esc_html_e('Private', 'ultimate-ajax-datatable'); ?></option>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('Default post status filter when the table loads.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default Author', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_default_author">
                            <option value="" <?php selected($settings['default_author'], ''); ?>><?php esc_html_e('All Authors', 'ultimate-ajax-datatable'); ?></option>
                            <option value="current_user" <?php selected($settings['default_author'], 'current_user'); ?>><?php esc_html_e('Current User', 'ultimate-ajax-datatable'); ?></option>
                            <?php
                            $authors = get_users(['who' => 'authors']);
                            foreach ($authors as $author) {
                                echo '<option value="' . esc_attr($author->ID) . '" ' . selected($settings['default_author'], $author->ID, false) . '>' . esc_html($author->display_name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('Default author filter when the table loads.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default Sort Column', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_default_orderby">
                            <option value="date" <?php selected($settings['default_orderby'], 'date'); ?>><?php esc_html_e('Date', 'ultimate-ajax-datatable'); ?></option>
                            <option value="title" <?php selected($settings['default_orderby'], 'title'); ?>><?php esc_html_e('Title', 'ultimate-ajax-datatable'); ?></option>
                            <option value="author" <?php selected($settings['default_orderby'], 'author'); ?>><?php esc_html_e('Author', 'ultimate-ajax-datatable'); ?></option>
                            <option value="modified" <?php selected($settings['default_orderby'], 'modified'); ?>><?php esc_html_e('Modified Date', 'ultimate-ajax-datatable'); ?></option>
                            <option value="comment_count" <?php selected($settings['default_orderby'], 'comment_count'); ?>><?php esc_html_e('Comment Count', 'ultimate-ajax-datatable'); ?></option>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('Default column to sort by when the table loads.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default Sort Order', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_default_order">
                            <option value="DESC" <?php selected($settings['default_order'], 'DESC'); ?>><?php esc_html_e('Descending (Newest First)', 'ultimate-ajax-datatable'); ?></option>
                            <option value="ASC" <?php selected($settings['default_order'], 'ASC'); ?>><?php esc_html_e('Ascending (Oldest First)', 'ultimate-ajax-datatable'); ?></option>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('Default sort order when the table loads.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render performance settings tab
     */
    private function render_performance_settings($settings)
    {
        ?>
        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Caching Settings', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Configure caching to improve performance with large datasets.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Caching', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_enable_caching" value="1" <?php checked($settings['enable_caching']); ?>>
                            <?php esc_html_e('Enable intelligent caching', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('Cache filter options and query results for better performance.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Cache Duration', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <select name="uadt_cache_duration">
                            <option value="60" <?php selected($settings['cache_duration'], 60); ?>><?php esc_html_e('1 minute', 'ultimate-ajax-datatable'); ?></option>
                            <option value="300" <?php selected($settings['cache_duration'], 300); ?>><?php esc_html_e('5 minutes', 'ultimate-ajax-datatable'); ?></option>
                            <option value="900" <?php selected($settings['cache_duration'], 900); ?>><?php esc_html_e('15 minutes', 'ultimate-ajax-datatable'); ?></option>
                            <option value="1800" <?php selected($settings['cache_duration'], 1800); ?>><?php esc_html_e('30 minutes', 'ultimate-ajax-datatable'); ?></option>
                            <option value="3600" <?php selected($settings['cache_duration'], 3600); ?>><?php esc_html_e('1 hour', 'ultimate-ajax-datatable'); ?></option>
                        </select>
                        <p class="uadt-help-text"><?php esc_html_e('How long to cache filter options and query results.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="uadt-info">
            <strong><?php esc_html_e('Performance Tips:', 'ultimate-ajax-datatable'); ?></strong>
            <ul style="margin: 10px 0 0 20px;">
                <li><?php esc_html_e('Enable caching for better performance with large datasets', 'ultimate-ajax-datatable'); ?></li>
                <li><?php esc_html_e('Lower the default items per page if you have thousands of posts', 'ultimate-ajax-datatable'); ?></li>
                <li><?php esc_html_e('Disable unused features to reduce JavaScript bundle size', 'ultimate-ajax-datatable'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render advanced settings tab
     */
    private function render_advanced_settings($settings)
    {
        ?>
        <div class="uadt-setting-section">
            <h3><?php esc_html_e('User Interface Settings', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Advanced options for controlling the user interface behavior.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enhanced Mode Behavior', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="uadt_auto_enhanced_mode" value="1" <?php checked($settings['auto_enhanced_mode']); ?>>
                            <?php esc_html_e('Automatically use enhanced mode', 'ultimate-ajax-datatable'); ?>
                        </label>
                        <p class="uadt-help-text"><?php esc_html_e('When enabled, the enhanced data table will automatically replace the standard WordPress posts page for enabled post types. Users can still access standard view via the "← Standard View" link.', 'ultimate-ajax-datatable'); ?></p>

                        <div style="margin-top: 15px;">
                            <label>
                                <input type="checkbox" name="uadt_show_enhanced_notice" value="1" <?php checked($settings['show_enhanced_notice']); ?> <?php echo $settings['auto_enhanced_mode'] ? 'disabled' : ''; ?>>
                                <?php esc_html_e('Show "Try Enhanced View" notice (when auto mode is disabled)', 'ultimate-ajax-datatable'); ?>
                            </label>
                            <p class="uadt-help-text"><?php esc_html_e('Display a notice on standard post pages offering to switch to enhanced mode. This option is ignored when auto enhanced mode is enabled.', 'ultimate-ajax-datatable'); ?></p>
                        </div>

                        <div class="uadt-info" style="margin-top: 15px;">
                            <strong><?php esc_html_e('Behavior Summary:', 'ultimate-ajax-datatable'); ?></strong>
                            <ul style="margin: 10px 0 0 20px;">
                                <li><strong><?php esc_html_e('Auto Enhanced Mode ON:', 'ultimate-ajax-datatable'); ?></strong> <?php esc_html_e('Enhanced interface loads automatically, no notices shown', 'ultimate-ajax-datatable'); ?></li>
                                <li><strong><?php esc_html_e('Auto Enhanced Mode OFF + Notice ON:', 'ultimate-ajax-datatable'); ?></strong> <?php esc_html_e('Standard interface with "Try Enhanced View" notice', 'ultimate-ajax-datatable'); ?></li>
                                <li><strong><?php esc_html_e('Auto Enhanced Mode OFF + Notice OFF:', 'ultimate-ajax-datatable'); ?></strong> <?php esc_html_e('Standard WordPress interface only, no enhanced features visible', 'ultimate-ajax-datatable'); ?></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="uadt-setting-section">
            <h3><?php esc_html_e('Reset Settings', 'ultimate-ajax-datatable'); ?></h3>
            <p class="uadt-setting-description"><?php esc_html_e('Reset all plugin settings to their default values.', 'ultimate-ajax-datatable'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Reset All Settings', 'ultimate-ajax-datatable'); ?></th>
                    <td>
                        <button type="button" class="button button-secondary" onclick="uadtResetSettings()">
                            <?php esc_html_e('Reset to Defaults', 'ultimate-ajax-datatable'); ?>
                        </button>
                        <p class="uadt-help-text"><?php esc_html_e('This will reset all plugin settings to their default values. This action cannot be undone.', 'ultimate-ajax-datatable'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <script>
        function uadtResetSettings() {
            if (confirm('<?php echo esc_js(__('Are you sure you want to reset all settings to their default values? This action cannot be undone.', 'ultimate-ajax-datatable')); ?>')) {
                var form = document.getElementById('uadt-settings-form');
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reset_settings';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        }

        // Handle auto enhanced mode checkbox interaction
        document.addEventListener('DOMContentLoaded', function() {
            var autoModeCheckbox = document.querySelector('input[name="uadt_auto_enhanced_mode"]');
            var noticeCheckbox = document.querySelector('input[name="uadt_show_enhanced_notice"]');

            if (autoModeCheckbox && noticeCheckbox) {
                function toggleNoticeCheckbox() {
                    if (autoModeCheckbox.checked) {
                        noticeCheckbox.disabled = true;
                        noticeCheckbox.closest('label').style.opacity = '0.5';
                    } else {
                        noticeCheckbox.disabled = false;
                        noticeCheckbox.closest('label').style.opacity = '1';
                    }
                }

                // Initial state
                toggleNoticeCheckbox();

                // Listen for changes
                autoModeCheckbox.addEventListener('change', toggleNoticeCheckbox);
            }
        });
        </script>
        <?php
    }

    /**
     * Save general settings
     */
    private function save_general_settings()
    {
        $enabled_post_types = isset($_POST['uadt_enabled_post_types']) ? array_map('sanitize_text_field', $_POST['uadt_enabled_post_types']) : [];
        $items_per_page = isset($_POST['uadt_items_per_page']) ? intval($_POST['uadt_items_per_page']) : 25;
        $max_items_per_page = isset($_POST['uadt_max_items_per_page']) ? intval($_POST['uadt_max_items_per_page']) : 100;

        update_option('uadt_enabled_post_types', $enabled_post_types);
        update_option('uadt_items_per_page', $items_per_page);
        update_option('uadt_max_items_per_page', $max_items_per_page);
    }

    /**
     * Save feature settings
     */
    private function save_feature_settings()
    {
        $enable_search = isset($_POST['uadt_enable_search']) ? 1 : 0;
        $enable_filters = isset($_POST['uadt_enable_filters']) ? 1 : 0;
        $enable_bulk_actions = isset($_POST['uadt_enable_bulk_actions']) ? 1 : 0;
        $enable_export = isset($_POST['uadt_enable_export']) ? 1 : 0;
        $enable_presets = isset($_POST['uadt_enable_presets']) ? 1 : 0;

        update_option('uadt_enable_search', $enable_search);
        update_option('uadt_enable_filters', $enable_filters);
        update_option('uadt_enable_bulk_actions', $enable_bulk_actions);
        update_option('uadt_enable_export', $enable_export);
        update_option('uadt_enable_presets', $enable_presets);
    }

    /**
     * Save column settings
     */
    private function save_column_settings()
    {
        $visible_columns = isset($_POST['uadt_visible_columns']) ? array_map('sanitize_text_field', $_POST['uadt_visible_columns']) : [];
        update_option('uadt_visible_columns', $visible_columns);
    }

    /**
     * Save default settings
     */
    private function save_default_settings()
    {
        $default_status = isset($_POST['uadt_default_status']) ? sanitize_text_field($_POST['uadt_default_status']) : '';
        $default_author = isset($_POST['uadt_default_author']) ? sanitize_text_field($_POST['uadt_default_author']) : '';
        $default_orderby = isset($_POST['uadt_default_orderby']) ? sanitize_text_field($_POST['uadt_default_orderby']) : 'date';
        $default_order = isset($_POST['uadt_default_order']) ? sanitize_text_field($_POST['uadt_default_order']) : 'DESC';

        update_option('uadt_default_status', $default_status);
        update_option('uadt_default_author', $default_author);
        update_option('uadt_default_orderby', $default_orderby);
        update_option('uadt_default_order', $default_order);
    }

    /**
     * Save performance settings
     */
    private function save_performance_settings()
    {
        $enable_caching = isset($_POST['uadt_enable_caching']) ? 1 : 0;
        $cache_duration = isset($_POST['uadt_cache_duration']) ? intval($_POST['uadt_cache_duration']) : 300;

        update_option('uadt_enable_caching', $enable_caching);
        update_option('uadt_cache_duration', $cache_duration);
    }

    /**
     * Save advanced settings
     */
    private function save_advanced_settings()
    {
        // Handle reset settings
        if (isset($_POST['reset_settings'])) {
            $this->reset_all_settings();
            return;
        }

        $show_enhanced_notice = isset($_POST['uadt_show_enhanced_notice']) ? 1 : 0;
        $auto_enhanced_mode = isset($_POST['uadt_auto_enhanced_mode']) ? 1 : 0;

        update_option('uadt_show_enhanced_notice', $show_enhanced_notice);
        update_option('uadt_auto_enhanced_mode', $auto_enhanced_mode);
    }

    /**
     * Reset all settings to defaults
     */
    private function reset_all_settings()
    {
        $default_options = [
            'uadt_enabled_post_types' => ['post'],
            'uadt_items_per_page' => 25,
            'uadt_max_items_per_page' => 100,
            'uadt_enable_search' => true,
            'uadt_enable_filters' => true,
            'uadt_enable_bulk_actions' => true,
            'uadt_enable_export' => true,
            'uadt_enable_presets' => true,
            'uadt_visible_columns' => ['title', 'author', 'categories', 'tags', 'date', 'status'],
            'uadt_default_status' => '',
            'uadt_default_author' => '',
            'uadt_default_orderby' => 'date',
            'uadt_default_order' => 'DESC',
            'uadt_enable_caching' => true,
            'uadt_cache_duration' => 300,
            'uadt_show_enhanced_notice' => true,
            'uadt_auto_enhanced_mode' => false,
        ];

        foreach ($default_options as $option_name => $default_value) {
            update_option($option_name, $default_value);
        }
    }

    /**
     * Sanitization callbacks
     */
    public function sanitize_post_types($value)
    {
        if (!is_array($value)) {
            return ['post'];
        }

        $valid_post_types = get_post_types(['public' => true]);
        return array_intersect($value, array_keys($valid_post_types));
    }

    public function sanitize_items_per_page($value)
    {
        $value = intval($value);
        return max(10, min(500, $value));
    }

    public function sanitize_max_items_per_page($value)
    {
        $value = intval($value);
        return max(50, min(1000, $value));
    }

    public function sanitize_visible_columns($value)
    {
        if (!is_array($value)) {
            return ['title', 'author', 'date', 'status'];
        }

        $valid_columns = [
            'title', 'author', 'categories', 'tags', 'date', 'modified',
            'status', 'comment_count', 'featured_image', 'excerpt', 'post_id', 'post_type'
        ];

        return array_intersect($value, $valid_columns);
    }

    public function sanitize_order($value)
    {
        return in_array(strtoupper($value), ['ASC', 'DESC']) ? strtoupper($value) : 'DESC';
    }

    public function sanitize_cache_duration($value)
    {
        $value = intval($value);
        return max(60, min(3600, $value));
    }
}
