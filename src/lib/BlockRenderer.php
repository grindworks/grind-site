<?php

/**
 * Render JSON block data into HTML.
 * Convert structured block data into HTML string.
 */
if (!defined('GRINDS_APP')) exit;

class BlockRenderer
{
    private string $spriteUrl;
    private bool $allowUnsafe;
    private static bool $scriptsEnqueued = false;
    private bool $firstImageRendered = false;

    /**
     * Initialize the renderer.
     * Set up sprite URL and security settings.
     */
    public function __construct(bool $allowUnsafe = false)
    {
        $this->allowUnsafe = $allowUnsafe;
        $this->spriteUrl = resolve_url('assets/img/sprite.svg');

        // Enqueue frontend scripts only once per request
        if (!self::$scriptsEnqueued) {
            add_action('grinds_footer', [self::class, 'outputFooterScripts'], 20);
            self::$scriptsEnqueued = true;
        }
    }

    /**
     * Render JSON content into HTML.
     * Process blocks and return generated HTML string.
     */
    public function render($content)
    {
        if ($content === '' || $content === null) return '';

        if (is_array($content)) {
            $data = $content;
        } else {
            if (!is_string($content)) return '';
            $content = grinds_url_to_view($content);
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return nl2br($this->sanitizeText($content));
            }
        }

        if (!is_array($data) || !isset($data['blocks']) || !is_array($data['blocks'])) {
            return is_string($content) ? nl2br($this->sanitizeText($content)) : '';
        }

        $this->preloadImages($data['blocks']);
        $this->preloadPosts($data['blocks']);

        $html = '';
        $index = 0;
        $blocksCount = count($data['blocks']);

        while ($index < $blocksCount) {
            $block = $data['blocks'][$index];
            $block['_index'] = $index;

            if (($block['type'] ?? '') === 'password_protect') {
                $pwdData = $block['data'] ?? [];
                $password = $pwdData['password'] ?? '';
                $message = $pwdData['message'] ?? '';
                if ($message === '') {
                    $message = function_exists('_t') ? _t('ph_unlock_msg') : 'This content is password protected.';
                }

                $protectedHtml = '';
                $index++;
                while ($index < $blocksCount) {
                    $pBlock = $data['blocks'][$index];
                    $pBlock['_index'] = $index;
                    $protectedHtml .= $this->renderBlock($pBlock);
                    $index++;
                }

                $bypass = false;
                if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
                    $bypass = true;
                } elseif (!empty($_GET['preview']) && preg_match('/^[a-f0-9]{32}$/', $_GET['preview'])) {
                    global $pageData;

                    // 1. Skip file I/O if preview data is already loaded in memory
                    if (isset($pageData['post']['__expires_at'])) {
                        if ($pageData['post']['__expires_at'] > time()) {
                            $bypass = true;
                        }
                    } else {
                        // 2. Fallback: Validate file if accessed directly
                        $previewFile = defined('ROOT_PATH') ? ROOT_PATH . '/data/tmp/preview/preview_' . $_GET['preview'] . '.json' : '';
                        if ($previewFile && file_exists($previewFile)) {
                            $currentPostId = $pageData['post']['id'] ?? null;
                            $pData = @json_decode(file_get_contents($previewFile), true);
                            $previewIdMatch = ($currentPostId && isset($pData['id']) && $pData['id'] == $currentPostId) || (empty($currentPostId) && empty($pData['id']));
                            if (is_array($pData) && $previewIdMatch) {
                                if (!isset($pData['__expires_at']) || $pData['__expires_at'] > time()) {
                                    $bypass = true;
                                }
                            }
                        }
                    }
                }

                if ($bypass && $password !== '') {
                    $noticeLabel = function_exists('_t') ? _t('Notice') : 'Notice';
                    $bypassMsg = function_exists('_t') ? _t('Password protection bypassed (Admin / Preview mode).') : 'Password protection bypassed (Admin / Preview mode).';
                    $testBtnLabel = function_exists('_t') ? _t('Test Password Screen') : 'Test Password Screen';

                    $wrapperHtml = $this->renderPasswordProtectWrapper($password, $message, $protectedHtml);

                    $html .= "<div id='pwd-bypass-{$index}' class='cms-block-password-bypass my-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 rounded-r-theme text-sm shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3'>";
                    $html .= "<div><strong class='font-bold mr-1'>{$noticeLabel}:</strong>{$bypassMsg}</div>";
                    $html .= "<button type='button' onclick='document.getElementById(\"pwd-bypass-{$index}\").style.display=\"none\"; document.getElementById(\"pwd-content-{$index}\").style.display=\"none\"; document.getElementById(\"pwd-test-{$index}\").style.display=\"block\";' class='text-xs bg-white border border-yellow-400 text-yellow-800 px-3 py-1.5 rounded-theme hover:bg-yellow-100 transition-colors font-bold whitespace-nowrap shadow-sm'>{$testBtnLabel}</button>";
                    $html .= "</div>";

                    $html .= "<div id='pwd-test-{$index}' style='display:none;'>{$wrapperHtml}</div>";
                    $html .= "<div id='pwd-content-{$index}'>{$protectedHtml}</div>";
                } else {
                    $html .= ($password !== '') ? $this->renderPasswordProtectWrapper($password, $message, $protectedHtml) : $protectedHtml;
                }
                break;
            }

            $html .= $this->renderBlock($block);
            $index++;
        }

        return $html;
    }

    /**
     * Output unified frontend script block for interactive elements.
     * Keeps the main HTML body completely clean of script tags.
     */
    public static function outputFooterScripts()
    {
        $root = defined('ROOT_PATH') ? ROOT_PATH : '';

        // KaTeX
        $katex_css = file_exists($root . '/assets/css/vendor/katex.min.css') ? resolve_url('assets/css/vendor/katex.min.css') : 'https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/katex.min.css';
        $katex_js = file_exists($root . '/assets/js/vendor/katex.min.js') ? resolve_url('assets/js/vendor/katex.min.js') : 'https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/katex.min.js';
        $katex_auto = file_exists($root . '/assets/js/vendor/auto-render.min.js') ? resolve_url('assets/js/vendor/auto-render.min.js') : 'https://cdn.jsdelivr.net/npm/katex@0.16.10/dist/contrib/auto-render.min.js';

        // Prism.js
        $prism_css = file_exists($root . '/assets/css/vendor/prism-tomorrow.min.css') ? resolve_url('assets/css/vendor/prism-tomorrow.min.css') : 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css';
        $prism_js = file_exists($root . '/assets/js/vendor/prism.min.js') ? resolve_url('assets/js/vendor/prism.min.js') : 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js';
        $prism_auto = file_exists($root . '/assets/js/vendor/prism-autoloader.min.js') ? resolve_url('assets/js/vendor/prism-autoloader.min.js') : 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js';

        // JavaScript loader URL
        $loader_js = resolve_url('assets/js/dynamic_blocks_loader.js');

        // Output configuration JSON and load external script
        echo <<<HTML
<script>
window.grindsDynamicConfig = {
    katex_css: "{$katex_css}",
    katex_js: "{$katex_js}",
    katex_auto: "{$katex_auto}",
    prism_css: "{$prism_css}",
    prism_js: "{$prism_js}",
    prism_auto: "{$prism_auto}"
};
</script>
<script src="{$loader_js}" defer></script>
HTML;
    }

    /**
     * Preload image metadata.
     * Extract URLs and fetch metadata for SEO/performance.
     */
    private function preloadImages($blocks)
    {
        if (!function_exists('grinds_preload_image_meta')) return;

        $urls = self::extractImages($blocks);

        if (!empty($urls)) {
            grinds_preload_image_meta($urls);
        }
    }

    /**
     * Extract all image URLs from blocks.
     * Return array of unique image URLs found in block data.
     */
    public static function extractImages($blocks)
    {
        $urls = [];
        if (empty($blocks) || !is_array($blocks)) return $urls;

        $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

        // Uses grinds_extract_urls for robust, single-pass extraction
        $candidates = grinds_extract_urls(['blocks' => $blocks]);

        foreach ($candidates as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, $imgExts)) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Preload post data for internal cards.
     * Batch fetch posts to optimize database queries.
     */
    private function preloadPosts($blocks)
    {
        global $grinds_post_cache;
        if (!isset($grinds_post_cache)) $grinds_post_cache = [];
        $pdo = App::db();
        if (!$pdo) return;

        $ids = [];
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'internal_card' && !empty($block['data']['id'])) {
                $id = (int)$block['data']['id'];
                if (!array_key_exists($id, $grinds_post_cache)) {
                    $ids[] = $id;
                }
            }
        }

        if (!empty($ids)) {
            $ids = array_unique($ids);
            $repo = new PostRepository($pdo);

            $status = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) ? 'all' : 'published';

            $posts = $repo->fetch([
                'ids' => $ids,
                'status' => $status
            ]);

            $fetchedIds = [];
            foreach ($posts as $post) {
                $id = (int)$post['id'];
                $grinds_post_cache[$id] = $post;
                $fetchedIds[] = $id;
            }

            $missingIds = array_diff($ids, $fetchedIds);
            foreach ($missingIds as $missingId) {
                $grinds_post_cache[$missingId] = null;
            }
        }
    }

    /**
     * Sanitize text for HTML output.
     * Apply HTML sanitization based on security settings.
     */
    public function sanitizeText($text)
    {
        return grinds_sanitize_html($text, $this->allowUnsafe);
    }

    /**
     * Render a single block.
     * Dispatch rendering to theme, template, or core handler.
     */
    private function renderBlock($block)
    {
        $type = $block['type'] ?? 'unknown';

        // Ignore nested password_protect blocks to prevent rendering issues.
        if ($type === 'password_protect') {
            return '';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $type)) {
            return '';
        }

        $data = $block['data'] ?? [];

        global $activeTheme;
        $currentTheme = $activeTheme ?? 'default';
        $themeRenderer = $currentTheme . '_render_block';

        $pathFixer = function ($text) {
            return $this->sanitizeText($text);
        };

        if (function_exists($themeRenderer)) {
            $custom_html = $themeRenderer($block, $pathFixer, $this->allowUnsafe);
            if ($custom_html !== null) {
                return $custom_html;
            }
        }

        $themeBlockDir = ROOT_PATH . '/theme/' . $currentTheme . '/blocks/';
        $templateFile = $themeBlockDir . $type . '.php';

        if (file_exists($templateFile)) {
            return $this->renderTemplate($templateFile, $data, $this->allowUnsafe);
        }

        if (function_exists('theme_render_block')) {
            $custom_html = call_user_func('theme_render_block', $block, $pathFixer, $this->allowUnsafe);
            if ($custom_html !== null) {
                return $custom_html;
            }
        }

        $coreHtml = $this->renderCoreBlock($block, $pathFixer, $this->allowUnsafe);
        if ($coreHtml !== null) {
            return $coreHtml;
        }

        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
            return '<div style="border: 2px dashed #ccc; padding: 10px; color: #888; background: #f9f9f9; text-align: center; margin: 20px 0; font-family: monospace; font-size: 12px;">[Undefined Block: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ']</div>';
        }

        return '';
    }

    /**
     * Render a block using a template file.
     * Extract data and include the template in output buffer.
     */
    private function renderTemplate($_grinds_sys_target_file, $__data, $allowUnsafe = false)
    {
        ob_start();
        $__data['allowUnsafe'] = $allowUnsafe;
        extract($__data, EXTR_SKIP);
        include $_grinds_sys_target_file;
        return ob_get_clean();
    }

    /**
     * Render a core block as a fallback.
     * Handle built-in block types when no theme override exists.
     */
    private function renderCoreBlock($block, $pathFixer, $allowUnsafe = false)
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];
        $commonClass = "cms-block-" . $type;
        $spriteUrl = $this->spriteUrl;

        switch ($type) {

            case 'before_after':
                $beforeUrl = resolve_url($data['beforeUrl'] ?? '');
                $afterUrl = resolve_url($data['afterUrl'] ?? '');
                if (!$beforeUrl || !$afterUrl) return '';
                $beforeLabel = h($data['beforeLabel'] ?? '');
                $afterLabel = h($data['afterLabel'] ?? '');
                $uid = 'ba-' . bin2hex(random_bytes(4));

                // Use Alpine.js to manage slider state and avoid inline scripts for CSP compliance.
                $html = "<div class='{$commonClass} my-10 max-w-4xl mx-auto'>";
                $html .= "<div x-data=\"{ sliderPos: 50 }\" id='{$uid}' class='relative w-full overflow-hidden rounded-theme shadow-theme select-none touch-none bg-gray-100 aspect-video'>";

                // Render after image
                $html .= get_image_html($afterUrl, ['class' => 'absolute inset-0 w-full h-full object-cover pointer-events-none', 'draggable' => 'false']);
                if ($afterLabel) {
                    $html .= "<div class='absolute top-4 right-4 bg-black/50 text-white px-3 py-1 rounded-theme text-xs font-bold backdrop-blur-sm z-0'>{$afterLabel}</div>";
                }

                // Render before image
                $html .= "<div class='before-wrapper absolute inset-0 w-full h-full pointer-events-none' :style=\"`clip-path: inset(0 \${100 - sliderPos}% 0 0);`\">";
                $html .= get_image_html($beforeUrl, ['class' => 'absolute inset-0 w-full h-full object-cover pointer-events-none', 'draggable' => 'false']);
                if ($beforeLabel) {
                    $html .= "<div class='absolute top-4 left-4 bg-black/50 text-white px-3 py-1 rounded-theme text-xs font-bold backdrop-blur-sm'>{$beforeLabel}</div>";
                }
                $html .= "</div>";

                // Render slider control
                $html .= "<div class='slider-line absolute top-0 bottom-0 w-1 bg-white shadow-[0_0_5px_rgba(0,0,0,0.5)] -translate-x-1/2 pointer-events-none z-10' :style=\"`left: \${sliderPos}%;`\">";
                $html .= "<div class='absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-white rounded-full shadow-theme flex items-center justify-center text-gray-500'>";
                $html .= "<svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 9l-3 3 3 3m8-6l3 3-3 3'></path></svg>";
                $html .= "</div></div>";

                // Render input range
                $html .= "<input type='range' min='0' max='100' x-model=\"sliderPos\" class='absolute inset-0 w-full h-full opacity-0 cursor-ew-resize z-20 m-0 p-0'>";

                $html .= "</div></div>";
                return $html;

            case 'progress_bar':
                $items = $data['items'] ?? [];
                $validItems = array_filter($items, fn($item) => trim($item['label'] ?? '') !== '' || (int)($item['percentage'] ?? 0) > 0);
                if (empty($validItems)) return '';

                $html = "<div class='{$commonClass} my-10 space-y-4 max-w-3xl mx-auto'>";
                foreach ($validItems as $item) {
                    $label = h($item['label'] ?? '');
                    $pct = (int)($item['percentage'] ?? 0);
                    $color = $item['color'] ?? 'primary';
                    if ($pct < 0) $pct = 0;
                    if ($pct > 100) $pct = 100;

                    // Determine color class
                    $bgClass = match ($color) {
                        'success' => 'bg-green-500',
                        'danger'  => 'bg-red-500',
                        'warning' => 'bg-yellow-500',
                        'dark'    => 'bg-gray-800',
                        default   => 'bg-theme-primary'
                    };

                    $html .= "<div class='flex flex-col gap-1.5'>";
                    $html .= "<div class='flex justify-between items-center text-sm font-bold text-gray-800'>";
                    $html .= "<span>{$label}</span><span>{$pct}%</span>";
                    $html .= "</div>";
                    $html .= "<div class='w-full bg-gray-200 rounded-theme h-3 overflow-hidden shadow-inner'>";
                    // Set initial width to 0 for scroll animation
                    $html .= "<div class='{$bgClass} h-3 rounded-theme transition-all duration-1000 ease-out' style='width: 0%;' data-width='{$pct}%'></div>";
                    $html .= "</div></div>";
                }
                $html .= "</div>";

                return $html;

            case 'video':
                $rawUrl = $data['url'] ?? '';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawUrl)) {
                    return '';
                }
                $url = resolve_url($rawUrl);
                if (!$url) return '';
                $attrs = 'controls playsinline';
                if (!empty($data['autoplay'])) $attrs .= ' autoplay';
                if (!empty($data['loop'])) $attrs .= ' loop';
                if (!empty($data['muted'])) $attrs .= ' muted';
                $safeUrl = h($url);
                return "<div class='{$commonClass} grinds-auto-stop my-8 rounded-theme overflow-hidden shadow-theme bg-black'><video src='{$safeUrl}' {$attrs} class='w-full max-h-[600px] object-contain'></video></div>";

            case 'toc':
                global $pageData;
                $contentData = $pageData['post']['content_decoded'] ?? json_decode($pageData['post']['content'] ?? '{}', true);
                $headers = function_exists('get_post_toc') ? get_post_toc($contentData) : [];
                if (empty($headers)) return '';
                $title = h($data['title'] ?? 'Contents');
                $tocId = 'toc-' . bin2hex(random_bytes(4));
                $html = "<details id='{$tocId}' open class='{$commonClass} my-8 p-6 border border-gray-200 rounded-theme bg-gray-50 shadow-theme'>";
                $html .= "<summary class='font-bold cursor-pointer text-gray-800 mb-4 text-lg outline-none'>{$title}</summary>";
                $html .= "<nav aria-label='Table of Contents'><ul class='space-y-2 list-none m-0 p-0'>";
                foreach ($headers as $h) {
                    $indentClass = 'ml-0 font-bold';
                    if ($h['level'] === 3) $indentClass = 'ml-4 font-normal';
                    elseif ($h['level'] >= 4) $indentClass = 'ml-8 font-normal text-sm text-gray-600';
                    $html .= "<li class='{$indentClass}'><a href='#{$h['id']}' class='hover:underline hover:text-theme-primary text-gray-700 block py-0.5 transition-colors'>" . h($h['text']) . "</a></li>";
                }
                $html .= "</ul></nav></details>";
                return $html;

            case 'author':
                $name = h($data['name'] ?? '');
                $role = h($data['role'] ?? '');
                $bio = nl2br($pathFixer($data['bio'] ?? ''));
                $img = resolve_url($data['image'] ?? '');
                $link = resolve_url($data['link'] ?? '');
                if ($name === '' && $bio === '') return '';

                $type = (isset($data['type']) && $data['type'] === 'Organization') ? 'Organization' : 'Person';

                $html = "<aside aria-label=\"Author Profile\" class='{$commonClass} my-10 p-6 bg-white border border-gray-200 rounded-theme shadow-theme flex flex-col sm:flex-row items-center sm:items-start gap-6'>";
                if ($img) {
                    $html .= get_image_html($img, ['class' => 'w-20 h-20 rounded-theme object-cover shadow-theme shrink-0', 'alt' => $name]);
                } else {
                    $iconId = $type === 'Organization' ? 'outline-building-office' : 'outline-user-circle';
                    $iconHtml = "<svg class='w-10 h-10 text-gray-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#{$iconId}'></use></svg>";
                    $html .= "<div class='w-20 h-20 rounded-theme bg-gray-100 flex items-center justify-center shrink-0 border border-gray-200'>{$iconHtml}</div>";
                }
                $html .= "<div class='text-center sm:text-left flex-1'>";

                if ($type === 'Person') {
                    $html .= "<div class='text-xs font-bold text-gray-400 uppercase tracking-wider mb-1'>" . ($role ?: 'Author') . "</div>";
                } elseif ($role) {
                    $html .= "<div class='text-xs font-bold text-gray-400 uppercase tracking-wider mb-1'>{$role}</div>";
                }

                $html .= "<h3 class='text-xl font-bold text-gray-900 mb-2'>{$name}</h3>";
                $html .= "<p class='text-sm text-gray-600 leading-relaxed mb-3'>{$bio}</p>";
                if ($link) {
                    $html .= "<a href='" . h($link) . "' target='_blank' rel='noopener noreferrer external' class='inline-flex items-center text-xs font-bold text-theme-primary hover:underline'><svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-link'></use></svg>Profile Link</a>";
                }
                $html .= "</div></aside>";
                return $html;

            case 'social_share':
                $align = $data['align'] ?? 'center';
                $text = h($data['text'] ?? '');

                $alignClass = match ($align) {
                    'left' => 'justify-start text-left',
                    'right' => 'justify-end text-right',
                    default => 'justify-center text-center',
                };

                $buttons = function_exists('grinds_get_share_buttons') ? grinds_get_share_buttons() : [];
                if (empty($buttons)) return '';

                $html = "<div class='{$commonClass} my-10'>";

                if ($text !== '') {
                    $textClass = match ($align) {
                        'left' => 'text-left',
                        'right' => 'text-right',
                        default => 'text-center',
                    };
                    $html .= "<div class='font-bold text-gray-700 mb-4 {$textClass}'>{$text}</div>";
                }

                $html .= "<div class='flex flex-wrap gap-3 {$alignClass}'>";
                foreach ($buttons as $button) {
                    $displayName = $button['display_name'] ?? $button['name'];
                    // Apply button styles
                    $html .= "<a href='" . $button['share_url'] . "' target='_blank' rel='noopener noreferrer external' class='flex items-center gap-2 hover:opacity-90 px-5 py-2.5 rounded-theme font-bold text-white text-sm transition-all shadow-theme hover:shadow-theme hover:-translate-y-0.5' style='background-color:" . $button['color'] . ";'>";
                    $html .= "<svg class='w-4 h-4' fill='currentColor'><use href='" . $button['sprite_url'] . "#" . $button['icon'] . "'></use></svg>";
                    // Display text on desktop
                    $html .= "<span class='hidden sm:inline'>{$displayName}</span></a>";
                }
                $html .= "</div></div>";
                return $html;
            case 'math':
                $code = $data['code'] ?? '';
                if (trim((string)$code) === '') return '';
                $display = $data['display'] ?? 'block';
                $isBlock = ($display === 'block');

                $escapedCode = h($code);

                $html = "";
                if ($isBlock) {
                    $html .= "<div class='{$commonClass} my-8 overflow-x-auto py-4 text-center bg-gray-50 rounded-theme'>\$\$ " . $escapedCode . " \$\$</div>";
                } else {
                    $html .= "<span class='{$commonClass} px-1 text-gray-800'>\$ " . $escapedCode . " \$</span>";
                }

                // Removed inline script. KaTeX loader logic moved to outputFooterScripts().
                return $html;
            case 'icon_list':
                $items = $data['items'] ?? [];
                $validItems = array_filter($items, fn($item) => trim($item ?? '') !== '');
                if (empty($validItems)) return '';

                $iconType = $data['icon'] ?? 'check';
                $colorType = $data['color'] ?? 'green';

                static $iconConfig = null;
                if ($iconConfig === null) {
                    $config = require ROOT_PATH . '/admin/config/editor_blocks.php';
                    $iconConfig = $config['library']['design']['items']['icon_list'] ?? [];
                }

                $svgId = $iconConfig['icons'][$iconType]['svg'] ?? 'outline-check';

                // Map color name to class
                $colorClass = match ($colorType) {
                    'green' => 'text-green-500',
                    'blue' => 'text-blue-500',
                    'red' => 'text-red-500',
                    'yellow' => 'text-yellow-500',
                    'gray' => 'text-gray-400',
                    'primary' => 'text-theme-primary',
                    default => 'text-green-500'
                };

                $iconHtml = "<svg class='mt-0.5 mr-3 w-5 h-5 shrink-0 {$colorClass}' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#{$svgId}'></use></svg>";

                $html = "<ul class='{$commonClass} my-8 space-y-3 list-none p-0'>";
                foreach ($validItems as $item) {
                    // Sanitize item text
                    $html .= "<li class='flex items-start text-gray-700 text-base leading-relaxed'>{$iconHtml}<span>" . $pathFixer($item) . "</span></li>";
                }
                $html .= "</ul>";
                return $html;
            case 'tabs':
                $items = $data['items'] ?? [];
                $validItems = array_values(array_filter($items, fn($item) => trim($item['title'] ?? '') !== '' || trim($item['content'] ?? '') !== ''));
                if (empty($validItems)) return '';

                $html = "<div class='{$commonClass} my-10' x-data=\"{ activeTab: 0 }\">";

                $html .= "<div class='flex overflow-x-auto border-b border-gray-200 no-scrollbar' role='tablist'>";
                foreach ($validItems as $i => $item) {
                    $title = h($item['title'] ?: theme_t('Tab'));
                    $html .= "<button type='button' @click=\"activeTab = {$i}\"
                        :class=\"activeTab === {$i} ? 'border-theme-primary text-theme-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'\"
                        class='whitespace-nowrap py-3 px-6 border-b-2 font-bold text-sm outline-none transition-colors'
                        role='tab'
                        :aria-selected=\"activeTab === {$i}\">{$title}</button>";
                }
                $html .= "</div>";

                $html .= "<div class='pt-6'>";
                foreach ($validItems as $i => $item) {
                    $content = nl2br($pathFixer($item['content'] ?? ''));
                    $html .= "<div x-show=\"activeTab === {$i}\" x-transition:enter='transition ease-out duration-300' x-transition:enter-start='opacity-0 translate-y-2' x-transition:enter-end='opacity-100 translate-y-0' role='tabpanel' class='text-gray-700 leading-relaxed' style='" . ($i === 0 ? "" : "display: none;") . "'>{$content}</div>";
                }
                $html .= "</div>";

                $html .= "</div>";
                return $html;
            case 'post_grid':
                $limit = (int)($data['limit'] ?? 6);
                $cols = (int)($data['columns'] ?? 3);
                $catId = (int)($data['category'] ?? 0);
                $style = $data['style'] ?? 'card';

                $pdo = App::db();
                if (!$pdo) return '';

                $repo = new PostRepository($pdo);
                $filters = ['status' => 'published', 'type' => 'post'];
                if ($catId > 0) {
                    $filters['category_id'] = $catId;
                }

                $posts = $repo->fetch($filters, $limit, 0, 'p.published_at DESC');
                if (empty($posts)) return '';

                $html = "<div class='{$commonClass} my-10'>";

                if ($style === 'card') {
                    $gridClass = match ($cols) {
                        2 => 'grid-cols-1 sm:grid-cols-2',
                        4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                        default => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
                    };
                    $html .= "<div class='grid {$gridClass} gap-6'>";

                    foreach ($posts as $p) {
                        $url = resolve_url($p['slug']);
                        $title = h($p['title']);

                        $dateStr = $p['published_at'] ?? $p['created_at'];
                        $date = $dateStr ? date(get_option('date_format', 'Y-m-d'), strtotime($dateStr)) : '';

                        $thumb = !empty($p['thumbnail']) ? resolve_url(grinds_url_to_view($p['thumbnail'])) : '';
                        $catName = h($p['category_name'] ?? '');

                        $html .= "<article class='relative flex flex-col bg-white shadow-theme hover:shadow-theme border border-gray-200 rounded-theme overflow-hidden transition-shadow group'>";

                        if ($thumb) {
                            $html .= "<div class='relative aspect-[16/9] overflow-hidden bg-gray-100 shrink-0'>";
                            $html .= get_image_html($thumb, ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500', 'alt' => $title]);
                            if ($catName) {
                                $html .= "<span class='absolute top-3 left-3 bg-theme-primary text-theme-on-primary text-[10px] font-bold px-2 py-1 rounded-theme shadow-theme z-10'>{$catName}</span>";
                            }
                            $html .= "</div>";
                        }

                        $html .= "<div class='p-5 flex flex-col flex-grow'>";
                        if (!$thumb && $catName) {
                            $html .= "<div class='relative z-10 mb-2 text-[10px] font-bold text-theme-primary uppercase tracking-wider'>{$catName}</div>";
                        }

                        $html .= "<h3 class='font-bold text-gray-900 text-lg mb-3 line-clamp-2 leading-snug'><a href='{$url}' class='hover:text-theme-primary transition-colors no-underline after:absolute after:inset-0'>{$title}</a></h3>";

                        if ($cols <= 2) {
                            $plainText = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($p['content']) : strip_tags($p['content']);
                            $excerpt = h(mb_strimwidth($plainText, 0, 80, '...', 'UTF-8'));
                            if ($excerpt) {
                                $html .= "<p class='text-gray-600 text-sm line-clamp-2 mb-4 leading-relaxed'>{$excerpt}</p>";
                            }
                        }

                        $html .= "<div class='mt-auto pt-4 border-gray-100 border-t flex items-center text-xs text-gray-400 font-mono'>{$date}</div>";
                        $html .= "</div></article>";
                    }
                    $html .= "</div>";
                } else {
                    $html .= "<ul class='divide-y divide-gray-100 border-y border-gray-100'>";
                    foreach ($posts as $p) {
                        $url = resolve_url($p['slug']);
                        $title = h($p['title']);
                        $dateStr = $p['published_at'] ?? $p['created_at'];
                        $date = $dateStr ? date(get_option('date_format', 'Y-m-d'), strtotime($dateStr)) : '';

                        $html .= "<li class='py-4 flex sm:flex-row flex-col sm:items-center gap-2 sm:gap-6 group relative'>";
                        $html .= "<span class='text-gray-400 font-mono text-sm w-28 shrink-0'>{$date}</span>";
                        $html .= "<a href='{$url}' class='font-bold text-gray-800 text-lg group-hover:text-theme-primary transition-colors leading-snug after:absolute after:inset-0'>{$title}</a>";
                        $html .= "</li>";
                    }
                    $html .= "</ul>";
                }

                $html .= "</div>";
                return $html;
            case 'header':
                $level = strtolower($data['level'] ?? 'h2');
                if (!preg_match('/^h[2-6]$/', $level)) $level = 'h2';
                $text = $pathFixer($data['text'] ?? '');
                if ($text === '') return '';

                $idx = $block['_index'] ?? rand(1000, 9999);
                $rawText = strip_tags($data['text'] ?? '');
                $safeText = mb_substr(preg_replace('/[^a-zA-Z0-9\p{L}\p{N}]+/u', '-', mb_strtolower($rawText, 'UTF-8')), 0, 30);
                $safeText = trim($safeText, '-');
                $semanticId = 'sec-' . $idx . ($safeText ? '-' . $safeText : '');

                return "<div class='{$commonClass}'><{$level} id='{$semanticId}' class='mt-8 mb-4 font-bold text-gray-800'>{$text}</{$level}></div>";

            case 'paragraph':
                $text = nl2br($pathFixer($data['text'] ?? ''));
                if ($text === '') return '';
                return "<div class='{$commonClass}'><p class='mb-6 text-gray-700 leading-relaxed'>{$text}</p></div>";

            case 'image':
                $url = resolve_url($data['url'] ?? '');
                if (!$url) return '';
                $caption = h($data['caption'] ?? '');
                $width = (int)($data['width'] ?? 100);
                $figStyle = ($width > 0 && $width < 100) ? " style='max-width: {$width}%; margin-left: auto; margin-right: auto;'" : "";
                $html = "<figure class='{$commonClass} my-8'{$figStyle}>";

                $loadingAttr = $this->firstImageRendered ? 'lazy' : 'eager';
                $attrs = [
                    'loading' => $loadingAttr,
                    'class' => 'w-full rounded-theme shadow-theme border border-gray-100' . ($width === 100 ? ' mx-auto' : '')
                ];
                if (!$this->firstImageRendered) {
                    $attrs['fetchpriority'] = 'high';
                    $this->firstImageRendered = true;
                }

                if (isset($data['alt'])) {
                    $attrs['alt'] = $data['alt'];
                } elseif (!empty($data['caption'])) {
                    $attrs['alt'] = $data['caption'];
                }

                global $grinds_image_meta_cache;
                $baseUrl = rtrim(BASE_URL, '/') . '/';
                $relPath = preg_replace('/\?.*$/', '', ltrim(str_replace($baseUrl, '', grinds_url_to_view($url)), '/'));
                $meta = $grinds_image_meta_cache[$relPath] ?? [];
                $isAi = !empty($meta['is_ai']);
                $aiSource = h($meta['source'] ?? 'AI');
                $capId = '';

                if ($caption || $isAi) {
                    $capId = 'cap-' . uniqid();
                    $attrs['aria-describedby'] = $capId;
                    $html .= get_image_html($url, $attrs);
                    $html .= "<figcaption id='{$capId}' class='mt-2 text-gray-500 text-xs text-center flex items-center justify-center gap-1'>";
                    if ($isAi) {
                        $html .= "<span class='inline-flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded-theme text-[10px] font-bold text-gray-500 border border-gray-200' title='Generated by {$aiSource}'><svg class='w-3 h-3 text-blue-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-sparkles'></use></svg>AI Generated</span>";
                    }
                    if ($caption) {
                        $html .= "<span class='ml-1'>{$caption}</span>";
                    }
                    $html .= "</figcaption>";
                } else {
                    $html .= get_image_html($url, $attrs);
                }
                $html .= "</figure>";
                return $html;

            case 'list':
                $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
                $listClass = $style === 'ol' ? 'list-decimal' : 'list-disc';
                $items = $data['items'] ?? [];
                $validItems = array_filter($items, fn($item) => trim((string)$item) !== '');
                if (!empty($validItems)) {
                    $html = "<{$style} class='{$commonClass} {$listClass} list-outside ml-6 mb-6 text-gray-700 space-y-1'>";
                    foreach ($validItems as $item) {
                        $html .= "<li>" . $pathFixer($item) . "</li>";
                    }
                    $html .= "</{$style}>";
                    return $html;
                }
                return '';

            case 'table':
                $content = $data['content'] ?? [];
                $withHeadings = !empty($data['withHeadings']);
                if (!empty($content) && is_array($content)) {
                    $html = "<div class='{$commonClass} shadow-theme my-8 border border-gray-200 rounded-theme overflow-x-auto'>";
                    $html .= "<table class='divide-y divide-gray-200 min-w-full text-sm'>";

                    if ($withHeadings && isset($content[0]) && is_array($content[0])) {
                        $html .= "<thead class='bg-gray-50'><tr>";
                        foreach ($content[0] as $cell) {
                            $cellText = nl2br($pathFixer($cell ?? ''));
                            $html .= "<th scope='col' class='px-6 py-3 border-gray-200 border-r last:border-r-0 font-bold text-gray-500 text-xs text-left uppercase tracking-wider'>{$cellText}</th>";
                        }
                        $html .= "</tr></thead>";
                        unset($content[0]);
                    }

                    $html .= "<tbody class='bg-white divide-y divide-gray-200'>";
                    foreach ($content as $row) {
                        if (!is_array($row)) continue;
                        $html .= "<tr class='hover:bg-gray-50/50 transition-colors'>";
                        foreach ($row as $cell) {
                            $cellText = nl2br($pathFixer($cell ?? ''));
                            $html .= "<td class='px-6 py-4 border-gray-200 border-r last:border-r-0 text-gray-700 whitespace-normal'>{$cellText}</td>";
                        }
                        $html .= "</tr>";
                    }
                    $html .= "</tbody></table></div>";
                    return $html;
                }
                return '';

            case 'quote':
                $text = nl2br($pathFixer($data['text'] ?? ''));
                if ($text === '') return '';
                $cite = h($data['cite'] ?? '');
                $rawCiteUrl = $data['citeUrl'] ?? '';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawCiteUrl)) {
                    $rawCiteUrl = '';
                }
                $citeAttr = $rawCiteUrl ? " cite=\"" . h($rawCiteUrl) . "\"" : "";
                $html = "<figure class='{$commonClass} bg-gray-50 my-6 p-4 pl-4 border-gray-300 border-l-4 rounded-r-theme'>";
                $html .= "<blockquote{$citeAttr} class='text-gray-600 italic'><p>{$text}</p></blockquote>";
                if ($cite || $rawCiteUrl) {
                    $html .= "<figcaption class='mt-2 text-gray-500 text-sm'>— <cite>";
                    if ($rawCiteUrl) {
                        $displayCite = $cite ?: $rawCiteUrl;
                        $linkAttrs = grinds_get_link_attributes($rawCiteUrl, ['class' => 'hover:underline text-theme-primary']);
                        $html .= "<a{$linkAttrs}>" . h($displayCite) . "</a>";
                    } else {
                        $html .= $cite;
                    }
                    $html .= "</cite></figcaption>";
                }
                $html .= "</figure>";
                return $html;

            case 'divider':
                return "<hr class='{$commonClass} my-10 border-gray-200'>";

            case 'html':
                $code = $data['code'] ?? '';
                if ($code === '') return '';
                if (!$allowUnsafe) {
                    $code = $pathFixer($code);
                }
                return "<div class='cms-block-html my-8'>{$code}</div>";

            case 'code':
                $lang = h($data['language'] ?? 'plaintext');
                $code = h($data['code'] ?? '');
                if ($code === '') return '';
                return "<div class='{$commonClass}'>" .
                    "<pre class='bg-gray-800 my-6 p-4 rounded-theme overflow-x-auto font-mono text-white text-sm'>" .
                    "<code class='language-{$lang}'>{$code}</code>" .
                    "</pre></div>";

            case 'spacer':
                $height = (int)($data['height'] ?? 50);
                return "<div class='{$commonClass}' style='height:{$height}px' aria-hidden='true'></div>";

            case 'accordion':
                $items = $data['items'] ?? [];
                $validItems = array_filter($items, fn($item) => trim($item['title'] ?? '') !== '' || trim($item['content'] ?? '') !== '');
                if (empty($validItems)) return '';

                $html = "<div class='{$commonClass} my-8 border-gray-200 border-t'>";
                foreach ($validItems as $item) {
                    $q = h($item['title'] ?? theme_t('Untitled'));
                    $a = nl2br($pathFixer($item['content'] ?? ''));
                    $html .= "<details class='group border-gray-200 border-b'>";
                    $html .= "<summary class='flex justify-between items-center py-4 font-bold cursor-pointer list-none'>{$q}";
                    // Show plus icon when closed
                    $html .= "<span class='text-gray-400 group-open:hidden'>+</span>";
                    // Show minus icon when open
                    $html .= "<span class='text-gray-400 hidden group-open:inline'>&minus;</span>";
                    $html .= "</summary>";
                    $html .= "<div class='pb-4 text-gray-600'>{$a}</div>";
                    $html .= "</details>";
                }
                $html .= "</div>";
                return $html;

            case 'gallery':
                $images = $data['images'] ?? [];
                $validImages = array_filter($images, fn($img) => !empty(resolve_url($img['url'] ?? '')));
                if (empty($validImages)) return '';

                $cols = (int)($data['columns'] ?? 3);
                $html = "<div class='{$commonClass} grid grid-cols-2 md:grid-cols-{$cols} gap-4 my-8'>";
                foreach ($validImages as $img) {
                    $src = resolve_url($img['url']);
                    $cap = h($img['caption'] ?? '');
                    $html .= "<div>";

                    $loadingAttr = $this->firstImageRendered ? 'lazy' : 'eager';
                    $imgAttrs = ['class' => 'w-full h-full object-cover rounded-theme shadow-theme', 'loading' => $loadingAttr, 'alt' => $img['alt'] ?? $img['caption'] ?? ''];
                    if (!$this->firstImageRendered) {
                        $imgAttrs['fetchpriority'] = 'high';
                        $this->firstImageRendered = true;
                    }
                    $html .= get_image_html($src, $imgAttrs);
                    if ($cap) $html .= "<div class='mt-1 text-gray-500 text-xs text-center'>{$cap}</div>";
                    $html .= "</div>";
                }
                $html .= "</div>";
                return $html;

            case 'section':
                $text = nl2br($pathFixer($data['text'] ?? ''));
                if ($text === '') return '';
                $bgColor = $data['bgColor'] ?? 'gray';

                static $sectionColors = null;
                if ($sectionColors === null) {
                    $config = require ROOT_PATH . '/admin/config/editor_blocks.php';
                    $sectionColors = $config['library']['layout']['items']['section']['colors'] ?? [];
                }

                $colorDef = $sectionColors[$bgColor] ?? $sectionColors['gray'] ?? [];
                $bgClass = $colorDef['class'] ?? '';
                $bgStyle = $colorDef['style'] ?? '';
                $styleAttr = $bgStyle ? " style=\"{$bgStyle}\"" : "";
                if ((!empty($bgStyle) || str_contains($bgClass, 'border-')) && !preg_match('/\bborder\b/', $bgClass)) $bgClass .= ' border';

                $sectionName = trim($data['name'] ?? '');
                $attrStr = '';
                if ($sectionName !== '') {
                    $safeName = htmlspecialchars($sectionName, ENT_QUOTES, 'UTF-8');
                    $safeId = trim(preg_replace('/[^a-zA-Z0-9\-_]+/', '-', strtolower($sectionName)), '-');
                    $attrStr .= " aria-label=\"{$safeName}\"";
                    if ($safeId !== '') {
                        $attrStr .= " id=\"sec-{$safeId}\"";
                    }
                }

                return "<section{$attrStr} class='{$commonClass} my-10 p-8 rounded-theme {$bgClass} leading-relaxed shadow-theme'{$styleAttr}>{$text}</section>";

            case 'columns':
                $left = nl2br($pathFixer($data['leftText'] ?? ''));
                $right = nl2br($pathFixer($data['rightText'] ?? ''));
                if ($left === '' && $right === '') return '';
                $ratio = $data['ratio'] ?? '1-1';
                $gridClass = match ($ratio) {
                    '1-2' => 'md:grid-cols-[1fr_2fr]',
                    '2-1' => 'md:grid-cols-[2fr_1fr]',
                    default => 'md:grid-cols-2',
                };
                $html = "<div class='{$commonClass} grid grid-cols-1 {$gridClass} gap-8 my-10 items-start'>";
                $html .= "<div class='leading-relaxed'>{$left}</div>";
                $html .= "<div class='leading-relaxed'>{$right}</div>";
                $html .= "</div>";
                return $html;

            case 'map':
                $code = $data['code'] ?? '';
                if (preg_match('/<iframe\s+[^>]*src=["\']([^"\']+)["\']/i', $code, $matches)) {
                    $src = $matches[1];
                    $parsed = parse_url($src);
                    $host = $parsed['host'] ?? '';
                    $isGoogle = ($host === 'www.google.com' || $host === 'google.com') && str_starts_with($parsed['path'] ?? '', '/maps');
                    $isOSM = ($host === 'www.openstreetmap.org' || $host === 'openstreetmap.org');
                    if ($isGoogle || $isOSM) {
                        $height = '450';
                        if (preg_match('/height=["\']([0-9%a-zA-Z]+)["\']/', $code, $hMatches)) {
                            $height = $hMatches[1];
                        }
                        $mapTitle = $isGoogle ? 'Google Maps' : ($isOSM ? 'OpenStreetMap' : 'Map');
                        $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
                        $iframe = "<iframe src=\"{$safeSrc}\" title=\"{$mapTitle}\" width=\"100%\" height=\"{$height}\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>";
                        return "<div class='{$commonClass} my-10 overflow-hidden rounded-theme shadow-theme border border-gray-200'>{$iframe}</div>";
                    }
                }
                return '';

            case 'timeline':
                $items = $data['items'] ?? [];
                $validItems = array_filter($items, fn($item) => trim($item['date'] ?? '') !== '' || trim($item['title'] ?? '') !== '' || trim($item['content'] ?? '') !== '');
                if (empty($validItems)) return '';

                $html = "<ol class='{$commonClass} my-12 space-y-8 border-l-2 border-gray-200 ml-3 pl-8 relative list-none p-0'>";
                foreach ($validItems as $item) {
                    $date = h($item['date'] ?? '');
                    $title = h($item['title'] ?? '');
                    $content = nl2br($pathFixer($item['content'] ?? ''));
                    $html .= "<li class='relative'>";
                    $html .= "<span class='top-1 -left-[41px] absolute bg-white border-4 border-theme-primary rounded-full w-5 h-5' aria-hidden='true'></span>";
                    if ($date) $html .= "<time class='block mb-1 font-bold text-theme-primary text-sm'>{$date}</time>";
                    if ($title) $html .= "<h4 class='mb-2 font-bold text-gray-900 text-lg'>{$title}</h4>";
                    $html .= "<div class='text-gray-600 text-sm leading-relaxed'>{$content}</div>";
                    $html .= "</li>";
                }
                $html .= "</ol>";
                return $html;

            case 'internal_card':
                global $grinds_post_cache;
                $pdo = App::db();
                $id = (int)($data['id'] ?? 0);
                if (!$id) return '';
                try {
                    if (array_key_exists($id, $grinds_post_cache)) {
                        $post = $grinds_post_cache[$id];
                    } elseif ($pdo) {
                        $repo = new PostRepository($pdo);
                        $status = (function_exists('current_user_can') && current_user_can('manage_posts')) ? 'any' : 'published';
                        $posts = $repo->fetch([
                            'ids' => [$id],
                            'status' => $status
                        ], 1);
                        $post = !empty($posts) ? $posts[0] : null;

                        // Cache the result (even if null) to prevent redundant DB calls
                        $grinds_post_cache[$id] = $post;
                    }
                    if (empty($post)) return '';
                    $url = resolve_url($post['slug']);
                    $title = h($post['title']);
                    $thumb = $post['thumbnail'] ? resolve_url($post['thumbnail']) : '';
                    $date = date('Y.m.d', strtotime($post['created_at']));
                    $desc = h($post['description']);
                    if (empty($desc)) {
                        $plainText = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($post['content']) : strip_tags($post['content']);
                        $desc = h(mb_strimwidth($plainText, 0, 100, '...', 'UTF-8'));
                    }
                    $safeUrl = h($url);
                    $html = "<div class='{$commonClass} relative flex sm:flex-row flex-col bg-white shadow-theme hover:shadow-theme mx-auto border border-gray-200 hover:border-theme-primary/30 rounded-theme max-w-3xl overflow-hidden transition-all group my-10'>";
                    if ($thumb) {
                        $html .= "<div class='relative bg-gray-100 sm:w-48 h-40 sm:h-auto overflow-hidden shrink-0'>";
                        $loadingAttr = $this->firstImageRendered ? 'lazy' : 'eager';
                        $imgAttrs = [
                            'class' => 'absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 transform',
                            'loading' => $loadingAttr,
                            'alt' => $post['title']
                        ];
                        if (!$this->firstImageRendered) {
                            $imgAttrs['fetchpriority'] = 'high';
                            $this->firstImageRendered = true;
                        }
                        $html .= get_image_html($thumb, $imgAttrs);
                        $html .= "</div>";
                    } else {
                        $html .= "<div class='flex justify-center items-center bg-gray-100 sm:w-32 h-32 sm:h-auto text-gray-300 shrink-0'>";
                        $html .= "<svg class='w-10 h-10' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-photo'></use></svg>";
                        $html .= "</div>";
                    }
                    $html .= "<div class='flex flex-col flex-1 justify-center p-5'>";
                    $html .= "<div class='mb-1 text-gray-400 text-xs'>{$date}</div>";
                    $html .= "<h4 class='mb-2 font-bold text-gray-900 group-hover:text-theme-primary text-lg line-clamp-2 leading-snug'><a href='{$safeUrl}' class='no-underline after:absolute after:inset-0'>{$title}</a></h4>";
                    $html .= "<p class='text-gray-600 text-sm line-clamp-2 leading-relaxed'>{$desc}</p>";
                    $html .= "</div>";
                    $html .= "</div>";
                    return $html;
                } catch (Exception $e) {
                    return '';
                }

            case 'embed':
                $rawUrl = $data['url'] ?? '';
                if ($rawUrl === '') return '';
                $align = $data['align'] ?? 'center';
                $alignClass = ($align === 'center') ? 'mx-auto text-center' : (($align === 'right') ? 'ml-auto text-right' : '');
                $embedHtml = '';
                if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $rawUrl, $matches)) {
                    $embedId = h($matches[1] . '/' . $matches[2]);
                    $embedHtml = "<div class='relative w-full aspect-video rounded-theme overflow-hidden shadow-theme {$alignClass} max-w-[800px]'>
                      <iframe loading='lazy' src='https://www.canva.com/design/{$embedId}/view?embed' title='Canva Design' class='absolute inset-0 w-full h-full' allowfullscreen='allowfullscreen' allow='fullscreen' frameborder='0'></iframe>
                  </div>";
                } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $rawUrl, $matches)) {
                    $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($rawUrl);
                    $embedHtml = "<div class='relative w-full aspect-video rounded-theme overflow-hidden shadow-theme {$alignClass} max-w-[800px]'>
                      <iframe loading='lazy' src='" . h($embedUrl) . "' title='Figma Design' class='absolute inset-0 w-full h-full' allowfullscreen frameborder='0'></iframe>
                  </div>";
                } elseif (preg_match('/(?:youtube\.com\/(?:(?:v|e(?:mbed)?|shorts)\/|[^\s&?"]*+[?&]v=)|youtu\.be\/)([^"&?\\/\\s]{11})/i', $rawUrl, $matches)) {
                    $vid = h($matches[1]);
                    $embedHtml = "<div class='relative w-full aspect-video rounded-theme overflow-hidden shadow-theme bg-black {$alignClass} max-w-[800px] w-full'>
                      <iframe src='https://www.youtube-nocookie.com/embed/{$vid}?enablejsapi=1' title='YouTube Video Player' class='absolute inset-0 w-full h-full' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen loading='lazy'></iframe>
                  </div>";
                } elseif (preg_match('/(twitter\.com|x\.com)\/[a-zA-Z0-9_]+\/status\/([0-9]+)/', $rawUrl)) {
                    $safeTwitterUrl = h(str_replace('x.com', 'twitter.com', $rawUrl));
                    $embedHtml = "<div class='twitter-embed {$alignClass} max-w-[550px] w-full'>
                    <blockquote class='twitter-tweet'><a href='{$safeTwitterUrl}'></a></blockquote>
                    <script async src='https://platform.twitter.com/widgets.js' charset='utf-8'></script>
                 </div>";
                } elseif (preg_match('/instagram\.com\/p\/([a-zA-Z0-9_-]+)/', $rawUrl)) {
                    $safeInstaUrl = h($rawUrl);
                    $embedHtml = "<div class='instagram-embed {$alignClass} max-w-[540px] w-full'>
                      <blockquote class='instagram-media' data-instgrm-permalink='{$safeInstaUrl}' data-instgrm-version='14'></blockquote>
                      <script async src='//www.instagram.com/embed.js'></script>
                  </div>";
                } else {
                    $url = $rawUrl;
                    if (preg_match('/^\s*(javascript|vbscript|data):/i', $url)) {
                        $url = '#';
                    }
                    $linkAttrs = grinds_get_link_attributes($url, ['class' => 'text-theme-primary underline']);
                    $embedHtml = "<div class='{$alignClass}'><a{$linkAttrs}>" . h($url) . "</a></div>";
                }
                $autoStopClass = str_contains($embedHtml, '<iframe') ? ' grinds-auto-stop' : '';
                return "<div class='{$commonClass}{$autoStopClass} my-10 {$alignClass}'>{$embedHtml}</div>";

            case 'card':
                $rawUrl = $data['url'] ?? '#';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawUrl)) {
                    $rawUrl = '#';
                }
                $url = resolve_url($rawUrl);
                $title = h($data['title'] ?? '');
                $desc = h($data['description'] ?? '');
                $img = resolve_url($data['image'] ?? '');
                if (empty($title) && empty($desc) && empty($img)) return '';
                $align = $data['align'] ?? 'center';
                $marginClass = ($align === 'left') ? 'mr-auto' : (($align === 'right') ? 'ml-auto' : 'mx-auto');

                $html = "<div class='{$commonClass} relative flex flex-col sm:flex-row bg-white border border-gray-200 rounded-theme overflow-hidden shadow-theme hover:shadow-theme hover:border-theme-primary/30 transition-all max-w-3xl {$marginClass} group my-10'>";
                if ($img) {
                    $html .= "<div class='relative bg-gray-100 sm:w-56 h-48 sm:h-auto overflow-hidden shrink-0'>";
                    $loadingAttr = $this->firstImageRendered ? 'lazy' : 'eager';
                    $imgAttrs = [
                        'class' => 'absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 transform',
                        'loading' => $loadingAttr,
                        'alt' => $data['title'] ?? ''
                    ];
                    if (!$this->firstImageRendered) {
                        $imgAttrs['fetchpriority'] = 'high';
                        $this->firstImageRendered = true;
                    }
                    $html .= get_image_html($img, $imgAttrs);
                    $html .= "</div>";
                }
                $html .= "<div class='flex flex-col flex-1 justify-center p-6'>";
                $linkAttrs = grinds_get_link_attributes($url, ['class' => 'no-underline after:absolute after:inset-0']);
                $html .= "<h4 class='mb-2 font-bold text-gray-900 group-hover:text-theme-primary text-lg line-clamp-2 leading-snug'><a{$linkAttrs}>{$title}</a></h4>";
                if ($desc) {
                    $html .= "<p class='mb-3 text-gray-600 text-sm line-clamp-2 leading-relaxed'>{$desc}</p>";
                }
                $host = parse_url($url, PHP_URL_HOST);
                // Use current host for relative URLs
                if (!$host) $host = parse_url(resolve_url('/'), PHP_URL_HOST);
                $html .= "<div class='flex items-center mt-auto text-gray-400 text-xs'><svg class='mr-1 w-3 h-3' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-link'></use></svg>" . h($host) . "</div>";
                $html .= "</div></div>";
                return $html;

            case 'button':
                $text = h($data['text'] ?? 'Button');
                $rawUrl = $data['url'] ?? '';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawUrl)) {
                    $rawUrl = '#';
                }
                if (empty($rawUrl) || $rawUrl === '#') return '';
                $url = resolve_url($rawUrl);
                $color = $data['color'] ?? 'primary';
                $btnClass = "inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-theme shadow-theme transition-all duration-200 hover:shadow-theme hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2";
                $styleClass = "is-style-" . $color;
                if ($color === 'primary') $btnClass .= " bg-theme-primary text-theme-on-primary hover:opacity-90 focus:ring-theme-primary";
                elseif ($color === 'success') $btnClass .= " bg-green-600 text-white hover:bg-green-700 focus:ring-green-600";
                elseif ($color === 'danger') $btnClass .= " bg-red-600 text-white hover:bg-red-700 focus:ring-red-600";
                elseif ($color === 'dark') $btnClass .= " bg-gray-900 text-white hover:bg-black focus:ring-gray-900";

                $overrides = ['class' => "{$btnClass} {$styleClass}"];
                if (!empty($data['external'])) {
                    $overrides['target'] = '_blank';
                }
                $linkAttrs = grinds_get_link_attributes($url, $overrides);
                return "<div class='{$commonClass} my-10 text-center'><a{$linkAttrs}>{$text}</a></div>";

            case 'callout':
                $text = nl2br($pathFixer($data['text'] ?? ''));
                if ($text === '') return '';
                $style = $data['style'] ?? 'info';
                $styleClass = "is-style-" . $style;
                $boxClass = "p-5 rounded-r-theme border-l-4 my-8 shadow-theme flex items-start gap-3";
                $icon = '';
                if ($style === 'info') {
                    $boxClass .= " bg-blue-50 border-blue-400 text-blue-800";
                    $icon = '<svg class="mt-0.5 w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $spriteUrl . '#outline-information-circle"></use></svg>';
                } elseif ($style === 'warning') {
                    $boxClass .= " bg-yellow-50 border-yellow-400 text-yellow-800";
                    $icon = '<svg class="mt-0.5 w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $spriteUrl . '#outline-exclamation-triangle"></use></svg>';
                } elseif ($style === 'success') {
                    $boxClass .= " bg-green-50 border-green-400 text-green-800";
                    $icon = '<svg class="mt-0.5 w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $spriteUrl . '#outline-check-circle"></use></svg>';
                } elseif ($style === 'danger') {
                    $boxClass .= " bg-red-50 border-red-400 text-red-800";
                    $icon = '<svg class="mt-0.5 w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $spriteUrl . '#outline-exclamation-circle"></use></svg>';
                }
                $role = ($style === 'warning' || $style === 'danger') ? 'role="alert"' : 'role="note"';
                return "<aside {$role} aria-label=\"Callout\" class='{$commonClass} {$styleClass} {$boxClass}'>{$icon}<div class='flex-1'>{$text}</div></aside>";

            case 'download':
                $title = h($data['title'] ?? theme_t('Download File'));
                $rawUrl = $data['url'] ?? '';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawUrl)) {
                    $rawUrl = '#';
                }
                if (empty($rawUrl) || $rawUrl === '#') return '';
                $url = resolve_url($rawUrl);
                $size = h($data['fileSize'] ?? '');
                $iconSvg = '<svg class="w-8 h-8 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $spriteUrl . '#outline-arrow-down-tray"></use></svg>';
                $html = "<a href='" . h($url) . "' class='{$commonClass} flex items-center p-5 my-8 bg-gray-50 border border-gray-200 rounded-theme hover:bg-white hover:border-theme-primary hover:shadow-theme transition-all group no-underline' download>";
                $html .= "<div class='bg-white mr-5 p-3 border border-gray-200 group-hover:border-theme-primary/30 rounded-lg transition-colors'>{$iconSvg}</div>";
                $html .= "<div class='flex-1 min-w-0'>";
                $html .= "<div class='font-bold text-gray-900 group-hover:text-theme-primary text-lg truncate transition-colors'>{$title}</div>";
                if ($size) {
                    $html .= "<div class='mt-1 font-mono text-gray-500 text-xs'>Size: {$size}</div>";
                }
                $html .= "</div>";
                $html .= "<div class='text-gray-300 group-hover:text-theme-primary transition-colors'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-chevron-right'></use></svg></div>";
                $html .= "</a>";
                return $html;

            case 'step':
                $items = $data['items'] ?? [];
                $validItems = array_values(array_filter($items, fn($item) => trim($item['title'] ?? '') !== '' || trim($item['desc'] ?? '') !== ''));
                if (empty($validItems)) return '';

                $html = "<ol class='{$commonClass} my-12 space-y-0 list-none p-0'>";
                foreach ($validItems as $i => $item) {
                    $num = $i + 1;
                    $title = h($item['title'] ?? '');
                    $desc = nl2br($pathFixer($item['desc'] ?? ''));
                    $html .= "<li class='relative flex gap-6 pb-10 last:pb-0'>";
                    if ($i < count($validItems) - 1) {
                        $html .= "<div class='top-10 bottom-0 left-[19px] absolute bg-gray-200 w-0.5'></div>";
                    }
                    $html .= "<div class='z-10 flex justify-center items-center bg-theme-primary shadow-theme border-4 border-white rounded-theme w-10 h-10 font-bold text-theme-on-primary text-lg shrink-0'>{$num}</div>";
                    $html .= "<div class='pt-1'>";
                    $html .= "<h4 class='mb-3 font-bold text-gray-800 text-xl'>{$title}</h4>";
                    $html .= "<div class='text-gray-600 text-base leading-relaxed'>{$desc}</div>";
                    $html .= "</div></li>";
                }
                $html .= "</ol>";
                return $html;

            case 'price':
                $items = $data['items'] ?? (empty($data) ? [] : [$data]);
                $validItems = array_filter($items, fn($item) => trim($item['plan'] ?? '') !== '' || trim($item['price'] ?? '') !== '' || trim($item['features'] ?? '') !== '');
                if (empty($validItems)) return '';

                $count = count($validItems);
                $gridClass = match ($count) {
                    1 => 'max-w-sm mx-auto',
                    2 => 'grid-cols-1 md:grid-cols-2 max-w-3xl mx-auto',
                    default => 'grid-cols-1 md:grid-cols-3 max-w-6xl mx-auto',
                };
                $html = "<div class='{$commonClass} grid {$gridClass} gap-8 my-12 items-start'>";
                foreach ($validItems as $item) {
                    $plan = h($item['plan'] ?? '');
                    $price = h($item['price'] ?? '');
                    $features = explode("\n", h($item['features'] ?? ''));
                    $isRec = !empty($item['recommend']);
                    $wrapperClass = $isRec ? 'border-theme-primary ring-4 ring-theme-primary/10 shadow-theme scale-105 z-10 bg-white relative' : 'border-gray-200 bg-white shadow-theme hover:shadow-theme';
                    $html .= "<div class='border rounded-theme p-8 text-center transition-transform duration-300 {$wrapperClass}'>";
                    if ($isRec) {
                        $html .= "<span class='top-0 left-1/2 absolute bg-theme-primary shadow-theme px-4 py-1 rounded-theme font-bold text-theme-on-primary text-xs uppercase tracking-wider -translate-x-1/2 -translate-y-1/2 transform'>Recommended</span>";
                    }
                    $html .= "<h3 class='mb-2 font-bold text-gray-500 text-lg uppercase tracking-wide'>{$plan}</h3>";
                    $html .= "<div class='mb-8 font-extrabold text-gray-900 text-4xl'>{$price}</div>";
                    $html .= "<ul class='space-y-3 mb-8 pt-6 border-gray-100 border-t text-gray-600 text-sm text-left'>";
                    foreach ($features as $f) {
                        $f = trim($f);
                        if ($f) {
                            $html .= "<li class='flex items-start'><svg class='mt-0.5 mr-3 w-5 h-5 text-green-500 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-check'></use></svg><span>{$f}</span></li>";
                        }
                    }
                    $html .= "</ul></div>";
                }
                $html .= "</div>";
                return $html;

            case 'testimonial':
                $name = h($data['name'] ?? '');
                $role = h($data['role'] ?? '');
                $comment = nl2br($pathFixer($data['comment'] ?? ''));
                if ($comment === '') return '';
                $img = resolve_url($data['image'] ?? '');

                $siteName = function_exists('get_option') ? get_option('site_name', 'Our Service') : 'Our Service';
                $html = "<figure class='{$commonClass} my-10 bg-white p-8 rounded-theme shadow-theme border-gray-100 relative'>";
                $html .= "<div class='top-4 left-6 absolute font-serif text-gray-100 text-6xl leading-none' aria-hidden='true'>“</div>";
                $html .= "<div class='z-10 relative flex md:flex-row flex-col items-center md:items-start gap-6'>";
                if ($img) {
                    $html .= get_image_html($img, [
                        'class' => 'shadow-theme border-4 border-white rounded-theme w-20 h-20 object-cover shrink-0',
                        'alt' => $data['name'] ?? ''
                    ]);
                } else {
                    $html .= "<div class='flex justify-center items-center bg-gray-200 shadow-theme border-4 border-white rounded-theme w-20 h-20 shrink-0 text-gray-400' aria-hidden='true'><svg class='w-10 h-10' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-user-circle'></use></svg></div>";
                }
                $html .= "<blockquote class='md:text-left text-center flex-1'>";
                $html .= "<p class='mb-4 text-gray-700 text-lg italic leading-relaxed'>{$comment}</p>";
                $html .= "</blockquote></div>";
                $html .= "<figcaption class='mt-4 md:pl-[104px] md:text-left text-center'>"; // Calculate indentation
                $html .= "<div class='font-bold text-gray-900'>{$name}</div>";
                if ($role) {
                    $html .= "<div class='mt-1 font-bold text-theme-primary text-xs uppercase tracking-wide'>{$role}</div>";
                }
                $html .= "</figcaption></figure>";
                return $html;

            case 'audio':
                $rawUrl = $data['url'] ?? '';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawUrl)) {
                    return '';
                }
                $url = resolve_url($rawUrl);
                $title = h($data['title'] ?? '');
                if (!$url) return '';
                $uid = 'audio-' . bin2hex(random_bytes(4));
                $html = "<div id='{$uid}' class='{$commonClass} grinds-auto-stop bg-gray-100 my-6 p-4 rounded-theme'>";
                if ($title) $html .= "<div class='mb-2 font-bold text-sm'>{$title}</div>";
                $safeUrl = h($url);
                $html .= "<audio controls src='{$safeUrl}' class='w-full'></audio></div>";
                return $html;

            case 'pdf':
                $rawUrl = $data['url'] ?? '';
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $rawUrl)) {
                    return '';
                }
                $url = resolve_url($rawUrl);
                if (!$url) return '';
                $safeUrl = h($url);
                $html = "<div class='{$commonClass} bg-gray-100 my-8 border border-gray-200 rounded-theme h-[500px] overflow-hidden'><object data='{$safeUrl}' type='application/pdf' width='100%' height='100%'><p class='p-4 text-center'>" . theme_t('msg_pdf_error') . " <a href='{$safeUrl}' class='underline' aria-label='" . theme_t('download_pdf_aria') . "'>" . theme_t('btn_download') . "</a></p></object></div>";
                return $html;

            case 'search_box':
                $action = (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) ? 'search.html' : resolve_url('/');
                $ph = h($data['placeholder'] ?? theme_t('Search...'));
                $html = "<form action='{$action}' method='get' class='{$commonClass} flex my-8 grinds-search-form'>";
                $html .= "<input type='text' name='q' placeholder='{$ph}' class='flex-1 p-2 border rounded-l-theme'><button class='bg-theme-primary hover:opacity-90 px-4 rounded-r-theme text-theme-on-primary'>" . theme_t('Search') . "</button></form>";
                return $html;

            case 'conversation':
                $pos = ($data['position'] ?? 'left');
                $isRight = ($pos === 'right');
                $dir = $isRight ? 'flex-row-reverse' : 'flex-row';
                $bg = $isRight ? 'bg-green-100' : 'bg-gray-100';
                $name = h($data['name'] ?? '');
                $img = resolve_url($data['image'] ?? '');
                $text = nl2br($pathFixer($data['text'] ?? ''));
                if ($text === '') return '';
                $html = "<div class='{$commonClass} flex gap-4 my-6 {$dir}'>";
                $html .= "<div class='flex flex-col items-center gap-1 shrink-0'>";
                if ($img) {
                    $html .= get_image_html($img, [
                        'class' => 'w-12 h-12 rounded-theme object-cover border border-gray-200',
                        'alt' => $data['name'] ?? ''
                    ]);
                } else {
                    $html .= "<div class='flex justify-center items-center bg-gray-200 rounded-theme w-12 h-12 text-gray-400'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-user-circle'></use></svg></div>";
                }
                if ($name) $html .= "<span class='max-w-[5rem] text-gray-500 text-xs truncate'>{$name}</span>";
                $html .= "</div>";
                $html .= "<div class='relative p-4 rounded-theme {$bg} text-gray-800 leading-relaxed max-w-[80%] shadow-theme'>{$text}</div>";
                $html .= "</div>";
                return $html;

            case 'proscons':
                $pTitle = h($data['pros_title'] ?? theme_t('Good'));
                $cTitle = h($data['cons_title'] ?? theme_t('Bad'));
                $pItems = array_filter($data['pros_items'] ?? [], fn($item) => trim($item ?? '') !== '');
                $cItems = array_filter($data['cons_items'] ?? [], fn($item) => trim($item ?? '') !== '');
                if (empty($pItems) && empty($cItems)) return '';

                $html = "<div class='{$commonClass} gap-6 grid grid-cols-1 md:grid-cols-2 my-8'>";
                $html .= "<div class='bg-green-50 p-4 border border-green-200 rounded-theme' aria-label='Pros' role='region'>";
                $html .= "<div class='flex items-center gap-2 mb-3 font-bold text-green-800'><svg class='w-5 h-5 text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-check-circle'></use></svg> {$pTitle}</div>";
                $html .= "<ul class='space-y-2'>";
                foreach ($pItems as $item) $html .= "<li class='flex items-start gap-2 text-gray-700 text-sm'><svg class='mt-1 w-4 h-4 text-green-500 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-check'></use></svg> " . h($item) . "</li>";
                $html .= "</ul></div>";
                $html .= "<div class='bg-red-50 p-4 border border-red-200 rounded-theme' aria-label='Cons' role='region'>";
                $html .= "<div class='flex items-center gap-2 mb-3 font-bold text-red-800'><svg class='w-5 h-5 text-red-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-x-circle'></use></svg> {$cTitle}</div>";
                $html .= "<ul class='space-y-2'>";
                foreach ($cItems as $item) $html .= "<li class='flex items-start gap-2 text-gray-700 text-sm'><svg class='mt-1 w-4 h-4 text-red-500 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-x'></use></svg> " . h($item) . "</li>";
                $html .= "</ul></div></div>";
                return $html;

            case 'rating':
                $score = (float)($data['score'] ?? 5);
                $max = (int)($data['max'] ?? 5);
                $color = $data['color'] ?? 'gold';
                $starColor = match ($color) {
                    'red' => 'text-red-500',
                    'blue' => 'text-blue-500',
                    'green' => 'text-green-500',
                    default => 'text-yellow-400'
                };
                $html = "<div class='{$commonClass} flex items-center gap-4 bg-white shadow-theme my-6 p-4 border border-gray-200 rounded-theme w-fit'>";
                $html .= "<div class='flex items-center gap-0.5 {$starColor}'>";
                for ($i = 1; $i <= $max; $i++) {
                    $starIcon = ($i <= round($score)) ? "<svg class='w-6 h-6' fill='currentColor' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-star'></use></svg>" : "<svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-star'></use></svg>";
                    $html .= $starIcon;
                }
                $html .= "</div>";
                $html .= "<span class='font-bold text-gray-700 text-xl'>{$score}<span class='font-normal text-gray-400 text-sm'> / {$max}</span></span>";
                $html .= "</div>";
                return $html;

            case 'countdown':
                $deadline = h($data['deadline'] ?? '');
                $msg = h($data['message'] ?? theme_t('Finished'));
                return "<div class='{$commonClass} bg-gray-900 shadow-theme my-8 p-6 rounded-theme text-white text-center' data-deadline='{$deadline}' data-finish-msg='{$msg}'>" .
                    "<div class='opacity-70 mb-2 text-sm'>" . theme_t('lbl_time_remaining') . "</div>" .
                    "<div class='font-mono font-bold text-3xl md:text-5xl tracking-widest timer-display'>00:00:00:00</div>" .
                    "</div>";

            case 'qrcode':
                $url = $data['url'] ?? '';
                $size = (int)($data['size'] ?? 150);
                if ($url) {
                    $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
                    $html = "<div class='{$commonClass} my-8 text-center'><img src='{$qrSrc}' alt='QR Code' class='bg-white shadow-theme mx-auto p-2 border rounded-theme' width='{$size}' height='{$size}'></div>";
                    return $html;
                }
                return '';

            case 'carousel':
                $images = $data['images'] ?? [];
                $validImages = array_values(array_filter($images, fn($img) => !empty(resolve_url($img['url'] ?? ''))));
                if (empty($validImages)) return '';

                $count = count($validImages);
                $autoplay = !empty($data['autoplay']) ? 'true' : 'false';
                // Add touch event handlers for swipe support on mobile
                $alpineData = "{ active: 0, total: {$count}, autoplay: {$autoplay}, timer: null, touchStartX: 0, next() { this.active = (this.active + 1) % this.total }, prev() { this.active = (this.active - 1 + this.total) % this.total }, start() { if(this.autoplay) this.timer = setInterval(() => { this.next() }, 3000); }, stop() { clearInterval(this.timer); }, handleTouchStart(e) { this.touchStartX = e.changedTouches[0].screenX; this.stop(); }, handleTouchEnd(e) { let touchEndX = e.changedTouches[0].screenX; if(this.touchStartX - touchEndX > 50) this.next(); else if(touchEndX - this.touchStartX > 50) this.prev(); this.start(); } }";

                $html = "<div x-data='{$alpineData}' x-init='start()' @mouseenter='stop()' @mouseleave='start()' @touchstart='handleTouchStart' @touchend='handleTouchEnd' class='{$commonClass} relative w-full my-10 rounded-theme overflow-hidden shadow-theme group touch-pan-y'>";

                $html .= "<div class='relative bg-gray-100 w-full aspect-video'>";
                foreach ($validImages as $i => $img) {
                    $src = resolve_url($img['url'] ?? '');
                    $caption = h($img['caption'] ?? '');
                    $html .= "<div x-show='active === {$i}' x-transition:enter='transition ease-out duration-300' x-transition:enter-start='opacity-0' x-transition:enter-end='opacity-100' x-transition:leave='transition ease-in duration-200' x-transition:leave-start='opacity-100' x-transition:leave-end='opacity-0' class='absolute inset-0 w-full h-full'>";
                    $html .= get_image_html($src, [
                        'class'   => 'w-full h-full object-cover',
                        'alt'     => $img['alt'] ?? $img['caption'] ?? ''
                    ]);
                    if ($caption) {
                        $html .= "<div class='right-0 bottom-0 left-0 absolute bg-gradient-to-t from-black/70 to-transparent p-4 text-white text-sm text-center'>{$caption}</div>";
                    }
                    $html .= "</div>";
                }
                $html .= "</div>";

                if ($count > 1) {
                    $html .= "<button @click='prev()' aria-label='Previous slide' class='top-1/2 left-4 absolute bg-white/80 hover:bg-white opacity-0 group-hover:opacity-100 shadow-theme p-2 rounded-full focus:outline-none text-gray-800 transition-opacity -translate-y-1/2 duration-300'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7'/></svg></button>";
                    $html .= "<button @click='next()' aria-label='Next slide' class='top-1/2 right-4 absolute bg-white/80 hover:bg-white opacity-0 group-hover:opacity-100 shadow-theme p-2 rounded-full focus:outline-none text-gray-800 transition-opacity -translate-y-1/2 duration-300'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/></svg></button>";

                    $html .= "<div class='bottom-4 left-1/2 absolute flex space-x-2 -translate-x-1/2'>";
                    for ($i = 0; $i < $count; $i++) {
                        $html .= "<button @click='active = {$i}' aria-label='Go to slide " . ($i + 1) . "' :class=\"{'bg-white w-6': active === {$i}, 'bg-white/50 w-2': active !== {$i}}\" class='shadow-theme rounded-full focus:outline-none h-2 transition-all duration-300'></button>";
                    }
                    $html .= "</div>";
                }
                $html .= "</div>";
                return $html;

            default:
                return null;
        }
    }

    /**
     * Wrap content with client-side decryption logic using Web Crypto API.
     */
    private function renderPasswordProtectWrapper($password, $message, $protectedHtml)
    {
        if ($protectedHtml === '') {
            return '';
        }

        if (!extension_loaded('openssl') || !in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            return $protectedHtml;
        }

        $salt = random_bytes(16);
        $iterations = 100000;
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($protectedHtml, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, "", 16);

        if ($ciphertext === false) {
            return $protectedHtml;
        }

        $payload = base64_encode($salt . $iv . $tag . $ciphertext);

        $uid = 'pwd-' . bin2hex(random_bytes(4));
        $safeMsg = h($message);
        $btnText = h(function_exists('_t') ? _t('btn_unlock') : 'Unlock');
        $phText = h(function_exists('_t') ? _t('ph_password') : 'Password');
        $errText = h(function_exists('_t') ? _t('err_wrong_password') : 'Incorrect password.');
        $httpsErrText = h(function_exists('_t') ? _t('err_https_required') : 'HTTPS is required to unlock this content.');

        // Add data-nosnippet attribute to prevent noise in search engine snippets
        $out = "<div id='{$uid}-container' class='cms-block-password-protect my-12' data-nosnippet>";
        $out .= "<div class='pwd-form bg-gray-50 border border-gray-200 rounded-theme p-8 text-center max-w-lg mx-auto shadow-theme'>";
        $out .= "<svg class='w-12 h-12 mx-auto mb-4 text-gray-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$this->spriteUrl}#outline-lock-closed'></use></svg>";
        $out .= "<p class='mb-6 text-gray-700 font-bold leading-relaxed'>{$safeMsg}</p>";
        $out .= "<form onsubmit=\"event.preventDefault(); window.grindsDecrypt('{$uid}', '{$payload}', '{$errText}');\" class='flex flex-col sm:flex-row gap-3'>";

        $eyeIcon = "<svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$this->spriteUrl}#outline-eye'></use></svg>";
        $eyeSlashIcon = "<svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24' style='display:none;'><use href='{$this->spriteUrl}#outline-eye-slash'></use></svg>";

        $out .= "<div class='relative flex-1'>";
        $out .= "<input type='password' id='{$uid}-input' placeholder='{$phText}' class='w-full flex-1 px-4 py-3 border border-gray-300 rounded-theme focus:outline-none focus:border-theme-primary focus:ring-2 focus:ring-theme-primary/20 transition-all text-center tracking-widest pr-10' required>";
        $out .= "<button type='button'
            onclick=\"var inp = document.getElementById('{$uid}-input'); var isPass = inp.type === 'password'; inp.type = isPass ? 'text' : 'password'; var btn = this; btn.querySelector('svg:first-child').style.display = isPass ? 'none' : 'block'; btn.querySelector('svg:last-child').style.display = isPass ? 'block' : 'none';\"
            class='absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none'>
            {$eyeIcon}{$eyeSlashIcon}
        </button>";
        $out .= "</div>";

        $out .= "<button type='submit' class='bg-theme-primary hover:opacity-90 text-theme-on-primary font-bold px-8 py-3 rounded-theme shadow-theme transition-colors whitespace-nowrap flex items-center justify-center gap-2 transform hover:-translate-y-0.5'>";
        $out .= "<svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$this->spriteUrl}#outline-key'></use></svg>";
        $out .= "{$btnText}</button>";
        $out .= "</form>";
        $out .= "<div id='{$uid}-error' style='display:none;' class='mt-3 text-sm font-bold text-red-600 text-center animate-pulse'></div>";
        $out .= "</div>";
        $out .= "<div id='{$uid}-content' style='display:none;' class='mt-8'></div>";
        $out .= "</div>";

        $script = <<<HTML
<script>
if (typeof window.grindsDecrypt !== 'function') {
    window.grindsDecrypt = async function(id, payloadB64, errMsg) {
        const inputEl = document.getElementById(id + '-input');
        const errEl = document.getElementById(id + '-error');
        if (errEl) errEl.style.display = 'none';
        const btnEl = inputEl.nextElementSibling;
        const originalBtnHtml = btnEl.innerHTML;
        btnEl.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$this->spriteUrl}#outline-arrow-path"></use></svg>';
        btnEl.disabled = true;

        if (!window.crypto || !window.crypto.subtle) {
            const msg = '{$httpsErrText}';
            if (errEl) {
                errEl.innerText = msg;
                errEl.style.display = 'block';
            } else {
                alert(msg);
            }
            inputEl.value = '';
            btnEl.innerHTML = originalBtnHtml;
            btnEl.disabled = false;
            return;
        }

        try {
            const pwdBuf = new TextEncoder().encode(inputEl.value);
            const binStr = atob(payloadB64.replace(/\s+/g, ''));
            const bytes = new Uint8Array(binStr.length);
            for (let i = 0; i < binStr.length; i++) bytes[i] = binStr.charCodeAt(i);

            const salt = bytes.slice(0, 16);
            const iv = bytes.slice(16, 28);
            const tag = bytes.slice(28, 44);
            const ciphertext = bytes.slice(44);

            const keyMaterial = await crypto.subtle.importKey('raw', pwdBuf, { name: 'PBKDF2' }, false, ['deriveBits', 'deriveKey']);
            const key = await crypto.subtle.deriveKey(
                { name: 'PBKDF2', salt: salt, iterations: 100000, hash: 'SHA-256' },
                keyMaterial, { name: 'AES-GCM', length: 256 }, false, ['decrypt']
            );

            const dataToDecrypt = new Uint8Array(ciphertext.length + tag.length);
            dataToDecrypt.set(ciphertext, 0); dataToDecrypt.set(tag, ciphertext.length);
            const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv, tagLength: 128 }, key, dataToDecrypt);
            const html = new TextDecoder().decode(decrypted);
            document.getElementById(id + '-content').innerHTML = html;
            document.getElementById(id + '-content').style.display = 'block';
            document.getElementById(id + '-container').querySelector('.pwd-form').style.display = 'none';
            document.getElementById(id + '-content').querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                if (oldScript.innerHTML) newScript.innerHTML = oldScript.innerHTML;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
            if (typeof window.grindsInitDynamicBlocks === 'function') {
                window.grindsInitDynamicBlocks(document.getElementById(id + '-content'));
            }
        } catch (e) {
            const errEl = document.getElementById(id + '-error');
            if (errEl) {
                errEl.innerText = errMsg;
                errEl.style.display = 'block';
            } else {
                alert(errMsg);
            }
            inputEl.value = ''; inputEl.focus();
            btnEl.innerHTML = originalBtnHtml; btnEl.disabled = false;
        }
    };
}
</script>
HTML;

        return $script . $out;
    }
}
