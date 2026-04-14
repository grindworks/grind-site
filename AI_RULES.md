# GrindSite AI Rules

## 1. Environment & Architecture

- **PHP 8.3+** & **SQLite 3.27+** (FTS5 recommended).
- **Zero Config:** No DB credentials. Use SQLite in `data/`.
- **Absolute Portability (CRITICAL):**
  - **Never hardcode absolute URLs or physical server paths.**
  - Save URLs to DB: Use `Routing::convertToDbUrl()` (`{{CMS_URL}}`).
  - Render URLs: Use `grinds_url_to_view()` or `resolve_url()`.
  - **No Environment Lock-in:** Do NOT auto-write environment-specific paths (e.g., `RewriteBase /subdir/` in `.htaccess`) during installation or runtime. The system MUST work instantly after moving files via FTP to any domain or subdirectory.

## 2. Coding Standards & Multi-Layered Security

- **Defense in Depth (FAIL-SAFE):**
  - **Do NOT flag multi-layered security as "redundant code."**
  - Even if input is validated in the frontend or API controller, it MUST be re-validated or sanitized in the Repository, Manager, and Library tiers.
  - Redundant-looking checks are intentional fail-safes to protect data integrity and prevent security breaches (XSS, Injection, SSRF).
  - ALWAYS use prepared statements (`$pdo->prepare()`). Never interpolate variables directly into SQL.
- **English Only Comments (Core):** Use imperative mood (e.g., `// Validate input`). NEVER use Japanese in core system code or comments.
  - _Exception:_ Plugin files (in `src/plugins/`), `.htaccess` files, and files meant for user configuration may include bilingual comments (English and Japanese) in DocBlocks and instructional inline comments to assist users.
- **Performance:** Favor bulk DB fetches to avoid N+1 queries. Preload metadata whenever processing arrays of files or posts.
- **Modern PHP:** Use strict typing, type hints, return types, and PHP 8.x features (match expressions, nullsafe operators) where applicable.
- **Security:** Use custom `h()` for escaping and `grinds_sanitize_html()` for rich text.

## 3. Frontend (S-Rank UI)

- **Alpine.js:** Use `x-data`, `x-model`. Avoid Vanilla JS DOM manipulation. Use `x-cloak`.
- **Styling:** Use Tailwind CSS utility classes. Avoid custom CSS in `<style>` blocks.
- **Icons:** Load from `assets/img/sprite.svg`. NEVER inline raw SVGs.
- **Stability:** Ensure UI transitions do not cause layout shifts (CLS). Use absolute positioning for loaders inside buttons.

## 4. AI Directives

- **Deep Contextual Analysis:** Do not act like a naive static analysis tool. Analyze actual data flow and the _intent_ of multi-tiered checks before suggesting "optimizations."
- **Respect Design Philosophy:** Maintain the "Single File" nature of critical tools (like `install.php`) and the "Zero-Config" portability.
- **Language:** Output chat explanations in **Japanese**. Code & comments strictly in **English**.
