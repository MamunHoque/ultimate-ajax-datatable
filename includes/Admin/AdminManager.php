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
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on our admin pages
        if (strpos($hook, 'uadt-') === false) {
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
}
