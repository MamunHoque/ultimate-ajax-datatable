# Frequently Asked Questions (FAQ)

## General Questions

### What is Ultimate Ajax DataTable?
Ultimate Ajax DataTable is a WordPress plugin that enhances your posts management experience with a modern, fast, and beautiful interface. It replaces the standard WordPress posts table with an advanced data table featuring real-time search, filtering, and professional design.

### Do I need coding knowledge to use this plugin?
No! The plugin is designed for all users. Simply install, activate, and start using the enhanced interface. No coding or technical knowledge required.

### Will this plugin slow down my website?
No, the plugin is designed for performance. It uses intelligent caching, optimized queries, and only loads on admin pages. Your website's frontend performance is not affected.

### Is it compatible with my theme?
Yes! The plugin only affects the WordPress admin area, not your website's frontend. It works with any WordPress theme.

## Installation & Setup

### How do I install the plugin?
1. **Automatic**: Go to Plugins > Add New, search for "Ultimate Ajax DataTable", install and activate
2. **Manual**: Download the zip file, go to Plugins > Add New > Upload Plugin, choose the file and activate

### What happens after activation?
After activation, you'll see a new "Ultimate Ajax DataTable" menu in your WordPress admin. Visit Posts > All Posts to see the option to try the enhanced view.

### Do I need to configure anything?
The plugin works out of the box with sensible defaults. However, you can customize settings via Ultimate Ajax DataTable > Settings.

### How do I generate test data?
Go to Ultimate Ajax DataTable > Test Data and click "Create Test Posts" to generate 50 sample posts for testing the plugin's features.

## Using the Plugin

### How do I access the enhanced posts view?
1. Go to Posts > All Posts in your WordPress admin
2. Look for the blue notice: "Ultimate Ajax DataTable Available"
3. Click "Try Enhanced View" to activate the enhanced interface

### How do I switch back to the standard WordPress view?
Click the "← Standard View" button in the top right corner of the enhanced interface.

### Why can't I see the "Add New" button?
The "Add New" button is positioned in the top header area next to the "Posts" title, exactly where it appears in standard WordPress. If you can't see it, try refreshing the page.

### How does the search work?
The search is real-time and searches through post titles, content, and author names. Simply start typing in the search box and results will filter automatically.

### Can I search for specific content?
Yes! The search looks through post titles, content, excerpts, and author names. It's case-insensitive and updates as you type.

## Features & Functionality

### What filters are available?
The enhanced interface includes:
- Real-time text search
- Author filtering
- Post status filtering (published, draft, private)
- Date range filtering
- Category and tag filtering (for posts)

### How does pagination work?
The plugin shows 25 posts per page by default (configurable in settings). Use the Previous/Next buttons or page numbers to navigate. The interface shows exactly which items you're viewing.

### What are bulk actions?
Bulk actions let you perform operations on multiple posts at once:
- Change status (publish, draft, private)
- Move to trash
- Delete permanently (if you have permission)

### How do I use bulk actions?
1. Select posts using the checkboxes
2. Choose an action from the "Bulk Actions" dropdown
3. Click "Apply"
4. Confirm the action if prompted

### What are the colored status badges?
Status badges provide visual indicators:
- 🟢 **Green (Published)**: Post is live on your website
- 🟡 **Yellow (Draft)**: Post is saved but not published
- 🔵 **Blue (Private)**: Post is published but only visible to logged-in users

## Performance & Technical

### How many posts can the plugin handle?
The plugin is tested with 5,000+ posts and performs well. It uses pagination and caching to maintain good performance regardless of your total post count.

### Does it work on shared hosting?
Yes! The plugin is designed to work on all hosting environments, including shared hosting. It has minimal server requirements and efficient resource usage.

### What are the system requirements?
- WordPress 5.8 or higher
- PHP 7.4 or higher
- Modern browser with JavaScript enabled
- MySQL 5.6 or higher

### Does it work with other plugins?
Yes! The plugin is designed to be compatible with other WordPress plugins. It works alongside SEO plugins, page builders, security plugins, and more.

### Is it multisite compatible?
Yes, the plugin works on WordPress multisite installations. Each site has its own settings and data.

## Troubleshooting

### The enhanced view isn't loading
**Solutions:**
1. Make sure JavaScript is enabled in your browser
2. Check for browser console errors (F12 > Console)
3. Try refreshing the page
4. Deactivate and reactivate the plugin
5. Check if other plugins are causing conflicts

### Search isn't returning results
**Solutions:**
1. Make sure you have posts with content to search
2. Try different search terms
3. Check that posts aren't all in draft status
4. Clear any browser cache
5. Try deactivating and reactivating the plugin

### The page loads slowly
**Solutions:**
1. Reduce items per page in plugin settings
2. Enable caching in plugin settings
3. Check your hosting resources
4. Remove unnecessary test data

### I can't see the "Try Enhanced View" notice
**Solutions:**
1. Make sure the plugin is activated
2. Check that you're on the Posts > All Posts page
3. Verify you have permission to edit posts
4. Try refreshing the page

### Bulk actions aren't working
**Solutions:**
1. Make sure you have selected posts with checkboxes
2. Verify you have permission for the action you're trying
3. Check that the posts aren't already in the target status
4. Try with fewer posts selected

## Customization & Development

### Can I customize the appearance?
Yes! The plugin uses CSS classes prefixed with `uadt-` that you can customize with additional CSS in your theme or via the WordPress Customizer.

### Are there hooks for developers?
Yes! The plugin provides filter and action hooks for developers to extend functionality. See the developer documentation for details.

### Can I modify the search behavior?
Developers can use the `uadt_query_args` filter to modify how searches are performed.

### How do I add custom columns?
This feature is planned for a future version. Currently, the plugin displays standard post information.

## Data & Security

### Is my data safe?
Yes! The plugin follows WordPress security best practices:
- All inputs are sanitized and validated
- Proper capability checks for all actions
- CSRF protection with nonces
- No data is sent to external servers

### Does the plugin collect any data?
No, the plugin doesn't collect or transmit any data to external servers. All data stays on your WordPress site.

### Can I export my data?
The plugin includes export functionality for CSV and Excel formats (coming in future version).

### What happens if I deactivate the plugin?
Your posts and data remain completely intact. The plugin only affects the admin interface, not your actual content.

## Support & Updates

### How do I get support?
- **Documentation**: Check the user guide and this FAQ
- **WordPress Forum**: Visit the plugin's support forum
- **GitHub**: Report bugs or request features on GitHub

### How often is the plugin updated?
The plugin is actively maintained with regular updates for compatibility, security, and new features.

### Will my settings be lost during updates?
No, plugin updates preserve all your settings and data.

### How do I report a bug?
Please report bugs on the GitHub repository with:
1. WordPress version
2. Plugin version
3. Steps to reproduce the issue
4. Any error messages

### Can I request new features?
Yes! Feature requests are welcome on the GitHub repository or support forum.

## Uninstallation

### How do I remove the plugin?
1. Deactivate the plugin via Plugins > Installed Plugins
2. Delete the plugin files
3. Optionally, clean up test data via Ultimate Ajax DataTable > Test Data before deactivation

### Will uninstalling remove my posts?
No! The plugin only affects the admin interface. Your posts, pages, and all content remain completely intact.

### What data does the plugin store?
The plugin stores:
- Plugin settings in WordPress options
- Filter presets (if used)
- Test data markers (for easy cleanup)

All of this data is removed when you delete the plugin.

---

**Still have questions?** Visit our support forum or check the comprehensive user guide for more detailed information.
