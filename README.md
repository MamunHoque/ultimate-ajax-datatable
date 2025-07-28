# Ultimate Ajax DataTable

**Transform your WordPress posts management with a modern, lightning-fast data table interface.**

## 🚀 Features

### ⚡ Enhanced Posts Management
- **Real-time search** - Filter posts instantly as you type
- **Advanced filtering** - Filter by author, status, date range, categories, and tags
- **Beautiful design** - Modern interface with gradients, animations, and hover effects
- **Lightning fast** - AJAX-powered with intelligent caching
- **Responsive design** - Works perfectly on all devices

### 🎯 Professional Interface
- **Status badges** - Color-coded post statuses with icons
- **Smart pagination** - Smooth navigation with page information
- **Bulk actions** - Manage multiple posts efficiently
- **Search suggestions** - Real-time autocomplete for better UX
- **Loading states** - Beautiful loading animations and error handling

### 🔧 WordPress Integration
- **Seamless integration** - Works with existing WordPress posts page
- **Optional enhancement** - Choose between standard and enhanced views
- **Proper positioning** - Add New button in correct WordPress location
- **Security first** - Comprehensive security measures and capability checks
- **Performance optimized** - Intelligent caching and query optimization

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Modern browser with ES2020 support

## Installation

1. Upload the plugin files to `/wp-content/plugins/ultimate-ajax-datatable/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'DataTable Manager' in the admin menu to configure settings

## Development

### Technology Stack

- **Backend**: PHP 7.4+, WordPress REST API
- **Frontend**: React 18, TypeScript, React Query
- **Styling**: Tailwind CSS 3, clean design
- **Build**: Webpack optimized for WordPress
- **Testing**: PHPUnit, Jest, React Testing Library

### Development Setup

1. Clone the repository
2. Install dependencies: `npm install`
3. Start development server: `npm run dev`
4. Build for production: `npm run build`

## API Endpoints

### GET /wp-json/uadt/v1/posts

Retrieve posts with filtering options.

**Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 25, max: 100)
- `search` (string): Search term
- `post_type` (string): Post type (default: 'post')
- `author` (string): Author ID or name
- `status` (string): Post status (default: 'publish')
- `date_from` (string): Start date (Y-m-d format)
- `date_to` (string): End date (Y-m-d format)

### POST /wp-json/uadt/v1/posts/bulk

Perform bulk actions on posts.

**Parameters:**
- `action` (string): Action to perform ('publish', 'draft', 'trash', 'delete')
- `post_ids` (array): Array of post IDs

## Security

- Nonce validation for all AJAX calls
- Capability checks for all operations
- Input sanitization and validation
- CSRF protection
- SQL injection prevention with prepared statements
- Rate limiting for API endpoints

## Performance

- Handles 5,000+ records smoothly with pagination
- API responses <500ms for complex filters
- Bundle size <300KB gzipped
- Works well on shared hosting environments

## License

GPL v2 or later

## Support

For support and documentation, please visit the plugin settings page in your WordPress admin.

## Changelog

### 1.0.0
- Initial release
- Core plugin foundation
- Basic admin interface
- REST API with essential filtering
- Security framework
- Settings page
