# GrindSite Core AI Rules

> Shared rules for every Grind product: [`../AGENTS.md`](../AGENTS.md). This file adds GrindSite Core rules and wins where the two differ.

## 1. Environment & Architecture

- **PHP 8.3+** & **SQLite 3.27+** (FTS5 recommended).
- **Zero Config:** No DB credentials. Use SQLite in `data/`.
- **Absolute Portability (CRITICAL):**
  - **Never hardcode absolute URLs or physical server paths.**
  - Save URLs to DB: Use `Routing::convertToDbUrl()` (`{{CMS_URL}}`).
  - Render URLs: Use `grinds_url_to_view()` or `resolve_url()`.
  - **No Environment Lock-in:** Do NOT auto-write environment-specific paths (e.g., `RewriteBase /subdir/` in `.htaccess`) during installation or runtime. The system MUST work instantly after moving files via FTP to any domain or subdirectory.
- **SQLite JSON Compatibility:** Do NOT assume JSON1 extension is always enabled.
  - For simple updates, provide a PHP fallback (e.g., fetch, merge in PHP, then update) if `json_patch()` is unavailable.
  - For searching or sorting, NEVER use JSON functions (`json_extract`, etc.) in `WHERE` or `ORDER BY` clauses. Frequently queried data MUST be extracted to dedicated relational columns or tables to maintain performance and compatibility across all legacy SQLite environments.

## 2. Coding Standards & Multi-Layered Security

- **Defense in Depth (FAIL-SAFE):**
  - **Do NOT flag multi-layered security as "redundant code."**
  - Even if input is validated in the frontend or API controller, it MUST be re-validated or sanitized in the Repository, Manager, and Library tiers.
  - Redundant-looking checks are intentional fail-safes to protect data integrity and prevent security breaches (XSS, Injection, SSRF).
  - ALWAYS use prepared statements (`$pdo->prepare()`). Never interpolate variables directly into SQL.
- **English Only Comments (Core):** Use imperative mood (e.g., `// Validate input`). NEVER use Japanese in core system code or comments.
  - _Exception:_ Plugin files (in `src/plugins/`), `.htaccess` files, and files meant for user configuration may include bilingual comments (English and Japanese) in DocBlocks and instructional inline comments to assist users.
- **Translation Keys (i18n):**
  - **Core/Admin:** Use snake_case logical keys (e.g., `btn_save_changes`, `st_general_desc`).
  - **Theme/Frontend:** MUST use natural English phrases (WordPress-like) as keys (e.g., `Read More`, `Back to Home`). This is intentional to ensure a zero learning curve for end-users developing their own themes.
- **Performance:** Favor bulk DB fetches to avoid N+1 queries. Preload metadata whenever processing arrays of files or posts.
- **Modern PHP:** Use strict typing, type hints, return types, and PHP 8.x features (match expressions, nullsafe operators) where applicable.
- **Security & XSS Prevention:**
  - Use custom `h()` for escaping and `grinds_sanitize_html()` for rich text.
  - **CRITICAL:** When outputting translated strings using `_t()` inside HTML attributes (e.g., `placeholder`, `title`, `alt`, `value`, `aria-label`), they MUST ALWAYS be escaped with `h()` like `h(_t('key'))`. Double quotes in translation files will cause attribute injection/layout breaks if unescaped.

## 3. Frontend (S-Rank UI)

- **Alpine.js:** Use `x-data`, `x-model`. Avoid Vanilla JS DOM manipulation. Use `x-cloak`.
- **Styling:** Use Tailwind CSS utility classes. Avoid custom CSS in `<style>` blocks.
- **Icons:** Load from `assets/img/sprite.svg`. NEVER inline raw SVGs.
- **Stability:** Ensure UI transitions do not cause layout shifts (CLS). Use absolute positioning for loaders inside buttons.

## 4. AI Directives

- **Deep Contextual Analysis:** Do not act like a naive static analysis tool. Analyze actual data flow and the _intent_ of multi-tiered checks before suggesting "optimizations."
- **Respect Design Philosophy:** Maintain the "Single File" nature of critical tools (like `install.php`) and the "Zero-Config" portability.
- **Language:** Output chat explanations in **Japanese**. Code & comments strictly in **English**.

## 5. Security Rules Learned from Audits

- **Escape at output, not only at save.** `grinds_sanitize_post_content_array()` only rewrites values containing `<` and URL keys, so `'` and `"` pass through. Every block or post value concatenated into HTML MUST go through `h()` or a whitelist: core `lib/BlockRenderer.php`, theme `<folder>_render_block()` and `theme/*/blocks/*.php` alike.
- **`h()` does not double-encode.** A value that JavaScript reads back (`getAttribute`, `dataset`) arrives entity-decoded: insert it with `textContent`, never `innerHTML`. Values embedded in `<script>` use `json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP)`.
- **Authorization comes from the database.** `require_login()` reloads role and permissions on every request. Never trust a role cached elsewhere in `$_SESSION`.
- **Outbound HTTP** only through `grinds_fetch_url()` / `grinds_download_file()`. Downloads pass `block_private_ip => true`, which resolves DNS in PHP and pins every redirect hop. Never call `curl_*` or `file_get_contents()` on remote URLs directly.
- **Host-derived caches:** `BASE_URL` comes from the Host header, so every cache file built from it must include the base URL in its key.
- **Uploads:** never allow types a browser renders as active content (HTML, XML). SVG stays admin-only behind the sanitizer.
- **Plugins on `grinds_init`** run before `admin/bootstrap.php`. Core functions that security plugins call must be loaded in `admin/bootstrap_base.php`.
- **Hyphenated theme folders:** GrindSite calls `<folder>_render_block()` with hyphens replaced by underscores (`neo-minimalist` → `neo_minimalist_render_block()`).

## 6. Updates & Releases

- **Releases are performed by the AI agent on the owner's request.** Get explicit approval before anything is pushed or published. Run the script with your own attribution: `CO_AUTHOR="<model name> <noreply@anthropic.com>" ./bin/release.sh vX.Y.Z "Title"`.
- **Release only with `./bin/release.sh vX.Y.Z "Title"`** after writing `bin/release_template.txt`: line 2 is `vX.Y.Z - Title`, the body starts on line 5, English first, then Japanese. The script checks, tags, creates the GitHub Release, verifies the uploaded asset hash and pushes `main` last, so sites never see a release whose ZIP is not downloadable yet.
- **Installed sites poll `https://raw.githubusercontent.com/grindworks/grind-site/main/update.json`** (hardcoded in every release). Never rename the repository, and never create another repository named `grind-site-core` (that would break the redirect v1.7.3 relies on).
- **`update.json` MUST carry `sha256`.** v1.7.3+ updaters refuse an update without it.
- **The installed version performs the update.** Changes to `lib/updater.php`, `admin/api/update_process.php` or `admin/views/settings/update.php` only take effect from the next release. Release notes must describe what users of older versions will see, quoting the exact UI labels of those versions.
- **Updates never delete files.** Code must not depend on old files being removed.
- **Bundled themes and skins** are updated only when "Do not update themes and skins" is unchecked (the default from v1.7.4; in v1.7.3 and earlier the old option is checked by default). Release notes for a theme security fix must list the file paths so users can replace them manually.
- **Keep `README.md` "Updating" in sync** with the updater behavior, in English and Japanese.

## 7. Verification Before Release

- `php tools/check/run_all.php` (internal, not in Git). It includes `check_block_escaping.php`, which renders every block type in every bundled theme with attribute-breaking values. `release.sh` runs it and `php -l` automatically.
- **Updater changes:** run the `dry_run` / `backup` / `apply` / `cleanup` steps of `admin/api/update_process.php` with the theme option both checked and unchecked, and repeat with the previous release's updater code.
- **Local MAMP on macOS 27:** PHP crashes when curl resolves `github.com` by itself, so one-click updates from v1.6.9 and earlier fail there. This does not happen on production servers; do not "fix" it in GrindSite.
