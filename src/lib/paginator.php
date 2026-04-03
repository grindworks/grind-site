<?php

/**
 * Handle pagination logic and rendering.
 */
if (!defined('GRINDS_APP')) exit;

class Paginator
{
    private readonly int $total;
    private readonly int $limit;
    private readonly int $page;
    private readonly int $num_pages;
    private readonly string $base_path;
    private readonly array $url_params;

    /** Initializes paginator. */
    public function __construct(int $total, int $limit, int $page)
    {
        $this->total = max(0, $total);
        $this->limit = max(1, $limit);
        $this->num_pages = (int)ceil($this->total / $this->limit);

        $requested_page = max(1, $page);
        if ($this->num_pages > 0) {
            $this->page = min($requested_page, $this->num_pages);
        } else {
            $this->page = 1;
        }

        $this->base_path = Routing::getCurrentPath();

        $allowedParams = ['q', 'sort', 'order', 'type', 'status', 'cat', 'limit'];
        $analysis = Routing::analyzeParams($allowedParams);
        $this->url_params = $analysis['query'];

        if ($this->num_pages > 0 && $requested_page > $this->num_pages) {
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
                if (function_exists('is_ajax_request') && !is_ajax_request()) {
                    $redirectUrl = $this->createUrl($this->page);
                    if (!headers_sent()) {
                        header('HTTP/1.1 302 Found');
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                }
            }
        }
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function getNumPages(): int
    {
        return $this->num_pages;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    /** Generates page URL. */
    public function createUrl(int $page): string
    {
        // Handle SSG mode
        global $pageType;
        if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
            $logicalPath = Routing::getRelativePath($this->base_path);
            $logicalPath = trim($logicalPath, '/');

            if ($logicalPath === '') {
                $logicalPath = 'index';
            }

            if ($page > 1) {
                return '/' . $logicalPath . '_' . $page . '.html';
            } else {
                return '/' . $logicalPath . '.html';
            }
        }

        $params = $this->url_params;
        if ($page > 1) {
            $params['page'] = $page;
        }

        $query = http_build_query($params);

        // Resolve logical path excluding base path to prevent false positives in subdirectories
        $requestPath = Routing::getRelativePath($_SERVER['REQUEST_URI']);
        $isAdminContext = str_starts_with($requestPath, '/admin/') || $requestPath === '/admin';

        if (!$isAdminContext) {
            $resolvedPath = Routing::getResolvedPath();

            $parts = explode('/', $resolvedPath);
            $encodedParts = array_map('rawurlencode', $parts);
            $encodedPath = implode('/', $encodedParts);

            return resolve_url($encodedPath) . ($query ? '?' . $query : '');
        }

        return $this->base_path . ($query ? '?' . $query : '');
    }

    private function getPagesArray(): array
    {
        $range = 2;
        $start = max(1, $this->page - $range);
        $end = min($this->num_pages, $this->page + $range);
        $pages = [1, $this->num_pages];
        if ($start <= $end) {
            $pages = array_merge($pages, range($start, $end));
        }
        $pages = array_unique($pages);
        sort($pages);
        return $pages;
    }

    // Render admin pagination
    public function render(): string
    {
        if ($this->num_pages <= 1) return '';

        $html = '<div class="flex justify-between items-center mt-6 pt-4 border-theme-border border-t">';

        // Render mobile view
        $html .= '<div class="sm:hidden flex flex-wrap flex-1 justify-between items-center gap-2">';
        if ($this->page > 1) {
            $html .= '<a href="' . h($this->createUrl($this->page - 1)) . '" class="px-4 py-2 rounded-theme text-xs whitespace-nowrap btn-secondary shrink-0">&larr; ' . _t('prev') . '</a>';
        } else {
            $html .= '<span class="opacity-50 px-4 py-2 rounded-theme text-xs whitespace-nowrap cursor-not-allowed btn-secondary shrink-0">&larr; ' . _t('prev') . '</span>';
        }
        $html .= '<span class="mx-2 font-mono font-bold text-theme-text text-xs">' . $this->page . ' / ' . $this->num_pages . '</span>';
        if ($this->page < $this->num_pages) {
            $html .= '<a href="' . h($this->createUrl($this->page + 1)) . '" class="px-4 py-2 rounded-theme text-xs whitespace-nowrap btn-secondary shrink-0">' . _t('next') . ' &rarr;</a>';
        } else {
            $html .= '<span class="opacity-50 px-4 py-2 rounded-theme text-xs whitespace-nowrap cursor-not-allowed btn-secondary shrink-0">' . _t('next') . ' &rarr;</span>';
        }
        $html .= '</div>';

        // Render desktop view
        $html .= '<div class="hidden sm:flex sm:flex-1 sm:justify-between sm:items-center">';
        $start = ($this->total > 0) ? ($this->page - 1) * $this->limit + 1 : 0;
        $end = min($this->page * $this->limit, $this->total);
        $startHtml = '<span class="font-bold">' . $start . '</span>';
        $endHtml = '<span class="font-bold">' . $end . '</span>';
        $totalHtml = '<span class="font-bold">' . $this->total . '</span>';
        $html .= '<div><p class="text-theme-text text-sm">' . sprintf(_t('showing_results'), $startHtml, $endHtml, $totalHtml) . '</p></div>';

        $html .= '<div><nav class="inline-flex isolate -space-x-px shadow-theme rounded-theme" aria-label="Pagination">';

        // Render previous button
        $prevClass = "relative inline-flex items-center px-2 py-2 text-theme-text ring-1 ring-inset ring-theme-border hover:bg-theme-bg focus:z-20 focus:outline-offset-0 transition-colors";
        if ($this->page > 1) {
            $html .= '<a href="' . h($this->createUrl($this->page - 1)) . '" aria-label="' . _t('prev') . '" class="rounded-l-theme ' . $prevClass . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg') . '#outline-chevron-left') . '"></use></svg></a>';
        } else {
            $html .= '<span class="opacity-50 rounded-l-theme cursor-not-allowed ' . $prevClass . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg') . '#outline-chevron-left') . '"></use></svg></span>';
        }

        // Render page numbers
        $pages = $this->getPagesArray();

        $prev = 0;
        foreach ($pages as $i) {
            if ($prev > 0 && $i > $prev + 1) {
                $html .= '<span class="inline-flex relative items-center bg-theme-surface px-4 py-2 ring-theme-border focus:outline-offset-0 ring-1 ring-inset font-semibold text-theme-text text-sm">...</span>';
            }

            if ($i == $this->page) {
                $html .= '<span aria-current="page" class="inline-flex z-10 focus:z-20 relative items-center bg-theme-primary px-4 py-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-theme-primary focus-visible:outline-offset-2 font-semibold text-theme-on-primary text-sm">' . $i . '</span>';
            } else {
                $html .= '<a href="' . h($this->createUrl($i)) . '" class="inline-flex focus:z-20 relative items-center hover:bg-theme-bg px-4 py-2 ring-theme-border focus:outline-offset-0 ring-1 ring-inset font-semibold text-theme-text text-sm transition-colors">' . $i . '</a>';
            }
            $prev = $i;
        }

        // Render next button
        if ($this->page < $this->num_pages) {
            $html .= '<a href="' . h($this->createUrl($this->page + 1)) . '" aria-label="' . _t('next') . '" class="rounded-r-theme ' . $prevClass . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg') . '#outline-chevron-right') . '"></use></svg></a>';
        } else {
            $html .= '<span class="opacity-50 rounded-r-theme cursor-not-allowed ' . $prevClass . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg') . '#outline-chevron-right') . '"></use></svg></span>';
        }

        $html .= '</nav></div></div></div>';
        return $html;
    }

    // Render frontend pagination
    public function renderFrontend(): string
    {
        if ($this->num_pages <= 1) return '';

        // Handle SSG mode
        global $pageType;
        if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG && isset($pageType) && $pageType === 'search') {
            return '';
        }

        // Check theme template
        global $activeTheme;
        $theme = $activeTheme ?? get_option('site_theme', 'default');
        $themePart = ROOT_PATH . '/theme/' . $theme . '/parts/pagination.php';

        if (file_exists($themePart)) {
            ob_start();
            // $this is available inside the included file
            $paginator = $this;
            include $themePart;
            return ob_get_clean();
        }

        // Render fallback HTML
        $lang = defined('SITE_LANG') ? SITE_LANG : 'en';
        $txtPrev = ($lang === 'ja') ? '前へ' : 'Prev';
        $txtNext = ($lang === 'ja') ? '次へ' : 'Next';

        $html = '<nav class="pagination" aria-label="Pagination">';

        if ($this->page > 1) {
            $html .= '<a href="' . h($this->createUrl($this->page - 1)) . '" class="prev" rel="prev">&laquo; ' . $txtPrev . '</a>';
        }

        // Render page numbers
        $pages = $this->getPagesArray();

        $prev = 0;
        foreach ($pages as $i) {
            if ($prev > 0 && $i > $prev + 1) {
                $html .= '<span class="dots">...</span>';
            }

            if ($i == $this->page) {
                $html .= '<span aria-current="page" class="page-num current">' . $i . '</span>';
            } else {
                $html .= '<a href="' . h($this->createUrl($i)) . '" class="page-num">' . $i . '</a>';
            }
            $prev = $i;
        }

        // Render next button
        if ($this->page < $this->num_pages) {
            $html .= '<a href="' . h($this->createUrl($this->page + 1)) . '" class="next" rel="next">' . $txtNext . ' &raquo;</a>';
        }

        $html .= '</nav>';
        return $html;
    }
}
