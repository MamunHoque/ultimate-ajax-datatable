<?php
/**
 * Plugin Name: Ultimate Ajax DataTable
 * Plugin URI: https://codecanyon.net/item/ultimate-ajax-datatable
 * Description: A practical WordPress plugin that enhances admin list tables with modern AJAX DataTable interface and powerful multiple filter options. Designed for broad hosting compatibility and real user needs.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ultimate-ajax-datatable
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package UltimateAjaxDataTable
 * @version 1.0.0
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UADT_VERSION', '1.0.0');
define('UADT_PLUGIN_FILE', __FILE__);
define('UADT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UADT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UADT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('UADT_ASSETS_URL', UADT_PLUGIN_URL . 'assets/');
define('UADT_INCLUDES_DIR', UADT_PLUGIN_DIR . 'includes/');
define('UADT_TEMPLATES_DIR', UADT_PLUGIN_DIR . 'templates/');
define('UADT_LANGUAGES_DIR', UADT_PLUGIN_DIR . 'languages/');

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'UltimateAjaxDataTable\\';
    $base_dir = UADT_INCLUDES_DIR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Main plugin class
 */
final class UltimateAjaxDataTable
{
    /**
     * Plugin instance
     *
     * @var UltimateAjaxDataTable
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return UltimateAjaxDataTable
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        register_activation_hook(UADT_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(UADT_PLUGIN_FILE, [$this, 'deactivate']);
        
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Check compatibility
        if (!$this->is_compatible()) {
            deactivate_plugins(UADT_PLUGIN_BASENAME);
            wp_die(
                esc_html__('Ultimate Ajax DataTable requires WordPress 5.8+ and PHP 7.4+', 'ultimate-ajax-datatable'),
                esc_html__('Plugin Activation Error', 'ultimate-ajax-datatable'),
                ['back_link' => true]
            );
        }

        // Create database tables
        $this->create_tables();

        // Set default options
        $this->set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('uadt_activated', true);
        update_option('uadt_version', UADT_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clear scheduled events
        wp_clear_scheduled_hook('uadt_cleanup_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('uadt_activated');
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if WordPress and PHP versions are compatible
        if (!$this->is_compatible()) {
            return;
        }

        // Initialize core components
        $this->init_components();
        
        // Schedule cleanup events
        if (!wp_next_scheduled('uadt_cleanup_cache')) {
            wp_schedule_event(time(), 'daily', 'uadt_cleanup_cache');
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'ultimate-ajax-datatable',
            false,
            dirname(UADT_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Check compatibility
     *
     * @return bool
     */
    private function is_compatible()
    {
        global $wp_version;
        
        // Check WordPress version
        if (version_compare($wp_version, '5.8', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('Ultimate Ajax DataTable requires WordPress 5.8 or higher.', 'ultimate-ajax-datatable');
                echo '</p></div>';
            });
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('Ultimate Ajax DataTable requires PHP 7.4 or higher.', 'ultimate-ajax-datatable');
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }

    /**
     * Initialize core components
     */
    private function init_components()
    {
        // Initialize admin interface
        if (is_admin()) {
            new UltimateAjaxDataTable\Admin\AdminManager();
        }
        
        // Initialize REST API
        new UltimateAjaxDataTable\API\RestController();
        
        // Initialize security manager
        new UltimateAjaxDataTable\Security\SecurityManager();
    }

    /**
     * Create database tables
     */
    private function create_tables()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create filter presets table
        $table_name = $wpdb->prefix . 'uadt_filter_presets';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            filters longtext NOT NULL,
            post_type varchar(50) NOT NULL DEFAULT 'post',
            is_default tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY post_type (post_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private function set_default_options()
    {
        $default_options = [
            'uadt_enabled_post_types' => ['post', 'page'],
            'uadt_items_per_page' => 25,
            'uadt_enable_search' => true,
            'uadt_enable_filters' => true,
            'uadt_enable_bulk_actions' => true,

            'uadt_cache_duration' => 300, // 5 minutes
            'uadt_max_items_per_page' => 100,
        ];
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
function uadt_init()
{
    return UltimateAjaxDataTable::instance();
}

// Start the plugin
uadt_init();

/**
 * Helper function to get plugin instance
 *
 * @return UltimateAjaxDataTable
 */
function uadt()
{
    return UltimateAjaxDataTable::instance();
}
