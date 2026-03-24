# GrindSite - AI Coding Guidelines

## Project Overview
GrindSite is a lightweight, flat-file content management system built with PHP and SQLite. It features a modern block-based editor, theme system, and admin panel using Tailwind CSS + Alpine.js.

## Architecture
- **Front Controller**: `src/index.php` handles all requests, caching, and routing
- **Routing**: `lib/front.php::resolve_front_request()` parses URLs into page types (home, category, single, tag, search, 404)
- **Themes**: PHP templates in `src/theme/` with layout.php, single.php, home.php, etc.
- **Admin Panel**: `src/admin/` with CRUD interfaces for posts, categories, etc.
- **Database**: SQLite with tables for posts, categories, tags, settings, etc.
- **Content Storage**: Posts use block-based JSON structure in `content` column

## Key Components
- `lib/functions.php`: Core utilities (render_content, get_option, resolve_url, h for escaping)
- `lib/db.php`: Database connection and queries
- `lib/front.php`: Frontend routing and data fetching
- `src/admin/bootstrap.php`: Admin initialization
- Themes: `src/theme/default/` etc. with PHP templates

## Content System
- **Block Editor**: Content stored as JSON with `{"blocks": [...]}` structure
- **Rendering**: `render_content()` function processes blocks into HTML
- **Block Types**: header, paragraph, image, list, html, quote, code, template
- **Themes can override**: `theme_render_block()` function for custom block rendering

## Development Workflow
- **Setup**: Upload files, run installer, access admin panel
- **Building CSS**: `npm run build` compiles Tailwind for admin and themes
- **Watch Mode**: `npm run watch:admin` or `npm run watch:theme:default` for development
- **Themes**: Each theme has its own Tailwind config and input CSS
- **Deployment**: Flat-file system, no complex build process required

## Coding Conventions
- **Escaping**: Always use `h()` function for HTML output
- **URLs**: Use `resolve_url()` for asset paths and internal links
- **Settings**: Access via `get_option('key')`
- **Database**: Use prepared statements, global `$pdo` object
- **File Paths**: Absolute paths with `ROOT_PATH` constant
- **Sessions**: Unique session names per installation path
- **Security**: Input validation, CSRF protection, file upload restrictions

## Common Patterns
- **Page Rendering**: Check `pageType` in themes (home, single, category, etc.)
- **Context Data**: `$ctx` array contains page type and data
- **Navigation**: `get_nav_menus()` for menu items
- **Banners**: `get_front_banners()` for contextual banners
- **Image Processing**: Automatic resize/compression/Exif removal on upload
- **Caching**: Page-level HTML caching with MD5 keys
- **Maintenance Mode**: `.maintenance` file triggers maintenance page

## File Structure Examples
- **Theme Layout**: `src/theme/default/layout.php` includes head, content, footer
- **Single Post**: `src/theme/default/single.php` renders `$pageData['post']`
- **Admin CRUD**: `src/admin/posts.php` handles list/create/edit/delete
- **API Endpoints**: `src/admin/api/` for AJAX operations
- **Assets**: `src/assets/` for compiled CSS/JS, `src/assets/uploads/` for user files

## Integration Points
- **External Dependencies**: Composer autoloader in `vendor/`, but minimal PHP deps
- **JavaScript**: Alpine.js for admin interactivity, Prism.js for syntax highlighting
- **CSS**: Tailwind CSS with custom configs per theme
- **Image Handling**: GD library for processing uploads
- **Email**: `lib/mail.php` for notifications
- **Logging**: `lib/logger.php` with file and database logging

## Debugging
- **Debug Mode**: `get_option('debug_mode')` enables verbose logging
- **Logs**: Stored in `data/logs/` with rotation
- **Error Handling**: Try-catch blocks, custom error pages
- **Preview Mode**: Admin can preview unpublished content with tokens

## Deployment Notes
- **Flat-File**: No server-side compilation needed
- **Security**: `.htaccess` blocks sensitive files, restricts uploads
- **Updates**: One-click update via admin panel
- **Migration**: Automatic path updates on server moves
- **Backup**: Database export/import functionality

## Theme Development
- **Template Files**: PHP files with access to global variables
- **Functions**: `src/theme/default/functions.php` for custom logic
- **Overrides**: Per-post/category theme selection
- **Assets**: Theme-specific CSS/JS in theme directories
- **Blocks**: Override `theme_render_block()` for custom rendering

## Admin Development
- **Bootstrap**: `src/admin/bootstrap.php` initializes session/auth
- **Views**: `src/admin/views/` for reusable components
- **API**: RESTful endpoints in `src/admin/api/`
- **Skins**: Dark/light mode support
- **Validation**: Server-side validation with error messages
