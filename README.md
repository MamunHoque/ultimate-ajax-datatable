# Ultimate Ajax DataTable

A practical WordPress plugin that enhances admin list tables with modern AJAX DataTable interface and powerful multiple filter options. Designed for broad hosting compatibility and real user needs.

## Features

- **Multiple Filter Types**: Text search, dropdown selections, date ranges, custom fields
- **Saved Filter Presets**: Power users can save and reuse common filters
- **Export Functionality**: CSV/Excel export with current filters applied
- **Bulk Operations**: Select multiple items for batch actions
- **Fast Performance**: Handles 5,000+ records smoothly
- **Mobile Responsive**: Works on tablets and mobile devices
- **Broad Compatibility**: Works on shared hosting, PHP 7.4+, WordPress 5.8+

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
