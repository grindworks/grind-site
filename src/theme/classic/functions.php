<?php

if (!defined('GRINDS_APP'))
    exit;

/**
 * functions.php
 * Define theme helper functions.
 */

/**
 * Translate theme string.
 */
// function theme_t removed (centralized in core)

if (!function_exists('classic_theme_posted_on')) {
    function classic_theme_posted_on()
    {
        $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
        $time_string = sprintf(
            $time_string,
            get_the_date('c'),
            get_the_date()
        );

        echo '<span class="posted-on">' . theme_t('Posted on %s', $time_string) . '</span>';
    }
}

/**
 * Render share buttons.
 */
if (!function_exists('classic_the_share_buttons')) {
    function classic_the_share_buttons($url = null, $title = null)
    {
        $buttons = grinds_get_share_buttons($url, $title);
        if (empty($buttons))
            return;

        echo '<div class="share-buttons" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">';
        echo '<span style="font-weight:bold; margin-right:10px;">' . theme_t('Share') . ':</span>';

        foreach ($buttons as $button) {
            echo '<a href="' . h($button['share_url']) . '" target="_blank" rel="noopener noreferrer" style="display:inline-block; margin-right:5px; padding:5px 10px; background:' . h($button['color']) . '; color:#fff; text-decoration:none; border-radius:3px; font-size:0.8rem;">';
            echo h($button['name']);
            echo '</a>';
        }
        echo '</div>';
    }
}

/**
 * Render content blocks.
 */
function classic_render_block($block, $pathFixer)
{
    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];

    $commonClass = "cms-block-" . $type;

    switch ($type) {
        case 'header':
            $level = strtolower($data['level'] ?? 'h2');
            if (!preg_match('/^h[2-6]$/', $level))
                $level = 'h2';
            $text = h($data['text'] ?? '');
            if ($text === '')
                return '';
            return "<{$level} class='{$commonClass}'>{$text}</{$level}>";

        case 'paragraph':
            $text = nl2br($pathFixer($data['text'] ?? ''));
            if ($text === '')
                return '';
            return "<p class='{$commonClass}'>{$text}</p>";

        case 'list':
            $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
            $items = $data['items'] ?? [];
            if (empty($items))
                return '';
            $html = "<{$style} class='{$commonClass}'>";
            foreach ($items as $item)
                $html .= "<li>" . $pathFixer($item) . "</li>";
            $html .= "</{$style}>";
            return $html;

        case 'image':
            $url = $data['url'] ?? '';
            $caption = h($data['caption'] ?? '');
            if (!$url)
                return '';
            $html = "<figure class='{$commonClass}'>" . get_image_html($url, ['alt' => $caption, 'loading' => 'lazy']);
            if ($caption)
                $html .= "<figcaption>{$caption}</figcaption>";
            $html .= "</figure>";
            return $html;

        case 'quote':
            $text = nl2br($pathFixer($data['text'] ?? ''));
            if ($text === '')
                return '';
            $cite = h($data['cite'] ?? '');
            $html = "<blockquote class='{$commonClass}'><p>{$text}</p>";
            if ($cite)
                $html .= "<footer>— <cite>{$cite}</cite></footer>";
            $html .= "</blockquote>";
            return $html;

        case 'code':
            $code = h($data['code'] ?? '');
            if ($code === '')
                return '';
            return "<pre class='{$commonClass}'><code>{$code}</code></pre>";

        case 'table':
            $content = $data['content'] ?? [];
            $withHeadings = !empty($data['withHeadings']);
            if (empty($content))
                return '';
            $html = "<div class='{$commonClass}'><table>";
            foreach ($content as $i => $row) {
                if ($withHeadings && $i === 0) {
                    $html .= "<thead><tr>";
                    foreach ($row as $cell)
                        $html .= "<th>" . nl2br($pathFixer($cell)) . "</th>";
                    $html .= "</tr></thead><tbody>";
                } else {
                    if (!$withHeadings && $i === 0)
                        $html .= "<tbody>";
                    $html .= "<tr>";
                    foreach ($row as $cell)
                        $html .= "<td>" . nl2br($pathFixer($cell)) . "</td>";
                    $html .= "</tr>";
                }
            }
            $html .= "</tbody></table></div>";
            return $html;

        case 'divider':
            return "<hr class='{$commonClass}'>";

        case 'button':
            $text = h($data['text'] ?? 'Button');
            $rawUrl = $data['url'] ?? '';
            if (empty($rawUrl) || $rawUrl === '#')
                return '';
            $url = resolve_url($rawUrl);
            $target = !empty($data['external']) ? 'target="_blank" rel="noopener"' : '';
            return "<div class='{$commonClass}'><a href='{$url}' {$target} class='btn'>{$text}</a></div>";

        case 'columns':
            $left = nl2br($pathFixer($data['leftText'] ?? ''));
            $right = nl2br($pathFixer($data['rightText'] ?? ''));
            if ($left === '' && $right === '')
                return '';
            return "<div class='{$commonClass}'><div class='col'>{$left}</div><div class='col'>{$right}</div></div>";

        case 'callout':
            $text = nl2br($pathFixer($data['text'] ?? ''));
            if ($text === '')
                return '';
            $style = h($data['style'] ?? 'info');
            return "<div class='{$commonClass} callout-{$style}'>{$text}</div>";

        case 'section':
            $text = nl2br($pathFixer($data['text'] ?? ''));
            if ($text === '')
                return '';
            return "<div class='{$commonClass}'>{$text}</div>";

        case 'accordion':
            $items = $data['items'] ?? [];
            if (empty($items))
                return '';
            $html = "<div class='{$commonClass}'>";
            foreach ($items as $item) {
                $html .= "<details><summary>" . h($item['title']) . "</summary><div>" . nl2br($pathFixer($item['content'])) . "</div></details>";
            }
            $html .= "</div>";
            return $html;

        case 'gallery':
            $images = $data['images'] ?? [];
            if (empty($images))
                return '';
            $html = "<div class='{$commonClass}'>";
            foreach ($images as $img) {
                if (empty($img['url']))
                    continue;
                $html .= "<figure>" . get_image_html($img['url'], ['loading' => 'lazy']);
                if (!empty($img['caption']))
                    $html .= "<figcaption>" . h($img['caption']) . "</figcaption>";
                $html .= "</figure>";
            }
            $html .= "</div>";
            return $html;

        case 'card':
            $url = resolve_url($data['url'] ?? '#');
            $title = h($data['title'] ?? '');
            $desc = h($data['description'] ?? '');
            $img = $data['image'] ?? '';
            if (empty($title) && empty($desc) && empty($img))
                return '';
            $html = "<div class='{$commonClass}'><a href='{$url}'>";
            if ($img)
                $html .= get_image_html($img, ['alt' => $title, 'loading' => 'lazy']);
            if ($title)
                $html .= "<h3>{$title}</h3>";
            if ($desc)
                $html .= "<p>{$desc}</p>";
            $html .= "</a></div>";
            return $html;

        case 'embed':
            $url = h($data['url'] ?? '');
            if ($url === '')
                return '';

            $embedHtml = "<a href='{$url}' target='_blank'>{$url}</a>";

            if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $url, $matches)) {
                $embedId = h($matches[1] . '/' . $matches[2]);
                $embedHtml = "<div style='position:relative;width:100%;height:0;padding-bottom:56.25%;max-width:800px;margin:0 auto;box-shadow:0 4px 6px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden;'><iframe src='https://www.canva.com/design/{$embedId}/view?embed' style='position:absolute;top:0;left:0;width:100%;height:100%;border:0;' allowfullscreen loading='lazy'></iframe></div>";
            } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
                $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($url);
                $embedHtml = "<div style='position:relative;width:100%;height:0;padding-bottom:56.25%;max-width:800px;margin:0 auto;box-shadow:0 4px 6px rgba(0,0,0,0.1);border-radius:8px;overflow:hidden;'><iframe src='" . h($embedUrl) . "' style='position:absolute;top:0;left:0;width:100%;height:100%;border:0;' allowfullscreen loading='lazy'></iframe></div>";
            } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
                $vid = h($matches[1]);
                $embedHtml = "<div style='position:relative;width:100%;height:0;padding-bottom:56.25%;max-width:800px;margin:0 auto;background:#000;border-radius:8px;overflow:hidden;'><iframe src='https://www.youtube-nocookie.com/embed/{$vid}' style='position:absolute;top:0;left:0;width:100%;height:100%;border:0;' allowfullscreen loading='lazy'></iframe></div>";
            }

            return "<div class='{$commonClass}' style='text-align:center;'>{$embedHtml}</div>";

        case 'map':
            $code = $data['code'] ?? '';
            if (preg_match('/<iframe\s+[^>]*src=["\']([^"\']+)["\']/i', $code, $matches)) {
                return "<div class='{$commonClass}'><iframe src='" . htmlspecialchars($matches[1], ENT_QUOTES) . "' width='100%' height='450' style='border:0' allowfullscreen loading='lazy'></iframe></div>";
            }
            return '';

        case 'html':
            $code = $data['code'] ?? '';
            if ($code === '')
                return '';
            return "<div class='{$commonClass}'>" . ($data['code'] ?? '') . "</div>";

        case 'spacer':
            $height = (int)($data['height'] ?? 50);
            return "<div style='height:{$height}px' aria-hidden='true'></div>";

        case 'download':
            $title = h($data['title'] ?? theme_t('Download'));
            $rawUrl = $data['url'] ?? '';
            if (empty($rawUrl) || $rawUrl === '#')
                return '';
            $url = resolve_url($rawUrl);
            return "<div class='{$commonClass}'><a href='{$url}' download>⬇️ {$title}</a></div>";

        case 'timeline':
            $items = $data['items'] ?? [];
            if (empty($items))
                return '';
            $html = "<div class='{$commonClass}'>";
            foreach ($items as $item) {
                $html .= "<div class='timeline-item'><strong>" . h($item['date']) . "</strong> <h4>" . h($item['title']) . "</h4><p>" . nl2br($pathFixer($item['content'])) . "</p></div>";
            }
            $html .= "</div>";
            return $html;

        case 'step':
            $items = $data['items'] ?? [];
            if (empty($items))
                return '';
            $html = "<div class='{$commonClass}'>";
            foreach ($items as $i => $item) {
                $html .= "<div class='step-item'><strong>" . ($i + 1) . ". " . h($item['title']) . "</strong><p>" . nl2br($pathFixer($item['desc'])) . "</p></div>";
            }
            $html .= "</div>";
            return $html;

        case 'price':
            $items = $data['items'] ?? [];
            if (empty($items))
                return '';
            $html = "<div class='{$commonClass}'>";
            foreach ($items as $item) {
                $html .= "<div class='price-item'><h3>" . h($item['plan']) . "</h3><div class='price'>" . h($item['price']) . "</div><p>" . nl2br(h($item['features'])) . "</p></div>";
            }
            $html .= "</div>";
            return $html;

        case 'testimonial':
            $name = h($data['name'] ?? '');
            $comment = nl2br($pathFixer($data['comment'] ?? ''));
            if ($comment === '')
                return '';
            return "<div class='{$commonClass}'><blockquote>{$comment}</blockquote><cite>{$name}</cite></div>";

        case 'audio':
            $url = resolve_url($data['url'] ?? '');
            if (!$url)
                return '';
            return "<div class='{$commonClass}'><audio controls src='{$url}'></audio></div>";

        case 'pdf':
            $url = resolve_url($data['url'] ?? '');
            if (!$url)
                return '';
            return "<div class='{$commonClass}'><a href='{$url}' aria-label='" . theme_t('Download PDF') . "'>" . theme_t('Download PDF') . "</a></div>";

        case 'search_box':
            return "<div class='{$commonClass}'><form action='" . resolve_url('/') . "' method='get' class='grinds-search-form'><input type='text' name='q' placeholder='" . theme_t('Search...') . "'><button type='submit' aria-label='" . theme_t('Search') . "'><svg width='16' height='16' fill='currentColor' viewBox='0 0 16 16'><path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/></svg></button></form></div>";

        case 'internal_card':
            $id = $data['id'] ?? '';
            if ($id)
                return "<div class='{$commonClass}'><a href='?p={$id}'>View Post (ID: {$id})</a></div>";
            return '';

        case 'conversation':
            $name = h($data['name'] ?? '');
            $img = $data['image'] ?? '';
            $text = nl2br($pathFixer($data['text'] ?? ''));
            if ($text === '')
                return '';
            $html = "<div class='{$commonClass}'>";
            $html .= "<div class='conversation-avatar'>";
            if ($img)
                $html .= "<img src='" . resolve_url($img) . "' alt='{$name}'>";
            else
                $html .= "<span>👤</span>";
            $html .= "</div><div class='conversation-body'><strong>{$name}</strong><p>{$text}</p></div></div>";
            return $html;

        case 'proscons':
            $pTitle = h($data['pros_title'] ?? theme_t('Pros'));
            $cTitle = h($data['cons_title'] ?? theme_t('Cons'));
            $pItems = $data['pros_items'] ?? [];
            $cItems = $data['cons_items'] ?? [];
            if (empty($pItems) && empty($cItems))
                return '';
            $html = "<div class='{$commonClass}'><div class='pros'><h4>{$pTitle}</h4><ul>";
            foreach ($pItems as $item)
                if ($item)
                    $html .= "<li>" . h($item) . "</li>";
            $html .= "</ul></div><div class='cons'><h4>{$cTitle}</h4><ul>";
            foreach ($cItems as $item)
                if ($item)
                    $html .= "<li>" . h($item) . "</li>";
            $html .= "</ul></div></div>";
            return $html;

        case 'carousel':
            $images = $data['images'] ?? [];
            if (empty($images))
                return '';
            $id = 'carousel-' . uniqid();
            $html = "<div id='{$id}' class='{$commonClass} grinds-carousel'>";
            $html .= "<div class='carousel-inner'>";
            foreach ($images as $i => $img) {
                $src = $img['url'] ?? '';
                if (!$src)
                    continue;
                $activeClass = ($i === 0) ? 'active' : '';
                $html .= "<div class='carousel-item {$activeClass}'>";
                $html .= get_image_html($src, ['alt' => h($img['caption'] ?? ''), 'loading' => ($i === 0 ? 'eager' : 'lazy')]);
                if (!empty($img['caption']))
                    $html .= "<div class='carousel-caption'>" . h($img['caption']) . "</div>";
                $html .= "</div>";
            }
            $html .= "</div>";

            if (count($images) > 1) {
                $html .= "<button class='carousel-control prev' aria-label='Previous'>&lt;</button>";
                $html .= "<button class='carousel-control next' aria-label='Next'>&gt;</button>";
                $html .= "<script>(function () {
                        var c = document.getElementById('$id'), items = c.querySelectorAll('.carousel-item'), idx = 0;
                        function show(n) { items[idx].classList.remove('active'); idx = (n + items.length) % items.length; items[idx].classList.add('active'); }
                        c.querySelector('.prev').addEventListener('click', function () { show(idx - 1) });
                        c.querySelector('.next').addEventListener('click', function () { show(idx + 1) });
                    })();</script>";
            }
            $html .= "</div>";
            return $html;

        case 'rating':
            $score = (float)($data['score'] ?? 5);
            $max = (int)($data['max'] ?? 5);
            $html = "<div class='{$commonClass}'><div class='stars'>";
            for ($i = 1; $i <= $max; $i++)
                $html .= ($i <= round($score)) ? '★' : '☆';
            $html .= "</div><span class='score'>{$score} / {$max}</span></div>";
            return $html;

        case 'countdown':
            $deadline = h($data['deadline'] ?? '');
            $msg = h($data['message'] ?? theme_t('Finished'));
            $uid = 'timer-' . uniqid();
            $html = "<div id='{$uid}' class='{$commonClass}'><div class='label'>" . theme_t('Time Remaining') . "</div><div class='timer-display'>00:00:00:00</div></div>";
            $html .= "<script>(function () { const end = new Date('{$deadline}').getTime(); const el = document.querySelector('#{$uid} .timer-display'); const timer = setInterval(() => { const now = new Date().getTime(); const dist = end - now; if (dist < 0) { clearInterval(timer); el.innerHTML = '{$msg}'; return; } const d = Math.floor(dist / (1000 * 60 * 60 * 24)); const h = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60)); const m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60)); const s = Math.floor((dist % (1000 * 60)) / 1000); el.innerText = d + 'd ' + h.toString().padStart(2, '0') + 'h ' + m.toString().padStart(2, '0') + 'm ' + s.toString().padStart(2, '0') + 's'; }, 1000); })();</script>";
            return $html;

        case 'qrcode':
            $url = $data['url'] ?? '';
            $size = (int)($data['size'] ?? 150);
            if ($url) {
                $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
                return "<div class='{$commonClass}'><img src='{$qrSrc}' alt='QR Code' width='{$size}' height='{$size}'></div>";
            }
            return '';
    }

    return null;
}

/**
 * Define classic theme helpers.
 */

// Global iterator state.
global $grinds_query;
$grinds_query = [
    'posts' => [],
    'current_post' => -1,
    'post_count' => 0,
];

if (!function_exists('have_posts')) {
    function have_posts()
    {
        global $grinds_query, $pageData;
        if (!is_array($grinds_query)) {
            $grinds_query = ['posts' => [], 'current_post' => -1, 'post_count' => 0];
        }
        if (empty($grinds_query['posts']) && !empty($pageData['posts'])) {
            $grinds_query['posts'] = $pageData['posts'];
            $grinds_query['post_count'] = count($pageData['posts']);
        }
        return ($grinds_query['current_post'] + 1 < $grinds_query['post_count']);
    }
}

if (!function_exists('the_post')) {
    function the_post()
    {
        global $grinds_query, $post;
        $grinds_query['current_post']++;
        $post = $grinds_query['posts'][$grinds_query['current_post']];
        $GLOBALS['post'] = $post;
        return $post;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($slug = null)
    {
        if ($slug === null) {
            global $post;
            $slug = $post['slug'] ?? '';
        }
        $url = site_url($slug);
        if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG && $slug !== '' && $slug !== 'index') {
            $url .= '.html';
        }
        return $url;
    }
}

if (!function_exists('the_title')) {
    function the_title()
    {
        global $post;
        echo h($post['title'] ?? '');
    }
}

if (!function_exists('the_time')) {
    function the_time($format = 'Y.m.d')
    {
        global $post;
        $date = $post['published_at'] ?? $post['created_at'] ?? '';
        echo $date ? date($format, strtotime($date)) : '';
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date($format = 'Y.m.d')
    {
        global $post;
        $date = $post['published_at'] ?? $post['created_at'] ?? '';
        return $date ? date($format, strtotime($date)) : '';
    }
}

if (!function_exists('the_category')) {
    function the_category()
    {
        global $post;
        if (!empty($post['category_name'])) {
            echo h($post['category_name']);
        } else {
            echo theme_t('Uncategorized');
        }
    }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail()
    {
        global $post;
        return !empty($post['thumbnail']);
    }
}

if (!function_exists('the_post_thumbnail')) {
    function the_post_thumbnail()
    {
        global $post, $grinds_query;
        if (!empty($post['thumbnail'])) {
            $imgAttrs = ['alt' => h($post['title'] ?? '')];
            if (isset($grinds_query['current_post']) && $grinds_query['current_post'] < 2) {
                $imgAttrs['loading'] = 'eager';
                $imgAttrs['fetchpriority'] = 'high';
            } else {
                $imgAttrs['loading'] = 'lazy';
            }
            echo get_image_html($post['thumbnail'], $imgAttrs);
        }
    }
}

if (!function_exists('the_pagination')) {
    function the_pagination()
    {
        global $pageData;
        if (isset($pageData['paginator'])) {
            $paginator = $pageData['paginator'];
            include __DIR__ . '/parts/pagination.php';
        }
    }
}

if (!function_exists('the_content')) {
    function the_content()
    {
        global $pageData;
        echo render_content($pageData['post']['content'] ?? '');
    }
}

if (!function_exists('the_tags')) {
    function the_tags($before = '', $sep = ', ', $after = '')
    {
        global $pageData;
        if (!empty($pageData['tags']) && is_array($pageData['tags'])) {
            $links = [];
            foreach ($pageData['tags'] as $tag) {
                $name = h($tag['name']);
                $slug = h($tag['slug']);
                $url = site_url('tag/' . $slug);
                $links[] = '<a href="' . h($url) . '" rel="tag">' . $name . '</a>';
            }
            echo $before . implode($sep, $links) . $after;
        }
    }
}

/**
 * Define additional layout helpers.
 */

if (!function_exists('is_home')) {
    function is_home()
    {
        global $pageType;
        return ($pageType === 'home');
    }
}

if (!function_exists('body_class')) {
    function body_class($class = '')
    {
        global $pageType;
        $classes = [$class];
        $classes[] = 'page-type-' . ($pageType ?? 'unknown');
        if (is_home())
            $classes[] = 'home';
        echo 'class="' . implode(' ', array_filter($classes)) . '"';
    }
}

if (!function_exists('wp_nav_menu')) {
    function wp_nav_menu($args = [])
    {
        $location = $args['theme_location'] ?? 'header';
        $menus = function_exists('get_nav_menus') ? get_nav_menus($location) : [];

        if (empty($menus))
            return;

        echo '<ul>';
        foreach ($menus as $menu) {
            $target = $menu['is_external'] ? 'target="_blank" rel="noopener"' : '';
            $activeClass = grinds_is_menu_active($menu['url']) ? 'class="current-menu-item"' : '';
            echo '<li ' . $activeClass . '><a href="' . h($menu['url']) . '" ' . $target . '>' . h($menu['label']) . '</a></li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('dynamic_sidebar')) {
    function dynamic_sidebar($index = 1)
    {
        if (function_exists('get_sidebar_widgets')) {
            $widgets = get_sidebar_widgets();
            foreach ($widgets as $widget) {
                render_widget($widget);
            }
        }
    }
}

if (!function_exists('classic_get_breadcrumb_html')) {
    function classic_get_breadcrumb_html()
    {
        global $pageType, $pageData;

        $crumbs = [['label' => theme_t('Home'), 'url' => site_url()]];

        if ($pageType === 'category') {
            $crumbs[] = ['label' => $pageData['category']['name'], 'url' => ''];
        } elseif ($pageType === 'tag') {
            $crumbs[] = ['label' => '#' . $pageData['tag']['name'], 'url' => ''];
        } elseif ($pageType === 'search') {
            $crumbs[] = ['label' => 'Search: ' . htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8'), 'url' => ''];
        } elseif ($pageType === 'single') {
            if (!empty($pageData['post']['category_id'])) {
                $catName = $pageData['post']['category_name'] ?? theme_t('Category');
                $catSlug = $pageData['post']['category_slug'] ?? '';
                $catPath = 'category/' . $catSlug;
                if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
                    $catPath .= '.html';
                }
                $catUrl = site_url($catPath);
                $crumbs[] = ['label' => $catName, 'url' => $catUrl];
            }
            $crumbs[] = ['label' => $pageData['post']['title'], 'url' => ''];
        } elseif ($pageType === 'page') {
            $crumbs[] = ['label' => $pageData['post']['title'], 'url' => ''];
        } elseif ($pageType === '404') {
            $crumbs[] = ['label' => theme_t('404 Not Found'), 'url' => ''];
        }

        $html = '<nav class="breadcrumbs" aria-label="Breadcrumb"><ul style="display: flex; flex-wrap: wrap; list-style: none; padding: 0; margin: 0; gap: 0.5rem; align-items: center;">';
        foreach ($crumbs as $i => $crumb) {
            $html .= '<li>';
            if (!empty($crumb['url'])) {
                $html .= '<a href="' . h($crumb['url']) . '">' . h($crumb['label']) . '</a>';
            } else {
                $html .= '<span>' . h($crumb['label']) . '</span>';
            }
            $html .= '</li>';
            if ($i < count($crumbs) - 1) {
                $html .= '<li class="separator">/</li>';
            }
        }
        $html .= '</ul></nav>';
        return $html;
    }
}

/**
 * Filter widget output.
 */
add_filter('grinds_widget_output', function ($html, $widget) {
    // Replace search widget.
    if ($widget['type'] === 'search') {
        $action = resolve_url('/');
        $q = isset($_GET['q']) ? h($_GET['q']) : '';
        $ph = theme_t('Search...');
        $label = theme_t('Search');
        $icon = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:middle;"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>';

        $titleHtml = '';
        if (!empty($widget['title'])) {
            $titleHtml = '<h3 class="widget-title">' . h($widget['title']) . '</h3>';
        }

        return <<<HTML
<div class="widget widget-search">
    {$titleHtml}
    <form action="{$action}" method="get" class="grinds-search-form">
        <input type="text" name="q" placeholder="{$ph}" value="{$q}">
        <button type="submit" aria-label="{$label}">{$icon}</button>
    </form>
</div>
HTML;
    }

    // Clean up Tailwind classes.
    // Remove Tailwind-specific classes.
    $html = str_replace([
        'space-y-2',
        'space-y-4',
        'flex',
        'justify-between',
        'items-center',
        'group',
        'hover:text-grinds-red',
        'transition',
        'text-sm',
        'text-gray-500',
        'text-xs'
    ], '', $html);

    // Add widget title class.
    $html = str_replace('<h3>', '<h3 class="widget-title">', $html);

    // Ensure widget container class.
    if (preg_match('/^<div[^>]*class=["\'][^"\']*widget-([a-zA-Z0-9_-]+)[^"\']*["\'][^>]*>/i', $html, $matches)) {
        $type = $matches[1];
        $html = preg_replace('/^<div[^>]*>/i', '<div class="widget widget-' . $type . '">', $html);
    }

    // Add list styling class.
    if (strpos($html, '<ul') !== false) {
        $html = str_replace('<ul', '<ul class="widget-list"', $html);
    }

    return $html;
});
