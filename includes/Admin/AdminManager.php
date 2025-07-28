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
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Hide the default posts table
            $('.wp-list-table').hide();
            $('.tablenav').hide();
            $('.search-box').hide();

            // Add our enhanced data table container
            $('.wrap h1').after('<div id="uadt-posts-integration" style="margin-top: 20px;"></div>');

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
                    React.createElement('div', { className: 'uadt-posts-header', style: { marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                        React.createElement('div', { style: { display: 'flex', alignItems: 'center', gap: '10px' } },
                            React.createElement('input', {
                                type: 'text',
                                placeholder: 'Search posts...',
                                value: filters.search,
                                onChange: handleSearchChange,
                                style: {
                                    padding: '8px 12px',
                                    border: '1px solid #ddd',
                                    borderRadius: '4px',
                                    width: '300px'
                                }
                            }),
                            React.createElement('span', { style: { color: '#666', fontSize: '14px' } },
                                `${total} items found`
                            )
                        ),
                        React.createElement('a', {
                            href: window.location.pathname + window.location.search.replace(/[?&]uadt_mode=enhanced/, ''),
                            className: 'button',
                            style: { textDecoration: 'none' }
                        }, 'Switch to Standard View')
                    ),

                    // Content
                    loading ?
                        React.createElement('div', { style: { padding: '40px', textAlign: 'center' } }, 'Loading posts...') :
                    error ?
                        React.createElement('div', { style: { padding: '40px', textAlign: 'center', color: '#d63638' } }, error) :
                        React.createElement('div', null,
                            // Table
                            React.createElement('table', { className: 'wp-list-table widefat fixed striped posts' },
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
                                            React.createElement('td', { colSpan: 5, style: { textAlign: 'center', padding: '40px' } }, 'No posts found')
                                        ) :
                                        posts.map(post =>
                                            React.createElement('tr', { key: post.id },
                                                React.createElement('td', null,
                                                    React.createElement('strong', null, post.title || '(No title)'),
                                                    post.excerpt ? React.createElement('div', { style: { color: '#666', fontSize: '13px', marginTop: '4px' } }, post.excerpt.substring(0, 100) + '...') : null
                                                ),
                                                React.createElement('td', null, post.author),
                                                React.createElement('td', null,
                                                    React.createElement('span', {
                                                        style: {
                                                            padding: '2px 8px',
                                                            borderRadius: '3px',
                                                            fontSize: '12px',
                                                            backgroundColor: post.status === 'publish' ? '#00a32a' : '#dba617',
                                                            color: 'white'
                                                        }
                                                    }, post.status_label)
                                                ),
                                                React.createElement('td', null, post.date_formatted),
                                                React.createElement('td', null,
                                                    React.createElement('div', { style: { display: 'flex', gap: '8px' } },
                                                        post.edit_link ?
                                                            React.createElement('a', {
                                                                href: post.edit_link,
                                                                className: 'button button-small'
                                                            }, 'Edit') : null,
                                                        post.view_link ?
                                                            React.createElement('a', {
                                                                href: post.view_link,
                                                                className: 'button button-small',
                                                                target: '_blank'
                                                            }, 'View') : null
                                                    )
                                                )
                                            )
                                        )
                                )
                            ),

                            // Pagination
                            totalPages > 1 ? React.createElement('div', { className: 'tablenav bottom', style: { marginTop: '20px' } },
                                React.createElement('div', { className: 'tablenav-pages' },
                                    React.createElement('span', { className: 'displaying-num' }, `${total} items`),
                                    React.createElement('span', { className: 'pagination-links' },
                                        filters.page > 1 ?
                                            React.createElement('a', {
                                                className: 'button',
                                                onClick: () => handlePageChange(filters.page - 1),
                                                style: { marginRight: '5px', cursor: 'pointer' }
                                            }, '‹ Previous') : null,
                                        React.createElement('span', { style: { margin: '0 10px' } },
                                            `Page ${filters.page} of ${totalPages}`
                                        ),
                                        filters.page < totalPages ?
                                            React.createElement('a', {
                                                className: 'button',
                                                onClick: () => handlePageChange(filters.page + 1),
                                                style: { marginLeft: '5px', cursor: 'pointer' }
                                            }, 'Next ›') : null
                                    )
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
}
