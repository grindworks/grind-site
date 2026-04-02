# GrindSite AI & Developer Rules

This document defines the strict coding standards, architectural principles, and technology stack for GrindSite CMS. All AI models and developers contributing to this project MUST adhere to these rules to maintain an "S-Rank" code quality.

## 1. System Requirements

Do not use deprecated functions or polyfills for older versions. Assume the following environment:

- **PHP:** 8.3.0 or higher. Use modern PHP features (typed properties, match expressions, readonly classes).
- **Database:** SQLite 3.27.0+ (FTS5 extension is highly recommended for search).
- **Required PHP Extensions:** `pdo_sqlite`, `mbstring`, `zip`, `gd` (or `imagick`), `dom`, `libxml`, `openssl`, `json`.

## 2. Core Architecture & Portability

GrindSite is designed to be the simplest, most portable flat-file CMS.

- **Zero Configuration:** Never require manual database setup (e.g., MySQL credentials). Use SQLite in the `data/` directory.
- **Absolute Portability:** The CMS must work flawlessly if moved to a different directory or domain.
  - Never hardcode absolute URLs in the database.
  - Use `Routing::convertToDbUrl()` to replace base URLs with the `{{CMS_URL}}` placeholder before saving to the DB.
  - Use `grinds_url_to_view()` or `resolve_url()` when rendering URLs to the frontend.
- **Filesystem Security:** Rely on `.htaccess` and `nginx.conf.sample` to block direct access to `.php` files in `assets/uploads/`, `theme/`, and `plugins/`.

## 3. Coding Standards & Comments

Write clean, self-documenting code. Avoid redundant comments.

- **Git-Standard English Comments:** Use the **imperative mood** for comments and commit messages.
  - ✅ Good: `// Validate user input` , `// Return active plugins`
  - ❌ Bad: `// Validates user input` , `// This function returns active plugins`
- **Remove Obvious Comments:** Do not explain _what_ the code does if it is obvious. Explain _why_ if it involves complex business logic or security workarounds.
- **DRY Principle:** Do not duplicate logic. Extract shared logic into helper functions inside `src/lib/functions/`.

### PHP Example:

// Bad
/_ This function gets the user by ID _/
function getUser($id) { ... }

// Good
/\*_ Fetch user data by ID. _/
function grinds_get_user(int $id): ?array { ... }

## 4. Frontend Standards (S-Rank UI)

The admin panel and default theme rely on **Tailwind CSS** and **Alpine.js**. Do not use jQuery or heavy external UI libraries unless absolutely necessary.

### Tailwind CSS

- Use utility classes for all styling. Avoid writing custom CSS in `<style>` blocks.
- Use semantic color variables defined in the system (e.g., `text-theme-primary`, `bg-theme-surface`, `border-theme-border`).
- Ensure responsive design using `sm:`, `md:`, `lg:` prefixes.

### Alpine.js

- Manage state using `x-data`.
- Avoid mixing Vanilla JS DOM manipulation (e.g., `document.getElementById`) when Alpine.js data binding (`x-model`, `x-bind`, `x-text`) can achieve the same result.
- Use `x-cloak` to prevent Flash of Unstyled Content (FOUC).

### Icons (Heroicons)

- **NEVER** inline raw SVG code for icons in the HTML.
- **ALWAYS** load icons from the SVG sprite (`assets/img/sprite.svg`).
- Reference the icon ID matching the Heroicon name (e.g., `outline-home`, `outline-cog-6-tooth`).

### UI Code Example:

<!-- Good: Clean, semantic Tailwind + Alpine + Sprite SVG -->
<div x-data="{ isOpen: false }" class="relative">
  <button
    type="button"
    @click="isOpen = !isOpen"
    class="flex items-center gap-2 px-4 py-2 bg-theme-surface border border-theme-border rounded-theme text-sm font-bold text-theme-text hover:text-theme-primary transition-colors"
  >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
    </svg>
    <span x-text="isOpen ? 'Close' : 'Open'"></span>
  </button>

  <div x-show="isOpen" x-cloak class="absolute top-full mt-2 p-4 bg-theme-surface shadow-theme rounded-theme">
    <!-- Content -->
  </div>
</div>

## 5. Security & Performance Guidelines

- **XSS Protection:** Always escape output using the `h()` function (wrapper for `htmlspecialchars`). Use `grinds_sanitize_html()` for rich text.
- **CSRF Protection:** All forms and API mutations (POST/PUT/DELETE) MUST include and validate a CSRF token.
  - HTML: `<input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">`
  - JS (Fetch): Include `csrf_token: window.grindsCsrfToken` in the payload.
- **SQL Injection:** Always use PDO prepared statements. Never concatenate variables directly into SQL strings.
- **N+1 Query Problem:** Optimize database queries. When fetching a list of posts, fetch related tags or metadata in a single batch query rather than looping inside the view.
- **Timing Attacks:** Use `hash_equals()` when comparing tokens or hashes (e.g., CSRF tokens, password verification fallbacks).

## 6. AI Interaction & Language Preferences

- **Internal Thinking:** Perform your internal reasoning and step-by-step thinking in **English** to ensure maximum logical accuracy and coding quality.
- **Code Comments:** Write all in-code comments, code suggestions, and commit messages STRICTLY in **English** (Imperative mood). **NEVER use Japanese for in-code comments**, even if the user prompts in Japanese.
- **Chat Responses:** Output all chat explanations, summaries, and conversational responses to the user in **Japanese**.
