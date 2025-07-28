# Installation Guide - Ultimate Ajax DataTable

## System Requirements

Before installing, ensure your system meets these requirements:

### Minimum Requirements
- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Browser**: Modern browser with JavaScript enabled
- **Memory**: 128MB PHP memory limit (256MB recommended)
- **Disk Space**: 10MB free space

### Recommended Environment
- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Memory**: 256MB+ PHP memory limit
- **Server**: Apache or Nginx with mod_rewrite enabled

## Installation Methods

### Method 1: WordPress Admin (Recommended)

#### Step 1: Access Plugin Installation
1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Click the **Upload Plugin** button at the top

#### Step 2: Upload Plugin
1. Click **Choose File** and select the plugin zip file
2. Click **Install Now**
3. Wait for the upload and installation to complete

#### Step 3: Activate Plugin
1. Click **Activate Plugin** when installation completes
2. You'll see a success message confirming activation

### Method 2: FTP/cPanel Upload

#### Step 1: Extract Plugin Files
1. Download the plugin zip file to your computer
2. Extract the zip file to reveal the `ultimate-ajax-datatable` folder

#### Step 2: Upload via FTP
1. Connect to your website via FTP client (FileZilla, etc.)
2. Navigate to `/wp-content/plugins/` directory
3. Upload the entire `ultimate-ajax-datatable` folder

#### Step 3: Activate in WordPress
1. Go to your WordPress admin dashboard
2. Navigate to **Plugins > Installed Plugins**
3. Find "Ultimate Ajax DataTable" and click **Activate**

### Method 3: cPanel File Manager

#### Step 1: Access File Manager
1. Log in to your hosting cPanel
2. Open **File Manager**
3. Navigate to `public_html/wp-content/plugins/`

#### Step 2: Upload and Extract
1. Click **Upload** and select the plugin zip file
2. After upload, select the zip file and click **Extract**
3. Delete the zip file after extraction

#### Step 3: Activate Plugin
1. Go to your WordPress admin dashboard
2. Navigate to **Plugins > Installed Plugins**
3. Find "Ultimate Ajax DataTable" and click **Activate**

## Post-Installation Setup

### Step 1: Verify Installation
After activation, you should see:
- New menu item **"Ultimate Ajax DataTable"** in your WordPress admin sidebar
- Success message confirming plugin activation
- No error messages or warnings

### Step 2: Initial Configuration
1. Go to **Ultimate Ajax DataTable > Settings**
2. Review default settings (they work well for most sites)
3. Adjust settings if needed:
   - **Enabled Post Types**: Choose which post types to enhance
   - **Items Per Page**: Set default number of items to display
   - **Enable Features**: Toggle search, filters, bulk actions

### Step 3: Generate Test Data (Recommended)
To see the plugin in action with realistic data:
1. Go to **Ultimate Ajax DataTable > Test Data**
2. Click **"Create Test Posts"** to generate 50 sample posts
3. Wait for the success message
4. This provides plenty of data to test all features

### Step 4: Try Enhanced View
1. Navigate to **Posts > All Posts**
2. Look for the blue notice: **"Ultimate Ajax DataTable Available"**
3. Click **"Try Enhanced View"** to activate the enhanced interface
4. Experience the beautiful new interface!

## Verification Checklist

### ✅ Plugin Activation
- [ ] Plugin appears in **Plugins > Installed Plugins**
- [ ] Plugin status shows "Active"
- [ ] No error messages during activation
- [ ] New admin menu "Ultimate Ajax DataTable" appears

### ✅ Basic Functionality
- [ ] Can access **Ultimate Ajax DataTable > Settings**
- [ ] Can generate test data via **Test Data** page
- [ ] Enhanced view option appears on **Posts > All Posts**
- [ ] Enhanced interface loads without errors

### ✅ Enhanced Interface
- [ ] Search box works and filters posts in real-time
- [ ] Status badges display correctly with colors
- [ ] Pagination works smoothly
- [ ] "Add New Post" button appears in correct position
- [ ] Can switch back to standard view

## Troubleshooting Installation Issues

### Plugin Won't Activate

#### Possible Causes & Solutions:
1. **PHP Version Too Old**
   - Check your PHP version in hosting control panel
   - Upgrade to PHP 7.4 or higher
   - Contact your hosting provider for assistance

2. **WordPress Version Too Old**
   - Update WordPress to version 5.8 or higher
   - Go to **Dashboard > Updates** to update

3. **Memory Limit Too Low**
   - Increase PHP memory limit to 256MB
   - Add `ini_set('memory_limit', '256M');` to wp-config.php
   - Contact hosting provider to increase limit

4. **Plugin Conflicts**
   - Deactivate other plugins temporarily
   - Activate Ultimate Ajax DataTable
   - Reactivate other plugins one by one to identify conflicts

### Upload Errors

#### "File is too large" Error:
1. **Increase Upload Limits**
   - Contact hosting provider to increase limits
   - Or use FTP upload method instead

2. **Use Alternative Method**
   - Try FTP upload instead of admin upload
   - Use cPanel File Manager method

#### "Destination folder already exists" Error:
1. **Remove Old Installation**
   - Delete existing `ultimate-ajax-datatable` folder via FTP
   - Upload fresh copy of the plugin

### Enhanced View Not Loading

#### JavaScript Errors:
1. **Check Browser Console**
   - Press F12 to open developer tools
   - Look for JavaScript errors in Console tab
   - Report any errors to support

2. **Clear Browser Cache**
   - Clear browser cache and cookies
   - Try in incognito/private browsing mode
   - Try different browser

3. **Plugin Conflicts**
   - Deactivate other plugins temporarily
   - Test if enhanced view loads
   - Identify conflicting plugin

### Performance Issues

#### Slow Loading:
1. **Reduce Items Per Page**
   - Go to **Ultimate Ajax DataTable > Settings**
   - Reduce "Items Per Page" to 10 or 15
   - Test performance improvement

2. **Enable Caching**
   - Ensure caching is enabled in plugin settings
   - Clear any existing cache

3. **Check Server Resources**
   - Contact hosting provider about server performance
   - Consider upgrading hosting plan

## Security Considerations

### File Permissions
Ensure proper file permissions after installation:
- **Folders**: 755 or 750
- **Files**: 644 or 640
- **wp-config.php**: 600

### Security Plugins
The plugin is compatible with popular security plugins:
- Wordfence
- Sucuri Security
- iThemes Security
- All In One WP Security

### Firewall Rules
If you use a firewall, ensure these are allowed:
- AJAX requests to `/wp-admin/admin-ajax.php`
- REST API requests to `/wp-json/uadt/v1/`

## Hosting-Specific Notes

### Shared Hosting
- Plugin is optimized for shared hosting environments
- Uses minimal server resources
- Works with most shared hosting providers

### Managed WordPress Hosting
- Compatible with WP Engine, Kinsta, SiteGround, etc.
- May need to clear hosting-level cache after installation

### VPS/Dedicated Servers
- Full compatibility with all server configurations
- Can handle larger datasets efficiently

## Getting Help

### Documentation
- **User Guide**: Comprehensive usage instructions
- **FAQ**: Answers to common questions
- **Developer Docs**: Technical customization details

### Support Channels
- **WordPress Support Forum**: Community support
- **GitHub Issues**: Bug reports and feature requests
- **Email Support**: Direct developer support

### Before Contacting Support
Please provide:
1. WordPress version
2. PHP version
3. Plugin version
4. Description of the issue
5. Steps to reproduce
6. Any error messages
7. Browser and version

## Next Steps

After successful installation:
1. **Read the User Guide** for detailed feature explanations
2. **Explore Settings** to customize the plugin for your needs
3. **Generate Test Data** to see all features in action
4. **Try Enhanced View** to experience the improved interface
5. **Provide Feedback** to help improve the plugin

---

**Congratulations!** You've successfully installed Ultimate Ajax DataTable. Enjoy your enhanced WordPress posts management experience!
