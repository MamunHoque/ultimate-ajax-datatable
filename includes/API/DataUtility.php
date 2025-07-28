<?php
/**
 * Data Utility Class
 *
 * @package UltimateAjaxDataTable\API
 * @since 1.0.0
 */

namespace UltimateAjaxDataTable\API;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DataUtility class
 */
class DataUtility
{
    /**
     * Cache group for transients
     */
    const CACHE_GROUP = 'uadt_data';

    /**
     * Cache duration in seconds (5 minutes)
     */
    const CACHE_DURATION = 300;

    /**
     * Get cached data or execute callback
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $duration Cache duration in seconds
     * @return mixed
     */
    public static function get_cached($key, $callback, $duration = self::CACHE_DURATION)
    {
        $cache_key = self::CACHE_GROUP . '_' . md5($key);
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }

        $data = call_user_func($callback);
        
        if ($data !== false && $data !== null) {
            set_transient($cache_key, $data, $duration);
        }

        return $data;
    }

    /**
     * Clear cache for specific key or all plugin cache
     *
     * @param string|null $key Specific cache key or null for all
     */
    public static function clear_cache($key = null)
    {
        if ($key) {
            $cache_key = self::CACHE_GROUP . '_' . md5($key);
            delete_transient($cache_key);
        } else {
            // Clear all plugin transients
            global $wpdb;
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s
            ", '_transient_' . self::CACHE_GROUP . '_%'));
        }
    }

    /**
     * Optimize query for large datasets
     *
     * @param array $args WP_Query arguments
     * @return array
     */
    public static function optimize_query_args($args)
    {
        // Disable unnecessary queries for performance
        $args['no_found_rows'] = false; // We need pagination info
        $args['update_post_meta_cache'] = false; // Don't load meta unless needed
        $args['update_post_term_cache'] = true; // We need terms for display
        $args['cache_results'] = true; // Enable query caching
        
        // Limit fields if we don't need full post objects
        if (!isset($args['fields'])) {
            $args['fields'] = 'all'; // Keep all fields for now
        }

        return $args;
    }

    /**
     * Get post count for specific filters (cached)
     *
     * @param array $filters
     * @return int
     */
    public static function get_filtered_post_count($filters)
    {
        $cache_key = 'post_count_' . serialize($filters);
        
        return self::get_cached($cache_key, function() use ($filters) {
            // Build count query
            $count_args = $filters;
            $count_args['posts_per_page'] = 1;
            $count_args['fields'] = 'ids';
            $count_args['no_found_rows'] = false;
            
            $query = new \WP_Query($count_args);
            return $query->found_posts;
        }, 600); // Cache for 10 minutes
    }

    /**
     * Get popular search terms (cached)
     *
     * @param string $post_type
     * @param int $limit
     * @return array
     */
    public static function get_popular_search_terms($post_type = 'post', $limit = 10)
    {
        $cache_key = "popular_searches_{$post_type}_{$limit}";
        
        return self::get_cached($cache_key, function() use ($post_type, $limit) {
            // This would typically come from search logs
            // For now, return common terms based on post titles
            global $wpdb;
            
            $terms = $wpdb->get_results($wpdb->prepare("
                SELECT post_title, COUNT(*) as count
                FROM {$wpdb->posts}
                WHERE post_type = %s 
                AND post_status = 'publish'
                AND post_title != ''
                GROUP BY post_title
                ORDER BY count DESC
                LIMIT %d
            ", $post_type, $limit));

            return array_map(function($term) {
                return [
                    'term' => $term->post_title,
                    'count' => $term->count
                ];
            }, $terms);
        }, 3600); // Cache for 1 hour
    }

    /**
     * Build search suggestions
     *
     * @param string $query
     * @param string $post_type
     * @param int $limit
     * @return array
     */
    public static function get_search_suggestions($query, $post_type = 'post', $limit = 5)
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cache_key = "search_suggestions_{$post_type}_{$query}_{$limit}";
        
        return self::get_cached($cache_key, function() use ($query, $post_type, $limit) {
            global $wpdb;
            
            $suggestions = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT post_title
                FROM {$wpdb->posts}
                WHERE post_type = %s 
                AND post_status = 'publish'
                AND post_title LIKE %s
                ORDER BY post_title
                LIMIT %d
            ", $post_type, '%' . $wpdb->esc_like($query) . '%', $limit));

            return array_map(function($suggestion) {
                return $suggestion->post_title;
            }, $suggestions);
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Get database performance stats
     *
     * @return array
     */
    public static function get_performance_stats()
    {
        global $wpdb;
        
        return [
            'total_queries' => get_num_queries(),
            'query_time' => timer_stop(0, 3),
            'memory_usage' => size_format(memory_get_usage()),
            'memory_peak' => size_format(memory_get_peak_usage()),
            'cache_hits' => wp_cache_get_stats(),
        ];
    }

    /**
     * Log slow queries for optimization
     *
     * @param string $query
     * @param float $execution_time
     */
    public static function log_slow_query($query, $execution_time)
    {
        if ($execution_time > 1.0 && defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'UADT Slow Query (%.3fs): %s',
                $execution_time,
                $query
            ));
        }
    }

    /**
     * Sanitize and validate filter array
     *
     * @param array $filters
     * @return array
     */
    public static function sanitize_filters($filters)
    {
        $sanitized = [];
        $allowed_filters = [
            'search', 'post_type', 'author', 'status', 'category', 'tag',
            'date_from', 'date_to', 'page', 'per_page', 'orderby', 'order'
        ];

        foreach ($filters as $key => $value) {
            if (in_array($key, $allowed_filters)) {
                switch ($key) {
                    case 'page':
                    case 'per_page':
                        $sanitized[$key] = max(1, intval($value));
                        break;
                    
                    case 'search':
                    case 'post_type':
                    case 'author':
                    case 'status':
                    case 'orderby':
                    case 'order':
                        $sanitized[$key] = sanitize_text_field($value);
                        break;
                    
                    case 'category':
                    case 'tag':
                        if (is_array($value)) {
                            $sanitized[$key] = array_map('sanitize_text_field', $value);
                        } else {
                            $sanitized[$key] = sanitize_text_field($value);
                        }
                        break;
                    
                    case 'date_from':
                    case 'date_to':
                        $date = sanitize_text_field($value);
                        if (self::validate_date_format($date)) {
                            $sanitized[$key] = $date;
                        }
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Validate date format
     *
     * @param string $date
     * @return bool
     */
    private static function validate_date_format($date)
    {
        if (empty($date)) {
            return false;
        }

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
     * Generate cache key from filters
     *
     * @param array $filters
     * @return string
     */
    public static function generate_cache_key($filters)
    {
        ksort($filters); // Ensure consistent ordering
        return md5(serialize($filters) . get_current_user_id());
    }
}
