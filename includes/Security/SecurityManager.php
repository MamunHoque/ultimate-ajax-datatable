<?php
/**
 * Security Manager Class
 *
 * @package UltimateAjaxDataTable\Security
 * @since 1.0.0
 */

namespace UltimateAjaxDataTable\Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SecurityManager class
 */
class SecurityManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', [$this, 'init_security']);
    }

    /**
     * Initialize security measures
     */
    public function init_security()
    {
        // Add security headers
        add_action('send_headers', [$this, 'add_security_headers']);
        
        // Sanitize all inputs
        add_action('rest_api_init', [$this, 'sanitize_rest_inputs']);
    }

    /**
     * Add security headers
     */
    public function add_security_headers()
    {
        if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'uadt-') === 0) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
        }
    }

    /**
     * Sanitize REST API inputs
     */
    public function sanitize_rest_inputs()
    {
        // This will be expanded when we create the REST API
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @param string $nonce The nonce to verify
     * @param string $action The action for the nonce
     * @return bool
     */
    public static function verify_nonce($nonce, $action = 'wp_rest')
    {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check user capabilities
     *
     * @param string $capability The capability to check
     * @param int $user_id Optional user ID
     * @return bool
     */
    public static function check_capability($capability, $user_id = null)
    {
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            return $user && user_can($user, $capability);
        }
        
        return current_user_can($capability);
    }

    /**
     * Sanitize filter input
     *
     * @param mixed $input The input to sanitize
     * @param string $type The type of sanitization
     * @return mixed
     */
    public static function sanitize_filter_input($input, $type = 'text')
    {
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
            
            case 'textarea':
                return sanitize_textarea_field($input);
            
            case 'email':
                return sanitize_email($input);
            
            case 'url':
                return esc_url_raw($input);
            
            case 'int':
                return intval($input);
            
            case 'float':
                return floatval($input);
            
            case 'array':
                if (!is_array($input)) {
                    return [];
                }
                return array_map('sanitize_text_field', $input);
            
            case 'json':
                if (is_string($input)) {
                    $decoded = json_decode($input, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return is_array($input) ? $input : [];
            
            default:
                return sanitize_text_field($input);
        }
    }

    /**
     * Validate filter parameters
     *
     * @param array $filters The filters to validate
     * @return array
     */
    public static function validate_filters($filters)
    {
        $valid_filters = [];
        $allowed_filter_types = [
            'search',
            'author',
            'status',
            'post_type',
            'category',
            'tag',
            'date_from',
            'date_to',
            'meta_key',
            'meta_value'
        ];

        foreach ($filters as $key => $value) {
            if (in_array($key, $allowed_filter_types)) {
                $valid_filters[$key] = self::sanitize_filter_input($value);
            }
        }

        return $valid_filters;
    }

    /**
     * Check if user can perform bulk action
     *
     * @param string $action The bulk action
     * @param array $post_ids The post IDs
     * @return bool
     */
    public static function can_perform_bulk_action($action, $post_ids)
    {
        if (!is_array($post_ids) || empty($post_ids)) {
            return false;
        }

        // Check capability based on action
        switch ($action) {
            case 'delete':
            case 'trash':
                $capability = 'delete_posts';
                break;
            
            case 'publish':
            case 'draft':
            case 'private':
                $capability = 'edit_posts';
                break;
            
            default:
                return false;
        }

        if (!self::check_capability($capability)) {
            return false;
        }

        // Check individual post permissions
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                return false;
            }

            switch ($action) {
                case 'delete':
                case 'trash':
                    if (!current_user_can('delete_post', $post_id)) {
                        return false;
                    }
                    break;
                
                case 'publish':
                case 'draft':
                case 'private':
                    if (!current_user_can('edit_post', $post_id)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Rate limiting for API requests
     *
     * @param string $key The rate limit key
     * @param int $limit The request limit
     * @param int $window The time window in seconds
     * @return bool
     */
    public static function check_rate_limit($key, $limit = 100, $window = 3600)
    {
        $transient_key = 'uadt_rate_limit_' . md5($key);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            set_transient($transient_key, 1, $window);
            return true;
        }
        
        if ($requests >= $limit) {
            return false;
        }
        
        set_transient($transient_key, $requests + 1, $window);
        return true;
    }

    /**
     * Log security events
     *
     * @param string $event The event to log
     * @param array $data Additional data
     */
    public static function log_security_event($event, $data = [])
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'UADT Security Event: %s - Data: %s - User: %d - IP: %s',
                $event,
                json_encode($data),
                get_current_user_id(),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ));
        }
    }
}
