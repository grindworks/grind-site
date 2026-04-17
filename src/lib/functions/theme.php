<?php

/**
 * theme.php
 * centralized theme helper functions.
 */
if (!defined('GRINDS_APP'))
    exit;

/**
 * Get current active theme directory name.
 * @return string The directory name of the active theme.
 */
function grinds_get_active_theme(): string
{
    // Check global override from front controller (page specific theme)
    if (isset($GLOBALS['activeTheme']) && is_string($GLOBALS['activeTheme'])) {
        return $GLOBALS['activeTheme'];
    }

    static $activeTheme = null;

    // Return early if cached (avoids redundant I/O)
    if ($activeTheme !== null) {
        return $activeTheme;
    }

    $theme = get_option('site_theme', 'default');

    if (empty($theme) || !preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
        $theme = 'default';
    }

    // Validate existence (Fail-safe)
    if (!is_dir(ROOT_PATH . '/theme/' . $theme)) {
        if ($theme !== 'default') {
            error_log("GrindSite Warning: Theme '$theme' not found. Falling back to default.");
        }
        $theme = 'default';
    }

    // Cache the result for subsequent calls in this lifecycle
    $activeTheme = $theme;

    return $activeTheme;
}

/**
 * Load theme functions.php safely.
 *
 * @param string|null $theme Theme directory name. If null, active theme is used.
 */
if (!function_exists('grinds_load_theme_functions')) {
    function grinds_load_theme_functions($theme = null)
    {
        if ($theme === null) {
            $theme = grinds_get_active_theme();
        }

        if (empty($theme) || !preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
            return;
        }

        if ($theme !== 'default') {
            $defaultFunc = ROOT_PATH . '/theme/default/functions.php';
            if (file_exists($defaultFunc)) {
                require_once $defaultFunc;
            }
        }

        $themeFunc = ROOT_PATH . '/theme/' . $theme . '/functions.php';
        if (file_exists($themeFunc)) {
            require_once $themeFunc;
        }
    }
}

/**
 * Get current language code.
 * @return string
 */
function grinds_get_current_language(): string
{
    if (function_exists('grinds_detect_language')) {
        return (string)grinds_detect_language();
    }
    return 'en';
}

/**
 * Unified translation function.
 * @param string $key The translation key.
 * @param mixed ...$params Optional parameters for printf-style formatting.
 * @return string The translated string.
 */
function theme_t(string $key, ...$params): string
{
    static $messages = null;
    static $loadedLang = null;

    $lang = grinds_get_current_language();

    if ($messages === null || $loadedLang !== $lang) {
        $theme = grinds_get_active_theme();

        // Load translations with fallback (Low priority -> High priority):
        // 1. Default theme (English)
        // 2. Active theme (English)
        // 3. Default theme (Current Lang)
        // 4. Active theme (Current Lang)
        $candidates = [
            ROOT_PATH . '/theme/default/lang/en.php',
            ROOT_PATH . '/theme/' . $theme . '/lang/en.php',
            ROOT_PATH . '/theme/default/lang/' . $lang . '.php',
            ROOT_PATH . '/theme/' . $theme . '/lang/' . $lang . '.php',
        ];

        $candidates = array_unique($candidates);
        $messages = [];
        foreach ($candidates as $file) {
            if (file_exists($file)) {
                $loaded = require $file;
                if (is_array($loaded)) {
                    $messages = array_merge($messages, $loaded);
                }
            }
        }
        $loadedLang = $lang;
    }

    $text = (string)($messages[$key] ?? $key);

    if (!empty($params)) {
        // Suppress errors from vsprintf and check its return value.
        // This prevents the UI from breaking if a translation is missing placeholders.
        $result = @vsprintf($text, $params);

        // If vsprintf fails, it returns false. Fallback to the unformatted string.
        return $result !== false ? $result : $text;
    }

    return $text;
}

/**
 * Check if a menu URL is active.
 */
function grinds_is_menu_active($menuUrl)
{
    // Skip empty or fragment links
    if (empty($menuUrl) || $menuUrl === '#' || str_starts_with($menuUrl, 'javascript:')) {
        return false;
    }

    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $currentPath = rtrim($reqPath, '/') ?: '/';

    // Normalize menu URL (handle full URLs vs relative)
    $menuPathRaw = parse_url($menuUrl, PHP_URL_PATH) ?? '/';
    $menuPath = rtrim($menuPathRaw, '/') ?: '/';

    $homePathRaw = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
    $homePath = rtrim($homePathRaw, '/') ?: '/';

    // 1. Exact match for Home
    if ($menuPath === $homePath) {
        return $currentPath === $homePath;
    }

    // 2. Exact match or Prefix match for sub-pages
    // Ensure we don't match "/cat" against "/category" by adding slash
    if ($currentPath === $menuPath) {
        return true;
    }

    if (str_starts_with($currentPath, $menuPath . '/')) {
        return true;
    }

    return false;
}

/**
 * Helper to determine the best OGP image for a page.
 * @internal
 */
function _theme_generate_ogp_image(array $pageData, bool &$isFallback = false): string
{
    $ogImage = '';
    $isFallback = false;

    // 1. Post thumbnail
    if (isset($pageData['post']['thumbnail']) && !empty($pageData['post']['thumbnail'])) {
        $ogImage = resolve_url($pageData['post']['thumbnail']);
    }
    // 2. Hero image
    if (empty($ogImage) && isset($pageData['post']['hero_image']) && !empty($pageData['post']['hero_image'])) {
        $ogImage = resolve_url($pageData['post']['hero_image']);
    }
    // 3. Content image
    if (empty($ogImage) && isset($pageData['post']['content'])) {
        $rawContent = $pageData['post']['content'] ?? '{}';
        $contentData = $pageData['post']['content_decoded'] ?? json_decode($rawContent, true);

        if (is_array($contentData) && !empty($contentData['blocks'])) {
            $images = BlockRenderer::extractImages($contentData['blocks']);
            if (!empty($images)) {
                foreach ($images as $imgCandidate) {
                    $ext = strtolower(pathinfo((string)parse_url($imgCandidate, PHP_URL_PATH), PATHINFO_EXTENSION));
                    if ($ext !== 'svg') {
                        $ogImage = resolve_url($imgCandidate);
                        break;
                    }
                }
            }
        }
    }
    // 4. Site default / Fallback
    if (empty($ogImage)) {
        $defaultOgp = get_option('site_ogp_image');
        if ($defaultOgp) {
            $ogImage = resolve_url((string)$defaultOgp);
            $isFallback = true;
        }
    }

    return $ogImage;
}

/**
 * Helper to generate JSON-LD structured data.
 * @internal
 */
function _theme_generate_json_ld(string $siteName, string $pageType, string $pageTitle, string $canonicalUrl, string $finalDesc, string $ogImage, array $pageData, bool $isFallbackImage = false): array
{
    $graph = [];
    $homeUrl = rtrim(resolve_url('/'), '/') . '/';
    $webPageId = $canonicalUrl . '#webpage';
    $orgId = $homeUrl . '#organization';
    $authorId = $homeUrl . '#author';

    $searchTarget = (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) ? '/search.html?q={search_term_string}' : '/search?q={search_term_string}';

    $pubLogo = get_option('admin_logo') ?: get_option('site_ogp_image');
    $pubLogoUrl = $pubLogo ? resolve_url($pubLogo) : (function_exists('get_favicon_url') ? get_favicon_url() : resolve_url('/favicon.ico'));

    // Ensure logo is absolute URL with scheme for JSON-LD validation
    if (str_starts_with($pubLogoUrl, '//')) {
        $parsedBase = parse_url(resolve_url('/'));
        $scheme = $parsedBase['scheme'] ?? 'https';
        $pubLogoUrl = $scheme . ':' . $pubLogoUrl;
    } elseif (!str_starts_with($pubLogoUrl, 'http') && !str_starts_with($pubLogoUrl, 'data:')) {
        $scheme = (function_exists('is_ssl') && is_ssl()) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $pubLogoUrl = $scheme . $host . '/' . ltrim($pubLogoUrl, '/');
    }

    $socialLinksRaw = get_option('official_social_links', '');

    $socialLinks = array_values(array_filter(array_map('trim', explode("\n", $socialLinksRaw)), function ($link) {
        return filter_var($link, FILTER_VALIDATE_URL) !== false && preg_match('/^https?:\/\//i', $link);
    }));

    /**
     * Sanitize site name.
     */
    $cleanSiteName = html_entity_decode(strip_tags($siteName), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $orgNode = [
        "@type" => "Organization",
        "@id" => $orgId,
        "name" => $cleanSiteName,
        "url" => $homeUrl
    ];

    if (!str_starts_with($pubLogoUrl, 'data:')) {
        $orgNode["logo"] = [
            "@type" => "ImageObject",
            "url" => $pubLogoUrl
        ];
    }

    if (!empty($socialLinks)) {
        $orgNode["sameAs"] = $socialLinks;
    }
    $graph[] = $orgNode;

    $postDataObj = $pageData['post'] ?? [];
    $rawContent = $postDataObj['content'] ?? '{}';
    $contentData = $postDataObj['content_decoded'] ?? json_decode($rawContent, true);

    $extractedAuthor = is_array($contentData) && function_exists('grinds_extract_author_from_content') ? grinds_extract_author_from_content($contentData) : null;

    $heroSettings = isset($postDataObj['hero_settings_decoded'])
        ? $postDataObj['hero_settings_decoded']
        : json_decode($postDataObj['hero_settings'] ?? '{}', true);

    $postAuthor = $extractedAuthor['name'] ?? trim($heroSettings['seo_author'] ?? '');

    if ($postAuthor) {
        $authorNode = [
            "@type" => "Person",
            "@id" => $authorId,
            "name" => $postAuthor
        ];

        if ($extractedAuthor) {
            if (!empty($extractedAuthor['jobTitle'])) {
                $authorNode["jobTitle"] = $extractedAuthor['jobTitle'];
            }
            if (!empty($extractedAuthor['description'])) {
                $authorNode["description"] = $extractedAuthor['description'];
            }
            if (!empty($extractedAuthor['url'])) {
                $authorNode["url"] = $extractedAuthor['url'];
                $authorNode["sameAs"] = [$extractedAuthor['url']];
            }
        } else {
            if (!empty($socialLinks)) {
                $authorNode["sameAs"] = $socialLinks;
            }

            if (function_exists('get_sidebar_widgets')) {
                foreach (get_sidebar_widgets() as $w) {
                    if ($w['type'] === 'profile') {
                        $pSettings = json_decode($w['settings'] ?? '{}', true);
                        $bio = $w['content'] ?? $pSettings['text'] ?? '';
                        if ($bio) {
                            $authorNode["description"] = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($bio) : strip_tags($bio);
                        }
                        break;
                    }
                }
            }
        }

        $graph[] = $authorNode;
    } else {
        $authorId = $orgId;
    }

    // 3. WebSite Entity
    $graph[] = [
        "@type" => "WebSite",
        "@id" => $homeUrl . "#website",
        "name" => $cleanSiteName,
        "url" => $homeUrl,
        "publisher" => ["@id" => $orgId],
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => resolve_url($searchTarget)
            ],
            "query-input" => "required name=search_term_string"
        ]
    ];

    // 4. Breadcrumb Entity
    $breadcrumbs = [];
    $breadcrumbs[] = ["@type" => "ListItem", "position" => 1, "name" => theme_t('Home'), "item" => $homeUrl];

    if (in_array($pageType, ['category', 'tag', 'page'], true)) {
        $breadcrumbs[] = ["@type" => "ListItem", "position" => 2, "name" => $pageTitle, "item" => $canonicalUrl];
    } elseif ($pageType === 'single') {
        if (!empty($pageData['post']['category_name'])) {
            $catUrl = resolve_url('/category/' . rawurlencode($pageData['post']['category_slug']));
            if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
                $catUrl .= '.html';
            }
            $breadcrumbs[] = ["@type" => "ListItem", "position" => 2, "name" => $pageData['post']['category_name'], "item" => $catUrl];
            $breadcrumbs[] = ["@type" => "ListItem", "position" => 3, "name" => $pageTitle, "item" => $canonicalUrl];
        } else {
            $breadcrumbs[] = ["@type" => "ListItem", "position" => 2, "name" => $pageTitle, "item" => $canonicalUrl];
        }
    }

    if (count($breadcrumbs) > 1) {
        $graph[] = [
            "@type" => "BreadcrumbList",
            "@id" => $canonicalUrl . "#breadcrumb",
            "itemListElement" => $breadcrumbs
        ];
    }

    $applyImageMeta = function (array &$imageNode, string $imageUrl) use ($siteName) {
        global $grinds_image_meta_cache;
        $baseUrl = rtrim(resolve_url('/'), '/') . '/';
        $relPath = preg_replace('/\?.*$/', '', ltrim(str_replace($baseUrl, '', $imageUrl), '/'));

        if (isset($grinds_image_meta_cache[$relPath])) {
            $meta = $grinds_image_meta_cache[$relPath];
            if (!empty($meta['credit'])) {
                $imageNode['creditText'] = $meta['credit'];
                $imageNode['creator'] = [
                    "@type" => "Person",
                    "name" => $meta['credit']
                ];
            }
            if (!empty($meta['license_url']) && filter_var($meta['license_url'], FILTER_VALIDATE_URL)) {
                $imageNode['license'] = $meta['license_url'];
            } elseif (!empty($meta['license']) && $meta['license'] === 'owned') {
                $imageNode['copyrightNotice'] = "© " . date('Y') . " " . $siteName;
            }
            if (!empty($meta['acquire_license_page']) && filter_var($meta['acquire_license_page'], FILTER_VALIDATE_URL)) {
                $imageNode['acquireLicensePage'] = $meta['acquire_license_page'];
            }
            if (!empty($meta['is_ai'])) {
                $source = $meta['source'] ?? 'AI';
                $imageNode['description'] = "This image was generated by AI ({$source}).";
                $imageNode['iptcDigitalSourceType'] = "https://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia";
            }
            if (!empty($meta['width']) && !empty($meta['height'])) {
                $imageNode['width'] = $meta['width'];
                $imageNode['height'] = $meta['height'];
            }
        }
    };

    // 5. WebPage Entity
    $webPageType = 'WebPage';
    if ($pageType === 'page' && isset($pageData['post'])) {
        $slug = strtolower($pageData['post']['slug'] ?? '');
        if (in_array($slug, ['about', 'profile', 'company'], true)) {
            $webPageType = "AboutPage";
        } elseif (in_array($slug, ['contact', 'inquiry'], true)) {
            $webPageType = "ContactPage";
        }
    }

    $webPageNode = [
        "@type" => $webPageType,
        "@id" => $webPageId,
        "url" => $canonicalUrl,
        "name" => $pageTitle,
        "isPartOf" => ["@id" => $homeUrl . "#website"],
        "description" => $finalDesc,
        "inLanguage" => grinds_get_current_language()
    ];

    if (count($breadcrumbs) > 1) {
        $webPageNode['breadcrumb'] = ["@id" => $canonicalUrl . "#breadcrumb"];
    }

    // 6. Article / Content Entities
    if (($pageType === 'single' || $pageType === 'page') && isset($pageData['post'])) {
        $postDate = $pageData['post']['published_at'] ?? $pageData['post']['created_at'];
        $updateDate = $pageData['post']['updated_at'] ?? $postDate;

        $postTs = strtotime((string)$postDate) ?: time();
        $updateTs = strtotime((string)$updateDate) ?: $postTs;

        $articleIdx = -1;
        if ($pageType === 'single') {
            $plainTextContent = trim(preg_replace('/\s+/u', ' ', grinds_extract_text_from_content($rawContent)));

            $lang = strtolower(grinds_get_current_language());
            $isNonSpacedLang = in_array(substr($lang, 0, 2), ['ja', 'zh', 'ko', 'th', 'vi']);
            $wordCount = $isNonSpacedLang
                ? mb_strlen($plainTextContent, 'UTF-8')
                : str_word_count($plainTextContent);

            $articleNode = [
                "@type" => "Article",
                "@id" => $canonicalUrl . "#article",
                "isPartOf" => ["@id" => $webPageId],
                "mainEntityOfPage" => ["@id" => $webPageId],
                "inLanguage" => grinds_get_current_language(),
                "isAccessibleForFree" => true,
                "headline" => mb_strimwidth(html_entity_decode(strip_tags($pageData['post']['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, 110, '...', 'UTF-8'),
                "datePublished" => date('c', $postTs),
                "dateModified" => date('c', $updateTs),
                "author" => ["@id" => $authorId],
                "publisher" => ["@id" => $orgId],
                "description" => $finalDesc,
                "wordCount" => $wordCount,
                "articleBody" => $plainTextContent
            ];

            $webPageNode['mainEntity'] = ["@id" => $canonicalUrl . "#article"];

            if (!empty($pageData['post']['category_name'])) {
                $articleNode['articleSection'] = $pageData['post']['category_name'];
            }
            if (!empty($pageData['tags'])) {
                $tagNames = array_column($pageData['tags'], 'name');
                $articleNode['keywords'] = implode(', ', $tagNames);

                $aboutEntities = [];
                foreach ($pageData['tags'] as $tag) {
                    $tagUrl = resolve_url('/tag/' . rawurlencode($tag['slug']));
                    if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
                        $tagUrl .= '.html';
                    }
                    $aboutEntities[] = [
                        "@type" => "Thing",
                        "name" => $tag['name'],
                        "url" => $tagUrl
                    ];
                }
                $articleNode['about'] = $aboutEntities;
            }

            if ($ogImage && !$isFallbackImage) {
                if (function_exists('grinds_preload_image_meta')) grinds_preload_image_meta([$ogImage]);
                $imageNode = [
                    "@type" => "ImageObject",
                    "@id" => $ogImage . '#image',
                    "url" => $ogImage,
                    "contentUrl" => $ogImage
                ];
                $applyImageMeta($imageNode, $ogImage);
                $articleNode['image'] = [$imageNode];
            }

            $graph[] = $articleNode;
            $articleIdx = count($graph) - 1;
        } else {
            $webPageNode['datePublished'] = date('c', $postTs);
            $webPageNode['dateModified'] = date('c', $updateTs);

            if ($ogImage) {
                if (function_exists('grinds_preload_image_meta')) grinds_preload_image_meta([$ogImage]);
                $imageNode = [
                    "@type" => "ImageObject",
                    "@id" => $ogImage . '#image',
                    "url" => $ogImage,
                    "contentUrl" => $ogImage
                ];
                $applyImageMeta($imageNode, $ogImage);
                $webPageNode['primaryImageOfPage'] = $imageNode;
                $webPageNode['image'] = $imageNode;
            }
        }

        $rawContent = $pageData['post']['content'] ?? '{}';
        $contentData = $pageData['post']['content_decoded'] ?? json_decode($rawContent, true);

        if (is_array($contentData) && !empty($contentData['blocks'])) {
            $reviews = [];
            $citations = [];
            $faqs = [];
            $howtos = [];
            $currentRating = null;

            foreach ($contentData['blocks'] as $block) {
                $bType = $block['type'] ?? '';
                $bData = $block['data'] ?? [];

                if ($bType === 'password_protect') {
                    break;
                }

                if ($bType === 'rating' && isset($bData['score'])) {
                    $currentRating = [
                        "@type" => "Rating",
                        "ratingValue" => (string)$bData['score'],
                        "bestRating" => (string)($bData['max'] ?? 5)
                    ];
                }

                if ($bType === 'testimonial' && !empty($bData['name']) && !empty($bData['comment'])) {
                    $reviewNode = [
                        "@type" => "Review",
                        "itemReviewed" => [
                            "@type" => "Organization",
                            "name" => $cleanSiteName
                        ],
                        "author" => [
                            "@type" => "Person",
                            "name" => html_entity_decode(strip_tags($bData['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8')
                        ],
                        "reviewBody" => html_entity_decode(function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($bData['comment']) : strip_tags($bData['comment']), ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    ];
                    if ($currentRating) {
                        $reviewNode["reviewRating"] = $currentRating;
                        $currentRating = null;
                    }
                    $reviews[] = $reviewNode;
                }
                if ($bType === 'quote' && !empty($bData['citeUrl']) && filter_var($bData['citeUrl'], FILTER_VALIDATE_URL)) {
                    $citations[] = $bData['citeUrl'];
                }
                if ($bType === 'card' && !empty($bData['url']) && filter_var($bData['url'], FILTER_VALIDATE_URL)) {
                    $citations[] = $bData['url'];
                }

                if ($bType === 'accordion' && !empty($bData['items'])) {
                    foreach ($bData['items'] as $item) {
                        $q = trim(function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($item['title'] ?? '') : strip_tags($item['title'] ?? ''));
                        $a = trim(function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($item['content'] ?? '') : strip_tags($item['content'] ?? ''));
                        if ($q !== '' && $a !== '') {
                            $faqs[] = [
                                "@type" => "Question",
                                "name" => $q,
                                "acceptedAnswer" => [
                                    "@type" => "Answer",
                                    "text" => $a
                                ]
                            ];
                        }
                    }
                }

                if ($bType === 'step' && !empty($bData['items'])) {
                    $steps = [];
                    foreach ($bData['items'] as $index => $item) {
                        $stepTitle = trim(function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($item['title'] ?? '') : strip_tags($item['title'] ?? ''));
                        $stepDesc = trim(function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($item['desc'] ?? '') : strip_tags($item['desc'] ?? ''));
                        if ($stepTitle !== '') {
                            $stepNode = [
                                "@type" => "HowToStep",
                                "name" => $stepTitle,
                                "url" => $canonicalUrl . "#step-" . ($index + 1)
                            ];
                            if ($stepDesc !== '') {
                                $stepNode["text"] = $stepDesc;
                            }
                            $steps[] = $stepNode;
                        }
                    }
                    if (!empty($steps)) {
                        $howtos[] = [
                            "@type" => "HowTo",
                            "name" => $pageTitle . " - " . theme_t('Guide'),
                            "step" => $steps
                        ];
                    }
                }
            }

            if (!empty($reviews)) {
                if ($pageType === 'single' && $articleIdx >= 0) {
                    $graph[$articleIdx]['review'] = $reviews;
                } else {
                    $webPageNode['review'] = $reviews;
                }
            }
            if (!empty($citations)) {
                $citations = array_values(array_unique($citations));
                if ($pageType === 'single' && $articleIdx >= 0) {
                    $graph[$articleIdx]['citation'] = $citations;
                }
            }

            if (!empty($faqs)) {
                $graph[] = [
                    "@type" => "FAQPage",
                    "@id" => $canonicalUrl . "#faq",
                    "mainEntity" => $faqs
                ];
            }
            if (!empty($howtos)) {
                foreach ($howtos as $howto) {
                    $graph[] = $howto;
                }
            }
        }
    }

    $graph[] = $webPageNode;

    return [
        "@context" => "https://schema.org",
        "@graph" => $graph
    ];
}

/**
 * Helper to generate canonical URLs.
 * @internal
 */
function _theme_generate_canonical_url(string $pageType, array $pageData, string &$finalTitle, string &$finalDesc): array
{
    $canonicalUrl = rtrim(resolve_url('/'), '/');
    $urlPath = '';

    switch ($pageType) {
        case 'home':
            $urlPath = '/';
            break;
        case 'single':
            if (!empty($pageData['post']['slug'])) {
                if (function_exists('get_permalink')) {
                    $canonicalUrl = get_permalink($pageData['post']['slug']);
                    $urlPath = '';
                } else {
                    $urlPath = '/' . rawurlencode($pageData['post']['slug']);
                }
            }
            break;
        case 'category':
            if (!empty($pageData['category']['slug'])) {
                $urlPath = '/category/' . rawurlencode($pageData['category']['slug']);
            }
            break;
        case 'tag':
            if (!empty($pageData['tag']['slug'])) {
                $urlPath = '/tag/' . rawurlencode($pageData['tag']['slug']);
            }
            break;
        case 'search':
            $q = isset($_GET['q']) ? $_GET['q'] : '';
            $urlPath = '/search?q=' . rawurlencode($q);
            break;
    }

    // Pagination
    $currentPage = 1;
    if (isset($pageData['paginator']) && is_object($pageData['paginator'])) {
        $currentPage = $pageData['paginator']->getPage();
    } else {
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    }

    if (!in_array($pageType, ['home', 'category', 'tag', 'search'], true)) {
        $currentPage = 1;
    }

    // 1. Store base URL (without pagination) for OGP
    // Prevents SNS share counts from splitting across paginated URLs like ?page=2
    $ogpUrl = $canonicalUrl . $urlPath;
    if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
        if ($pageType === 'home') {
            $ogpUrl = $canonicalUrl . '/';
        } elseif ($pageType !== 'home' && !str_ends_with($ogpUrl, '.html')) {
            if (str_contains($ogpUrl, '?')) {
                $parts = explode('?', $ogpUrl, 2);
                $ogpUrl = $parts[0] . '.html?' . $parts[1];
            } elseif (!str_ends_with($ogpUrl, '/')) {
                $ogpUrl .= '.html';
            }
        }
    }

    // 2. Append pagination parameters to Canonical URL (for Googlebot)
    if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
        if ($pageType === 'home' && $currentPage > 1) {
            $urlPath = '/index';
        }
        $canonicalUrl .= $urlPath;

        if ($currentPage > 1) {
            $canonicalUrl .= '_' . $currentPage . '.html';
        } elseif ($pageType !== 'home' && !str_ends_with($canonicalUrl, '.html')) {
            if (str_contains($canonicalUrl, '?')) {
                $parts = explode('?', $canonicalUrl, 2);
                $canonicalUrl = $parts[0] . '.html?' . $parts[1];
            } elseif (!str_ends_with($canonicalUrl, '/')) {
                $canonicalUrl .= '.html';
            }
        }
    } else {
        $canonicalUrl .= $urlPath;
        if ($currentPage > 1) {
            $connector = (str_contains($canonicalUrl, '?')) ? '&' : '?';
            $canonicalUrl .= $connector . 'page=' . $currentPage;
        }
    }

    if ($currentPage > 1) {
        $pageSuffix = (grinds_get_current_language() === 'ja') ? " - {$currentPage}ページ目" : " - Page {$currentPage}";
        $finalTitle .= $pageSuffix;
        $finalDesc .= $pageSuffix;
    }

    $showCanonical = !(defined('GRINDS_IS_SSG') && GRINDS_IS_SSG && !get_option('ssg_base_url'));

    return [$canonicalUrl, $showCanonical, $ogpUrl];
}

/**
 * Generate standard HEAD metadata.
 *
 * @param array $context  Page context data ($ctx in layout.php)
 * @return array          Array of variables to extract in layout.php
 */
function grinds_get_header_data(array $context = []): array
{
    $pageType = $context['type'] ?? 'home';
    $pageData = $context['data'] ?? [];

    $siteName = get_option('site_name', defined('CMS_NAME') ? CMS_NAME : 'GrindSite');

    // Page Title
    global $pageTitle;

    if ($pageType === 'home') {
        $finalTitle = $siteName;
        if (!empty($pageTitle) && $pageTitle !== 'Home') {
            $finalTitle .= ' | ' . $pageTitle;
        }
    } else {
        $fmt = get_option('title_format', '{page_title} - {site_name}');
        $finalTitle = str_replace(['{page_title}', '{site_name}'], [$pageTitle, $siteName], $fmt);
    }

    /**
     * Sanitize page title.
     */
    $finalTitle = html_entity_decode(strip_tags($finalTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Clean up line breaks and multiple spaces in title
    $finalTitle = trim(preg_replace('/\s+/u', ' ', $finalTitle) ?? $finalTitle);

    // Page Description
    global $pageDesc;
    $finalDesc = $pageDesc ?? '';
    if (empty($finalDesc)) {
        $siteDesc = get_option('site_description');
        if ($pageType === 'category' && !empty($pageData['category']['name'])) {
            $finalDesc = $pageData['category']['name'] . ' | ' . $siteDesc;
        } elseif ($pageType === 'tag' && !empty($pageData['tag']['name'])) {
            $finalDesc = '#' . $pageData['tag']['name'] . ' | ' . $siteDesc;
        } else {
            $finalDesc = $siteDesc;
        }
    }
    // Sanitize HTML tags from description to prevent them from appearing in search results.
    $finalDesc = str_replace(["\r", "\n"], ' ', (string)$finalDesc);
    if (str_contains($finalDesc, '<')) {
        $finalDesc = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($finalDesc) : strip_tags($finalDesc);
    }
    // Always decode entities to pure plain text, as it will be safely re-escaped by h() in layout.php
    $finalDesc = html_entity_decode($finalDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Optimize whitespace: collapse multiple spaces into one and trim
    $finalDesc = trim(preg_replace('/\s+/u', ' ', $finalDesc) ?? $finalDesc);

    $finalDesc = mb_strimwidth($finalDesc, 0, 240, '...', 'UTF-8');

    // OGP Image
    $isFallbackImage = false;
    $ogImage = _theme_generate_ogp_image($pageData, $isFallbackImage);

    // Ensure OGP image is absolute URL with scheme (Fix for JSON-LD/Schema.org and SNS)
    if ($ogImage) {
        if (str_starts_with($ogImage, '//')) {
            $parsedBase = parse_url(resolve_url('/'));
            $scheme = $parsedBase['scheme'] ?? 'https';
            $ogImage = $scheme . ':' . $ogImage;
        } else {
            $ogImage = resolve_url($ogImage);

            if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG && function_exists('get_option')) {
                $ssgBase = get_option('ssg_base_url', '');
                if (!empty($ssgBase)) {
                    $ogImage = grinds_ssg_replace_base_url($ogImage, $ssgBase);
                }
            }

            if (!str_starts_with($ogImage, 'http') && !str_starts_with($ogImage, '//')) {
                $scheme = (function_exists('is_ssl') && is_ssl()) ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $ogImage = $scheme . $host . '/' . ltrim($ogImage, '/');
            }
        }
    }

    $ogImageWidth = '';
    $ogImageHeight = '';
    if ($ogImage) {
        global $grinds_image_meta_cache;
        if (function_exists('grinds_preload_image_meta')) {
            grinds_preload_image_meta([$ogImage]);
        }
        $baseUrl = rtrim(resolve_url('/'), '/') . '/';
        $relPath = preg_replace('/\?.*$/', '', ltrim(str_replace($baseUrl, '', $ogImage), '/'));

        if (isset($grinds_image_meta_cache[$relPath])) {
            $meta = $grinds_image_meta_cache[$relPath];
            if (!empty($meta['width']) && !empty($meta['height'])) {
                $ogImageWidth = $meta['width'];
                $ogImageHeight = $meta['height'];
            }
        }
    }

    // Canonical & URL Path
    [$canonicalUrl, $showCanonical, $ogpUrl] = _theme_generate_canonical_url($pageType, $pageData, $finalTitle, $finalDesc);

    // OGP Type
    $ogType = ($pageType === 'home') ? 'website' : 'article';

    // JSON-LD
    $jsonLd = _theme_generate_json_ld($siteName, $pageType, $pageTitle ?? '', $canonicalUrl, $finalDesc, $ogImage, $pageData, $isFallbackImage);

    // Robots
    $stats = [];

    // Force noindex in preview mode to prevent conflict with X-Robots-Tag
    if (isset($_GET['preview']) && $_GET['preview'] !== '') {
        $stats[] = 'noindex';
        $stats[] = 'nofollow';
    }

    if (get_option('site_noindex')) {
        $stats[] = 'noindex';
        $stats[] = 'nofollow';
    }
    $tdmReservation = false;
    if (get_option('site_block_ai')) {
        $stats[] = 'noai';
        $stats[] = 'noimageai';
        $tdmReservation = true;
    }
    if (isset($pageData['post'])) {
        if (!empty($pageData['post']['is_noindex']))
            $stats[] = 'noindex';
        if (!empty($pageData['post']['is_nofollow']))
            $stats[] = 'nofollow';
        if (!empty($pageData['post']['is_noarchive']))
            $stats[] = 'noarchive';
    }
    if ($pageType === 'search') {
        $stats[] = 'noindex';
    }

    $defaultRobots = ['max-image-preview:large', 'max-snippet:-1', 'max-video-preview:-1'];

    if (!in_array('noindex', $stats, true)) {
        $stats[] = 'index';
        $stats = array_merge($stats, $defaultRobots);
    }
    if (!in_array('nofollow', $stats, true)) {
        $stats[] = 'follow';
    }
    $robotsStr = implode(', ', array_unique($stats));

    // SEO Conflict Resolution: Disable canonical output if page is noindex
    if (in_array('noindex', $stats, true)) {
        $showCanonical = false;
    }

    $prevUrl = '';
    $nextUrl = '';
    if (isset($pageData['paginator']) && is_object($pageData['paginator'])) {
        $currentPage = $pageData['paginator']->getPage();
        $totalPages = $pageData['paginator']->getNumPages();
        if ($currentPage > 1) {
            $prevUrl = $pageData['paginator']->createUrl($currentPage - 1);
        }
        if ($currentPage < $totalPages) {
            $nextUrl = $pageData['paginator']->createUrl($currentPage + 1);
        }
    }

    return [
        'siteName' => $siteName,
        'finalTitle' => $finalTitle,
        'finalDesc' => $finalDesc,
        'ogImage' => $ogImage,
        'ogImageWidth' => $ogImageWidth,
        'ogImageHeight' => $ogImageHeight,
        'isFallbackImage' => $isFallbackImage,
        'ogType' => $ogType,
        'canonicalUrl' => $canonicalUrl,
        'ogpUrl' => $ogpUrl,
        'showCanonical' => $showCanonical,
        'jsonLd' => $jsonLd,
        'robots' => $robotsStr,
        'htmlLang' => grinds_get_current_language(),
        'prevUrl' => $prevUrl,
        'nextUrl' => $nextUrl,
        // Pass through for convenience
        'pageType' => $pageType,
        'pageData' => $pageData,
        'tdmReservation' => $tdmReservation
    ];
}

/**
 * Get social share buttons data.
 */
function grinds_get_share_buttons($url = null, $title = null)
{
    if ($url === null) {
        $protocol = (function_exists('is_ssl') && is_ssl()) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $url = "{$protocol}://{$host}{$uri}";
    }
    if ($title === null) {
        $title = get_option('site_name');
        global $pageTitle;
        if (!empty($pageTitle)) {
            $title = $pageTitle . ' - ' . $title;
        }
    }

    $urlEnc = rawurlencode($url);
    $titleEnc = rawurlencode($title);

    $shareButtonsJson = get_option('share_buttons', '[]');
    $shareButtons = json_decode($shareButtonsJson, true);

    if (!is_array($shareButtons) || empty($shareButtons)) {
        $shareButtons = function_exists('get_default_share_buttons') ? get_default_share_buttons() : [];
    }

    $results = [];
    $spriteUrl = resolve_url('assets/img/sprite.svg');

    foreach ($shareButtons as $button) {
        if (empty($button['enabled']))
            continue;

        $results[] = [
            'share_url' => str_ireplace(['{URL}', '{TITLE}'], [$urlEnc, $titleEnc], $button['url']),
            'name' => h($button['name']),
            'icon' => h($button['icon']),
            'color' => h($button['color']),
            'display_name' => trim(preg_replace('/\(.*\)/', '', $button['name'])),
            'sprite_url' => $spriteUrl
        ];
    }

    return $results;
}

/**
 * Load custom fields definition from theme.json
 *
 * @param string $context 'post' or 'category'
 * @param string|null $specificTheme Specific theme name to load from
 * @return array
 */
if (!function_exists('grinds_get_theme_custom_fields')) {
    function grinds_get_theme_custom_fields(string $context = 'post', ?string $specificTheme = null): array
    {
        $theme = $specificTheme ?: grinds_get_active_theme();
        $jsonFile = ROOT_PATH . '/theme/' . $theme . '/theme.json';
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (isset($data['custom_fields'][$context]) && is_array($data['custom_fields'][$context])) {
                return $data['custom_fields'][$context];
            }
        }
        return [];
    }
}

/**
 * Load Custom Post Types definition from theme.json
 *
 * @return array
 */
if (!function_exists('grinds_get_theme_post_types')) {
    function grinds_get_theme_post_types(): array
    {
        $theme = grinds_get_active_theme();
        $jsonFile = ROOT_PATH . '/theme/' . $theme . '/theme.json';
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (isset($data['post_types']) && is_array($data['post_types'])) {
                return $data['post_types'];
            }
        }
        return [];
    }
}

/**
 * Get custom field (meta_data) value from a post or category securely.
 *
 * @param array $item The post or category array containing 'meta_data'
 * @param string $key The custom field name
 * @param mixed $default The fallback value if not found
 * @return mixed
 */
if (!function_exists('get_custom_field')) {
    function get_custom_field(array $item, string $key, $default = null)
    {
        if (empty($item['meta_data'])) {
            return $default;
        }
        $meta = is_string($item['meta_data']) ? json_decode($item['meta_data'], true) : $item['meta_data'];
        if (is_array($meta) && array_key_exists($key, $meta)) {
            return $meta[$key] !== '' ? $meta[$key] : $default;
        }
        return $default;
    }
}
