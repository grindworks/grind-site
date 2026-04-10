# Theme Development Guidelines

## 1. Basic Rules

> [!WARNING]
> **DO NOT EDIT DEFAULT THEMES DIRECTLY**
> Default themes (e.g., `default`, `bootstrap`) may be overwritten during CMS updates.
> To create a custom theme, please **duplicate** the folder (e.g., copy `default` to `my-theme`) and edit the copy.

### CSS Files

> [!IMPORTANT]
> **CRITICAL RULE: Relative Paths Only**
> When referencing images or fonts inside CSS files (e.g., `background-image`), you **MUST** use relative paths relative to the CSS file location.
>
> - ✅ **Correct:** `url('../img/background.jpg')`
> - ❌ **Wrong:** `url('/theme/default/img/background.jpg')`
>
> **Reason:** If the CMS is installed in a subdirectory (e.g., `example.com/cms/`), absolute paths starting with `/` will point to the domain root, causing broken links.

- **Relative Paths**: Use relative paths (`../`) for all external resources (images, fonts, etc.).
- **No Absolute Paths**: Absolute paths (e.g., `/assets/...`) are **prohibited**. This ensures the theme works correctly even when the CMS is installed in a subdirectory.

### Dynamic Images

- **Inline Styles**: Do not define user-changeable images (such as post thumbnails, hero images, or banners) in CSS files.
- **Implementation**: Handle these images using the `style` attribute directly within your PHP templates.

#### Example

**Bad (CSS):**

```css
.hero {
  background-image: url('/assets/uploads/hero.jpg'); /* Do not do this */
}
```

<div class="hero" style="background-image: url('<?= h(resolve_url($post['thumbnail'])) ?>');">
    <h1><?= h($post['title']) ?></h1>
</div>
```

## 2. File Structure

Key files in the theme directory:

- `layout.php`: Main layout (header, footer, etc.). The `$content` variable contains the page HTML.
- `home.php`: Template for the homepage.
- `single.php`: Template for single posts.
- `page.php`: Template for static pages.
- `archive.php`: Template for lists (categories, tags, search results).
- `404.php`: Template for 404 Not Found errors.
- `functions.php`: Theme-specific logic and block renderers.
- `parts/`: Directory for reusable template parts (header, footer, sidebar, etc.).

## 3. Essential Functions & Variables

### Header & Footer Output

You must include the following functions in your theme's `layout.php`. These output the necessary CSS and JS for the CMS.

- `<?php grinds_head(); ?>` just before `</head>`
- `<?php grinds_footer(); ?>` just before `</body>`

### Escaping

Always use the `h()` function for HTML output to prevent XSS.

```php
<h1><?= h($post['title']) ?></h1>
```

### URL Generation

Use `resolve_url()` for internal links. For theme-specific static assets like images, use `grinds_theme_asset_url()` to correctly handle dynamic theme paths with default theme fallbacks.

```php
<a href="<?= resolve_url('/contact') ?>">Contact Us</a>
<img src="<?= grinds_theme_asset_url('img/logo.svg') ?>" alt="Logo">
```

### Rendering Content

Use `render_content()` to display post content (block editor data).

```php
<div class="post-content">
    <?= render_content($post['content']) ?>
</div>
```

### Custom Fields (`theme.json`)

You can define custom fields for your theme by creating a `theme.json` file in your theme directory.
These fields will automatically appear in the post editor sidebar without writing any PHP code.

```json
{
  "name": "My Custom Theme",
  "custom_fields": [
    {
      "name": "hero_subtitle",
      "label": "Hero Subtitle",
      "type": "text",
      "post_type": ["post", "page"]
    },
    {
      "name": "header_layout",
      "label": "Header Layout",
      "type": "select",
      "options": {
        "left": "Left Aligned",
        "center": "Centered"
      },
      "post_type": ["post", "page"]
    }
  ]
}
```

To retrieve and display the values in your template (e.g., `single.php`), use `get_post_meta()`:

```php
<?php $subtitle = get_post_meta($post['id'], 'hero_subtitle'); ?>
<?php if ($subtitle): ?><p><?= h($subtitle) ?></p><?php endif; ?>
```

## 4. Building Tailwind CSS

This CMS uses Tailwind CSS. To modify theme styles, use the following commands:

```bash
# First, you must add build scripts for your new theme inside `package.json`.
# Example:
# "build:theme:my-theme": "tailwindcss -c ./src/theme/my-theme/tailwind.config.js -i ./src/theme/my-theme/css/theme-input.css -o ./src/theme/my-theme/css/style.css --minify"
# "watch:theme:my-theme": "tailwindcss -c ./src/theme/my-theme/tailwind.config.js -i ./src/theme/my-theme/css/theme-input.css -o ./src/theme/my-theme/css/style.css --watch"

# Development (Watch for changes)
npm run watch:theme:my-theme

# Production Build (Minified)
npm run build:theme:my-theme
```
