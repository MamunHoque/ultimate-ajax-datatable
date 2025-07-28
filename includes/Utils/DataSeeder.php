<?php
/**
 * Data Seeder for generating test posts
 *
 * @package UltimateAjaxDataTable\Utils
 * @since 1.0.0
 */

namespace UltimateAjaxDataTable\Utils;

/**
 * DataSeeder class for creating test data
 */
class DataSeeder
{
    /**
     * Generate test posts
     *
     * @param int $count Number of posts to create
     * @return array Array of created post IDs
     */
    public static function create_test_posts($count = 50)
    {
        $post_ids = [];
        
        // Sample post titles
        $titles = [
            'Getting Started with WordPress Development',
            'Advanced PHP Techniques for Modern Web Development',
            'Building Responsive Websites with CSS Grid',
            'JavaScript ES6 Features Every Developer Should Know',
            'Database Optimization Tips for Better Performance',
            'Creating RESTful APIs with WordPress',
            'Modern Frontend Development with React',
            'Security Best Practices for WordPress Sites',
            'Performance Optimization Strategies',
            'User Experience Design Principles',
            'Mobile-First Development Approach',
            'Version Control with Git and GitHub',
            'Testing Strategies for Web Applications',
            'Deployment and DevOps for WordPress',
            'Content Management Best Practices',
            'SEO Optimization for WordPress Sites',
            'E-commerce Development with WooCommerce',
            'Custom Post Types and Fields',
            'WordPress Theme Development Guide',
            'Plugin Development Best Practices',
            'Working with WordPress Hooks and Filters',
            'Database Design and Management',
            'API Integration Techniques',
            'Progressive Web Apps with WordPress',
            'Accessibility in Web Development',
            'Cross-Browser Compatibility Testing',
            'Performance Monitoring and Analytics',
            'Content Strategy for Digital Marketing',
            'Social Media Integration',
            'Email Marketing Automation',
            'Search Engine Optimization',
            'Conversion Rate Optimization',
            'A/B Testing Methodologies',
            'User Analytics and Insights',
            'Customer Journey Mapping',
            'Brand Identity and Design',
            'Typography in Web Design',
            'Color Theory for Designers',
            'Layout and Composition Principles',
            'Prototyping and Wireframing',
            'User Interface Design Trends',
            'Design Systems and Style Guides',
            'Illustration and Icon Design',
            'Photography for Web Content',
            'Video Content Creation',
            'Podcast Production and Distribution',
            'Content Writing and Copywriting',
            'Technical Documentation',
            'Project Management for Developers',
            'Team Collaboration Tools and Techniques'
        ];

        // Sample content paragraphs
        $content_samples = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
            'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
            'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.',
            'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet.',
            'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.',
            'Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque.',
            'Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus.',
            'Ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.'
        ];

        // Post statuses to vary the data
        $statuses = ['publish', 'publish', 'publish', 'draft', 'private'];
        
        // Get some existing users or create test authors
        $users = get_users(['number' => 5]);
        if (empty($users)) {
            $users = [get_current_user_id()];
        } else {
            $users = array_column($users, 'ID');
        }

        for ($i = 0; $i < $count; $i++) {
            // Random title
            $title = $titles[array_rand($titles)];
            if ($i > 0) {
                $title .= ' ' . ($i + 1); // Make titles unique
            }

            // Random content (2-4 paragraphs)
            $paragraph_count = rand(2, 4);
            $content_paragraphs = [];
            for ($j = 0; $j < $paragraph_count; $j++) {
                $content_paragraphs[] = $content_samples[array_rand($content_samples)];
            }
            $content = '<p>' . implode('</p><p>', $content_paragraphs) . '</p>';

            // Random excerpt
            $excerpt = substr($content_samples[array_rand($content_samples)], 0, 150) . '...';

            // Random status
            $status = $statuses[array_rand($statuses)];

            // Random author
            $author = $users[array_rand($users)];

            // Random date (within last 6 months)
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 180) . ' days'));

            $post_data = [
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => $status,
                'post_type' => 'post',
                'post_author' => $author,
                'post_date' => $date,
                'meta_input' => [
                    '_test_post' => true, // Mark as test post for easy cleanup
                ]
            ];

            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                $post_ids[] = $post_id;

                // Add some random categories and tags
                if ($i % 3 == 0) {
                    wp_set_post_categories($post_id, [1]); // Uncategorized
                }
                
                if ($i % 5 == 0) {
                    wp_set_post_tags($post_id, ['development', 'tutorial']);
                } elseif ($i % 7 == 0) {
                    wp_set_post_tags($post_id, ['design', 'ui/ux']);
                } elseif ($i % 4 == 0) {
                    wp_set_post_tags($post_id, ['wordpress', 'php']);
                }
            }
        }

        return $post_ids;
    }

    /**
     * Clean up test posts
     *
     * @return int Number of posts deleted
     */
    public static function cleanup_test_posts()
    {
        $test_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'any',
            'numberposts' => -1,
            'meta_key' => '_test_post',
            'meta_value' => true,
        ]);

        $deleted_count = 0;
        foreach ($test_posts as $post) {
            if (wp_delete_post($post->ID, true)) {
                $deleted_count++;
            }
        }

        return $deleted_count;
    }

    /**
     * Create test categories
     *
     * @return array Array of created category IDs
     */
    public static function create_test_categories()
    {
        $categories = [
            'Web Development',
            'Design',
            'Marketing',
            'Business',
            'Technology',
            'Tutorials',
            'News',
            'Reviews'
        ];

        $category_ids = [];
        foreach ($categories as $category) {
            $result = wp_insert_term($category, 'category');
            if (!is_wp_error($result)) {
                $category_ids[] = $result['term_id'];
            }
        }

        return $category_ids;
    }

    /**
     * Create test tags
     *
     * @return array Array of created tag IDs
     */
    public static function create_test_tags()
    {
        $tags = [
            'wordpress', 'php', 'javascript', 'css', 'html',
            'react', 'vue', 'angular', 'nodejs', 'mysql',
            'design', 'ui', 'ux', 'frontend', 'backend',
            'api', 'rest', 'json', 'ajax', 'jquery',
            'responsive', 'mobile', 'seo', 'performance', 'security'
        ];

        $tag_ids = [];
        foreach ($tags as $tag) {
            $result = wp_insert_term($tag, 'post_tag');
            if (!is_wp_error($result)) {
                $tag_ids[] = $result['term_id'];
            }
        }

        return $tag_ids;
    }
}
