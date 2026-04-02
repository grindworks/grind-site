<?php

/**
 * functions.php
 * Define theme helper functions.
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Translate theme string.
 */
// function theme_t removed (centralized in core)

/**
 * Transform widget output to Bootstrap.
 */
if (!function_exists('bootstrap_transform_widget_output')) {
  function bootstrap_transform_widget_output($html)
  {
    // Convert generic widget output to Bootstrap Card
    // Input: <div class="... widget-type ...">...</div>
    // Output: <div class="shadow-sm mb-4 card widget-type">...</div>

    // Force max-width.
    $html = preg_replace('/<(img|iframe|video)([^>]*)>/i', '<$1$2 style="max-width: 100%; height: auto;">', $html);

    $html = str_replace('class="space-y-2"', 'class="list-unstyled mb-0"', $html);
    $html = str_replace('class="space-y-4"', 'class="list-unstyled mb-0"', $html);
    $html = str_replace('class="flex gap-3"', 'class="d-flex gap-3 mb-3"', $html);
    $html = str_replace('group flex justify-between', 'd-flex justify-content-between text-decoration-none text-dark', $html);
    $html = str_replace('class="flex flex-wrap gap-2"', 'class="d-flex flex-wrap gap-2"', $html);

    // Style search form.
    // Check for search input.
    if (strpos($html, 'name="q"') !== false || strpos($html, "name='q'") !== false || strpos($html, 'type="search"') !== false || strpos($html, 'search-form') !== false) {
      // Extract form action.
      $action = '';
      if (preg_match('/<form\b[^>]*action=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
        $action = $matches[1];
      }
      // Extract hidden inputs.
      $hiddenFields = '';
      if (preg_match_all('/<input\b[^>]*type=["\']hidden["\'][^>]*>/i', $html, $hiddenMatches)) {
        $hiddenFields = implode('', $hiddenMatches[0]);
      }
      // Rebuild form.
      $newForm = '<form action="' . $action . '" method="get" class="input-group grinds-search-form">';
      $newForm .= $hiddenFields;
      $newForm .= '<input type="text" name="q" class="form-control" placeholder="' . theme_t('Search...') . '">';
      $newForm .= '<button type="submit" class="btn btn-danger" aria-label="' . theme_t('Search') . '"><i class="bi bi-search"></i></button>';
      $newForm .= '</form>';

      // Replace form.
      $replaced = preg_replace('/<form\b[^>]*>.*?<\/form>/is', $newForm, $html);

      if ($replaced !== null && $replaced !== $html) {
        $html = $replaced;
      } else {
        // Fallback replacement.
        $html = preg_replace('/class="[^"]*grinds-search-form[^"]*"/', 'class="input-group grinds-search-form"', $html);
        $html = preg_replace('/<input[^>]*name="q"[^>]*>/', '<input type="text" name="q" class="form-control" placeholder="' . theme_t('Search...') . '">', $html);
        // Replace submit button.
        $html = preg_replace('/(<button\b[^>]*>.*?<\/button>|<input\b[^>]*type=["\'](?:submit|image)["\'][^>]*>)/is', '<button type="submit" class="btn btn-danger" aria-label="' . theme_t('Search') . '"><i class="bi bi-search"></i></button>', $html);
      }
    }

    // Style tags.
    if (strpos($html, 'tag-cloud') !== false) {
      $html = str_replace('class="tag-link"', 'class="badge bg-secondary text-decoration-none me-1"', $html);
    }

    // Style profile widget.
    if (strpos($html, 'widget-profile') !== false || strpos($html, 'profile-image') !== false) {
      $html = str_replace('class="profile-image"', 'class="text-center mb-4"', $html);

      // Style image.
      $html = preg_replace(
        '/<img\s+([^>]*?)src="([^"]+)"([^>]*?)>/i',
        '<img src="$2" $1 $3 class="rounded-circle shadow-sm border" style="width: 128px; height: 128px; object-fit: cover;">',
        $html
      );

      $html = str_replace('class="profile-text"', 'class="text-center text-muted"', $html);

      // Remove Tailwind classes.
      $html = str_replace(['flex', 'items-center', 'justify-center', 'bg-gray-100', 'rounded-full', 'w-12', 'h-12', 'mr-3'], '', $html);
    }

    // Transform container.

    $count = 0;

    // Replace main container.
    $html = preg_replace_callback(
      '/<div\s+[^>]*class=["\'][^"\']*\bwidget-([a-zA-Z0-9_-]+)[^"\']*["\'][^>]*>/i',
      function ($matches) {
        return '<div class="card mb-4 border-0 shadow-sm widget-' . $matches[1] . '">';
      },
      $html,
      -1,
      $count
    );

    // Return if no match.
    if ($count === 0) return $html;

    // Handle title and content.
    if (preg_match('/<h3[^>]*>(.*?)<\/h3>/s', $html, $matches)) {
      $titleInner = $matches[1];
      // Remove decorative span.
      $titleInner = preg_replace('/<span class="[^"]*"><\/span>/', '', $titleInner);

      $header = '<div class="card-header fw-bold bg-white border-0">' . trim($titleInner) . '</div>';
      // Replace header.
      $html = preg_replace('/<h3[^>]*>.*?<\/h3>/s', $header . '<div class="card-body">', $html);
    } else {
      // Inject card body.
      $html = preg_replace('/(<div class="card[^>]*>)/', '$1<div class="card-body">', $html);
    }

    // Close card body.
    // Trim whitespace.
    $html = trim($html);
    $html = preg_replace('/<\/div>$/', '</div></div>', $html);

    return $html;
  }
}

// Filter widget output.
add_filter('grinds_widget_output', function ($html, $widget) {
  return bootstrap_transform_widget_output($html);
}, 10, 2);

/**
 * Get highlighted excerpt for search results.
 */
if (!function_exists('bootstrap_get_highlighted_excerpt')) {
  function bootstrap_get_highlighted_excerpt($post, $length = 80)
  {
    $excerpt = (!empty($post['description'])) ? h($post['description']) : get_excerpt($post['content'], $length);
    if (isset($_GET['q']) && $_GET['q'] !== '') {
      $q = trim($_GET['q']);
      $plain = strip_tags($post['content']);
      $pos = mb_stripos($plain, $q, 0, 'UTF-8');
      if ($pos !== false) {
        $start = max(0, $pos - ($length / 2));
        $sub = mb_substr($plain, $start, $length, 'UTF-8');
        $marker = "[[MARK_" . bin2hex(random_bytes(8)) . "_START]]";
        $endMarker = "[[MARK_" . bin2hex(random_bytes(8)) . "_END]]";
        $subWithPlaceholders = preg_replace('/(' . preg_quote($q, '/') . ')/iu', $marker . '$1' . $endMarker, $sub);
        $escaped = h($subWithPlaceholders);
        $final = str_replace([$marker, $endMarker], ['<mark class="bg-warning p-0 text-dark">', '</mark>'], $escaped);
        $excerpt = '...' . $final . '...';
      }
    }
    return $excerpt;
  }
}

/**
 * Render content blocks.
 */
if (!function_exists('bootstrap_render_block')) {
  function bootstrap_render_block($block, $pathFixer)
  {
    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];

    switch ($type) {
      case 'header':
        $level = strtolower($data['level'] ?? 'h2');
        if (!preg_match('/^h[2-6]$/', $level)) $level = 'h2';
        $text = h($data['text'] ?? '');
        if ($text === '') return '';
        return "<{$level} class='mt-4 mb-3 fw-bold'>{$text}</{$level}>";

      case 'paragraph':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        return "<p class='mb-3'>{$text}</p>";

      case 'list':
        $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<{$style} class='mb-3'>";
        foreach ($items as $item) {
          $html .= "<li>" . $pathFixer($item) . "</li>";
        }
        $html .= "</{$style}>";
        return $html;

      case 'table':
        $content = $data['content'] ?? [];
        if (empty($content)) return '';
        $withHeadings = !empty($data['withHeadings']);
        $html = "<div class='table-responsive mb-3'><table class='table table-bordered table-hover'>";
        foreach ($content as $rowIndex => $row) {
          if ($withHeadings && $rowIndex === 0) {
            $html .= "<thead class='table-light'><tr>";
            foreach ($row as $cell) $html .= "<th>" . nl2br($pathFixer($cell)) . "</th>";
            $html .= "</tr></thead><tbody>";
          } else {
            if (!$withHeadings && $rowIndex === 0) $html .= "<tbody>";
            $html .= "<tr>";
            foreach ($row as $cell) $html .= "<td>" . nl2br($pathFixer($cell)) . "</td>";
            $html .= "</tr>";
          }
        }
        $html .= "</tbody></table></div>";
        return $html;

      case 'divider':
        return "<hr class='my-4'>";

      case 'code':
        $code = h($data['code'] ?? '');
        if ($code === '') return '';
        return "<pre class='bg-light mb-3 p-3 border rounded overflow-auto'><code>{$code}</code></pre>";

      case 'quote':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $cite = h($data['cite'] ?? '');
        $html = "<blockquote class='bg-light my-4 p-3 border-4 border-primary border-start blockquote'>";
        $html .= "<p class='mb-0'>{$text}</p>";
        if ($cite) $html .= "<footer class='mt-2 blockquote-footer'>{$cite}</footer>";
        $html .= "</blockquote>";
        return $html;

      case 'callout':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $style = $data['style'] ?? 'info';
        $alertClass = 'alert-info';
        if ($style === 'warning') $alertClass = 'alert-warning';
        if ($style === 'success') $alertClass = 'alert-success';
        if ($style === 'danger') $alertClass = 'alert-danger';
        return "<div class='alert {$alertClass} my-4' role='alert'>{$text}</div>";

      case 'button':
        $text = h($data['text'] ?? 'Button');
        $rawUrl = $data['url'] ?? '';
        if (empty($rawUrl) || $rawUrl === '#') return '';
        $url = resolve_url($rawUrl);
        $color = $data['color'] ?? 'primary';
        $target = !empty($data['external']) ? 'target="_blank" rel="noopener"' : '';

        $btnClass = 'btn btn-primary';
        if ($color === 'success') $btnClass = 'btn btn-success';
        if ($color === 'danger') $btnClass = 'btn btn-danger';
        if ($color === 'dark') $btnClass = 'btn btn-dark';

        return "<div class='my-4 text-center'><a href='{$url}' {$target} class='{$btnClass}'>{$text}</a></div>";

      case 'image':
        $url = $data['url'] ?? '';
        $caption = h($data['caption'] ?? '');
        if (!$url) return '';

        $html = "<figure class='my-4 w-100 figure'>";
        $html .= get_image_html($url, ['class' => 'rounded figure-img img-fluid', 'alt' => $caption, 'loading' => 'lazy']);
        if ($caption) $html .= "<figcaption class='text-center figure-caption'>{$caption}</figcaption>";
        $html .= "</figure>";
        return $html;

      case 'columns':
        $left = nl2br($pathFixer($data['leftText'] ?? ''));
        $right = nl2br($pathFixer($data['rightText'] ?? ''));
        if ($left === '' && $right === '') return '';
        $ratio = $data['ratio'] ?? '1-1';

        $leftCol = 'col-md-6';
        $rightCol = 'col-md-6';

        if ($ratio === '1-2') {
          $leftCol = 'col-md-4';
          $rightCol = 'col-md-8';
        }
        if ($ratio === '2-1') {
          $leftCol = 'col-md-8';
          $rightCol = 'col-md-4';
        }

        return "<div class='my-4 row'><div class='{$leftCol}'>{$left}</div><div class='{$rightCol}'>{$right}</div></div>";

      case 'gallery':
        $images = $data['images'] ?? [];
        if (empty($images)) return '';
        $cols = (int)($data['columns'] ?? 3);
        if ($cols < 1) $cols = 3;
        $html = "<div class='row row-cols-1 row-cols-md-{$cols} g-4 my-4'>";
        foreach ($images as $img) {
          $src = $img['url'] ?? '';
          $caption = h($img['caption'] ?? '');
          if ($src) {
            $html .= "<div class='col'>";
            $html .= "<figure class='mb-0 w-100 figure'>";
            $html .= get_image_html($src, ['class' => 'shadow-sm rounded w-100 figure-img img-fluid', 'alt' => $caption, 'loading' => 'lazy']);
            if ($caption) $html .= "<figcaption class='text-center figure-caption'>{$caption}</figcaption>";
            $html .= "</figure></div>";
          }
        }
        $html .= "</div>";
        return $html;

      case 'carousel':
        $images = $data['images'] ?? [];
        if (empty($images)) return '';
        $id = 'carousel-' . uniqid();
        $html = "<div id='{$id}' class='my-4 carousel slide' data-bs-ride='carousel'>";
        $html .= "<div class='carousel-indicators'>";
        foreach ($images as $i => $img) {
          $active = ($i === 0) ? 'active' : '';
          $html .= "<button type='button' data-bs-target='#{$id}' data-bs-slide-to='{$i}' class='{$active}' aria-current='" . ($i === 0 ? 'true' : 'false') . "' aria-label='Slide " . ($i + 1) . "'></button>";
        }
        $html .= "</div><div class='bg-light shadow-sm rounded carousel-inner'>";
        foreach ($images as $i => $img) {
          $src = $img['url'] ?? '';
          $caption = h($img['caption'] ?? '');
          $active = ($i === 0) ? 'active' : '';
          if ($src) {
            $html .= "<div class='carousel-item {$active}'>";
            $html .= get_image_html($src, ['class' => 'd-block w-100', 'alt' => $caption, 'style' => 'max-height: 500px; object-fit: contain; margin: 0 auto;', 'loading' => ($i === 0 ? 'eager' : 'lazy')]);
            if ($caption) $html .= "<div class='d-md-block bg-dark bg-opacity-50 p-2 rounded carousel-caption d-none'>{$caption}</div>";
            $html .= "</div>";
          }
        }
        $html .= "</div>";
        $html .= "<button class='carousel-control-prev' type='button' data-bs-target='#{$id}' data-bs-slide='prev'><span class='carousel-control-prev-icon' aria-hidden='true'></span><span class='visually-hidden'>Previous</span></button>";
        $html .= "<button class='carousel-control-next' type='button' data-bs-target='#{$id}' data-bs-slide='next'><span class='carousel-control-next-icon' aria-hidden='true'></span><span class='visually-hidden'>Next</span></button>";
        $html .= "</div>";
        return $html;

      case 'card':
        $url = resolve_url($data['url'] ?? '#');
        $title = h($data['title'] ?? '');
        $desc = h($data['description'] ?? '');
        $img = $data['image'] ?? '';
        if (empty($title) && empty($desc) && empty($img)) return '';
        $target = ($url !== '#' && $url !== '') ? 'target="_blank" rel="noopener noreferrer"' : '';
        $html = "<a href='{$url}' {$target} class='text-reset text-decoration-none'>";
        $html .= "<div class='hover-shadow shadow-sm mb-4 transition card'>";
        $html .= "<div class='row g-0'>";
        if ($img) {
          $html .= "<div class='col-md-4'>" . get_image_html($img, ['class' => 'rounded-start h-100 object-fit-cover img-fluid', 'alt' => $title, 'loading' => 'lazy']) . "</div>";
          $html .= "<div class='col-md-8'>";
        } else {
          $html .= "<div class='col-12'>";
        }
        $html .= "<div class='card-body'>";
        if ($title) $html .= "<h5 class='card-title fw-bold'>{$title}</h5>";
        if ($desc) $html .= "<p class='text-muted card-text small'>{$desc}</p>";
        $host = parse_url($url, PHP_URL_HOST);
        $html .= "<p class='card-text'><small class='text-muted'><i class='bi bi-link-45deg'></i> {$host}</small></p>";
        $html .= "</div></div></div></div></a>";
        return $html;

      case 'accordion':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $id = 'accordion-' . uniqid();
        $html = "<div class='my-4 accordion' id='{$id}'>";
        foreach ($items as $i => $item) {
          $itemId = $id . '-item-' . $i;
          $headerId = $id . '-header-' . $i;
          $q = h($item['title'] ?? '');
          $a = nl2br($pathFixer($item['content'] ?? ''));
          $html .= "<div class='accordion-item'><h2 class='accordion-header' id='{$headerId}'>";
          $html .= "<button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#{$itemId}' aria-expanded='false' aria-controls='{$itemId}'>{$q}</button></h2>";
          $html .= "<div id='{$itemId}' class='accordion-collapse collapse' aria-labelledby='{$headerId}' data-bs-parent='#{$id}'><div class='accordion-body'>{$a}</div></div></div>";
        }
        $html .= "</div>";
        return $html;

      case 'section':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $bgColor = $data['bgColor'] ?? 'gray';
        $bgClass = 'bg-light';
        if ($bgColor === 'blue') $bgClass = 'bg-primary-subtle text-primary-emphasis';
        if ($bgColor === 'yellow') $bgClass = 'bg-warning-subtle text-warning-emphasis';
        if ($bgColor === 'red') $bgClass = 'bg-danger-subtle text-danger-emphasis';
        if ($bgColor === 'green') $bgClass = 'bg-success-subtle text-success-emphasis';
        return "<div class='p-4 rounded my-4 {$bgClass}'>{$text}</div>";

      case 'spacer':
        $height = (int)($data['height'] ?? 50);
        return "<div style='height:{$height}px' aria-hidden='true'></div>";

      case 'download':
        $title = h($data['title'] ?? theme_t('Download'));
        $rawUrl = $data['url'] ?? '';
        if (empty($rawUrl) || $rawUrl === '#') return '';
        $url = resolve_url($rawUrl);
        $size = h($data['fileSize'] ?? '');
        $html = "<a href='{$url}' class='hover-bg-light mb-4 text-reset text-decoration-none card' download>";
        $html .= "<div class='d-flex align-items-center card-body'><div class='me-3 fs-2'>⬇️</div>";
        $html .= "<div><h6 class='mb-0 card-title fw-bold'>{$title}</h6>";
        if ($size) $html .= "<small class='text-muted'>{$size}</small>";
        $html .= "</div></div></a>";
        return $html;

      case 'timeline':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='list-group my-4 border-0'>";
        foreach ($items as $item) {
          $date = h($item['date'] ?? '');
          $title = h($item['title'] ?? '');
          $content = nl2br($pathFixer($item['content'] ?? ''));
          $html .= "<div class='list-group-item mb-3 ps-4 border-0 border-3 border-primary border-start'>";
          if ($date) $html .= "<small class='text-muted fw-bold'>{$date}</small>";
          if ($title) $html .= "<h5 class='mb-1'>{$title}</h5>";
          $html .= "<p class='mb-0'>{$content}</p></div>";
        }
        $html .= "</div>";
        return $html;

      case 'step':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='my-4'>";
        foreach ($items as $i => $item) {
          $num = $i + 1;
          $title = h($item['title'] ?? '');
          $desc = nl2br($pathFixer($item['desc'] ?? ''));
          $html .= "<div class='d-flex mb-4'><div class='flex-shrink-0 me-3'>";
          $html .= "<span class='bg-primary rounded-pill badge fs-6' style='width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;'>{$num}</span>";
          $html .= "</div><div><h5 class='fw-bold'>{$title}</h5><p class='mb-0 text-muted'>{$desc}</p></div></div>";
        }
        $html .= "</div>";
        return $html;

      case 'price':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='my-4 mb-3 text-center row row-cols-1 row-cols-md-3'>";
        foreach ($items as $item) {
          $plan = h($item['plan'] ?? '');
          $price = h($item['price'] ?? '');
          $features = nl2br(h($item['features'] ?? ''));
          $isRec = !empty($item['recommend']);
          $borderClass = $isRec ? 'border-primary' : '';
          $headerClass = $isRec ? 'bg-primary text-white' : 'bg-light';
          $html .= "<div class='col'><div class='card mb-4 rounded-3 shadow-sm {$borderClass} h-100'>";
          $html .= "<div class='card-header py-3 {$headerClass}'><h4 class='my-0 fw-normal'>{$plan}</h4></div>";
          $html .= "<div class='d-flex flex-column card-body'><h1 class='card-title pricing-card-title'>{$price}</h1>";
          $html .= "<div class='flex-grow-1 mt-3 mb-4 text-start'>{$features}</div></div></div></div>";
        }
        $html .= "</div>";
        return $html;

      case 'testimonial':
        $name = h($data['name'] ?? '');
        $role = h($data['role'] ?? '');
        $comment = nl2br($pathFixer($data['comment'] ?? ''));
        if ($comment === '') return '';
        $img = $data['image'] ?? '';
        $html = "<div class='bg-light my-4 border-0 card'><div class='p-4 card-body'>";
        $html .= "<figure class='mb-0'><blockquote class='blockquote'><p class='fs-6'>{$comment}</p></blockquote>";
        $html .= "<figcaption class='d-flex align-items-center gap-2 mt-3 mb-0 blockquote-footer'>";
        if ($img) {
          $html .= get_image_html($img, ['class' => 'rounded-circle', 'width' => '40', 'height' => '40', 'alt' => $name, 'loading' => 'lazy']);
        }
        $html .= "<div><cite title='Source Title' class='fw-bold'>{$name}</cite>";
        if ($role) $html .= "<br><small>{$role}</small>";
        $html .= "</div></figcaption></figure></div></div>";
        return $html;

      case 'audio':
        $url = resolve_url($data['url'] ?? '');
        $title = h($data['title'] ?? '');
        if ($url) {
          $html = "<div class='bg-light my-4 card'><div class='card-body'>";
          if ($title) $html .= "<h6 class='mb-2 card-title'>{$title}</h6>";
          $html .= "<audio controls src='{$url}' class='w-100'></audio></div></div>";
          return $html;
        }
        break;

      case 'pdf':
        $url = resolve_url($data['url'] ?? '');
        if ($url) {
          $html = "<div class='my-4 border rounded ratio ratio-16x9'><object data='{$url}' type='application/pdf'>";
          $html .= "<p class='p-3 text-center'>" . theme_t('msg_pdf_error') . " <a href='{$url}' aria-label='" . theme_t('download_pdf_aria') . "'>" . theme_t('btn_download') . "</a></p></object></div>";
          return $html;
        }
        break;

      case 'search_box':
        $action = resolve_url('/');
        $ph = h($data['placeholder'] ?? theme_t('Search...'));
        $html = "<form action='{$action}' method='get' class='d-flex my-4 grinds-search-form'>";
        $html .= "<input type='text' name='q' class='me-2 form-control' placeholder='{$ph}'>";
        $html .= "<button class='btn btn-primary' type='submit'>Search</button></form>";
        return $html;

      case 'internal_card':
        $id = $data['id'] ?? '';
        if ($id) {
          $html = "<div class='my-4 text-center alert alert-secondary'>";
          $html .= "Internal Link (ID: {$id}) - <a href='?p={$id}' class='alert-link'>View Post (ID: {$id})</a></div>";
          return $html;
        }
        break;

      case 'conversation':
        $pos = ($data['position'] ?? 'left');
        $isRight = ($pos === 'right');
        $alignClass = $isRight ? 'justify-content-end' : 'justify-content-start';
        $bubbleClass = $isRight ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-dark';
        $name = h($data['name'] ?? '');
        $img = $data['image'] ?? '';
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $html = "<div class='d-flex {$alignClass} my-4 gap-3'>";
        if (!$isRight) {
          $html .= "<div class='flex-shrink-0 text-center'>";
          if ($img) $html .= get_image_html($img, ['class' => 'border rounded-circle', 'width' => '48', 'height' => '48', 'alt' => $name, 'loading' => 'lazy']);
          else $html .= "<div class='d-flex align-items-center justify-content-center bg-secondary rounded-circle text-white' style='width:48px;height:48px;'>👤</div>";
          if ($name) $html .= "<div class='mt-1 text-muted small'>{$name}</div>";
          $html .= "</div>";
        }
        $html .= "<div class='p-3 rounded-3 {$bubbleClass} shadow-sm' style='max-width: 80%;'>{$text}</div>";
        if ($isRight) {
          $html .= "<div class='flex-shrink-0 text-center'>";
          if ($img) $html .= get_image_html($img, ['class' => 'border rounded-circle', 'width' => '48', 'height' => '48', 'alt' => $name, 'loading' => 'lazy']);
          else $html .= "<div class='d-flex align-items-center justify-content-center bg-secondary rounded-circle text-white' style='width:48px;height:48px;'>👤</div>";
          if ($name) $html .= "<div class='mt-1 text-muted small'>{$name}</div>";
          $html .= "</div>";
        }
        $html .= "</div>";
        return $html;

      case 'proscons':
        $pTitle = h($data['pros_title'] ?? theme_t('Pros'));
        $cTitle = h($data['cons_title'] ?? theme_t('Cons'));
        $pItems = $data['pros_items'] ?? [];
        $cItems = $data['cons_items'] ?? [];
        if (empty($pItems) && empty($cItems)) return '';
        $html = "<div class='my-4 row g-4'>";
        $html .= "<div class='col-md-6'><div class='border-success h-100 card'><div class='bg-success-subtle text-success card-header fw-bold'>✔ {$pTitle}</div><ul class='list-group list-group-flush'>";
        foreach ($pItems as $item) if ($item) $html .= "<li class='list-group-item text-success-emphasis'>{$item}</li>";
        $html .= "</ul></div></div>";
        $html .= "<div class='col-md-6'><div class='border-danger h-100 card'><div class='bg-danger-subtle text-danger card-header fw-bold'>✖ {$cTitle}</div><ul class='list-group list-group-flush'>";
        foreach ($cItems as $item) if ($item) $html .= "<li class='list-group-item text-danger-emphasis'>{$item}</li>";
        $html .= "</ul></div></div></div>";
        return $html;

      case 'rating':
        $score = (float)($data['score'] ?? 5);
        $max = (int)($data['max'] ?? 5);
        $color = $data['color'] ?? 'gold';
        $colorClass = 'text-warning';
        if ($color === 'red') $colorClass = 'text-danger';
        if ($color === 'blue') $colorClass = 'text-primary';
        if ($color === 'green') $colorClass = 'text-success';
        $html = "<div class='d-inline-block shadow-sm my-4 card'><div class='d-flex align-items-center gap-3 card-body'>";
        $html .= "<div class='fs-4 {$colorClass}'>";
        for ($i = 1; $i <= $max; $i++) $html .= ($i <= round($score)) ? '★' : '☆';
        $html .= "</div><div class='fw-bold fs-5'>{$score} <small class='text-muted fw-normal'>/ {$max}</small></div></div></div>";
        return $html;

      case 'countdown':
        $deadline = h($data['deadline'] ?? '');
        $msg = h($data['message'] ?? theme_t('Finished'));
        $uid = 'timer-' . uniqid();
        $html = "<div id='{$uid}' class='bg-dark my-4 text-white text-center card'><div class='py-4 card-body'>";
        $html .= "<small class='text-white-50 text-uppercase'>" . theme_t('Time Remaining') . "</small><div class='mt-2 font-monospace display-4 fw-bold timer-display'>00:00:00:00</div></div></div>";
        $html .= "<script>(function(){const end=new Date('{$deadline}').getTime();const el=document.querySelector('#{$uid} .timer-display');const timer=setInterval(()=>{const now=new Date().getTime();const dist=end-now;if(dist<0){clearInterval(timer);el.innerHTML='{$msg}';return;}const d=Math.floor(dist/(1000*60*60*24));const h=Math.floor((dist%(1000*60*60*24))/(1000*60));const m=Math.floor((dist%(1000*60*60))/(1000*60));const s=Math.floor((dist%(1000*60))/1000);el.innerText=d+'d '+h.toString().padStart(2,'0')+'h '+m.toString().padStart(2,'0')+'m '+s.toString().padStart(2,'0')+'s';},1000);})();</script>";
        return $html;

      case 'qrcode':
        $url = $data['url'] ?? '';
        $size = (int)($data['size'] ?? 150);
        if ($url) {
          $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
          $html = "<div class='my-4 text-center'><img src='{$qrSrc}' alt='QR Code' class='p-2 img-thumbnail' width='{$size}' height='{$size}'></div>";
          return $html;
        }
        break;

      case 'map':
        $code = $data['code'] ?? '';
        if (preg_match('/<iframe\s+[^>]*src=["\']([^"\']+)["\']/i', $code, $matches)) {
          $src = $matches[1];
          $parsed = parse_url($src);
          $host = $parsed['host'] ?? '';
          $isGoogle = ($host === 'www.google.com' || $host === 'google.com') && strpos($parsed['path'] ?? '', '/maps') === 0;
          $isOSM = ($host === 'www.openstreetmap.org' || $host === 'openstreetmap.org');
          if ($isGoogle || $isOSM) {
            $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
            $html = "<div class='my-4 border rounded ratio ratio-16x9'><iframe src=\"{$safeSrc}\" allowfullscreen loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe></div>";
            return $html;
          }
        }
        break;

      case 'embed':
        $url = h($data['url'] ?? '');
        if ($url === '') return '';
        $align = $data['align'] ?? 'center';
        $alignClass = ($align === 'center') ? 'text-center mx-auto' : (($align === 'right') ? 'text-end ms-auto' : 'text-start');
        $embedHtml = "<a href='{$url}' target='_blank'>{$url}</a>";
        if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $url, $matches)) {
          $embedId = h($matches[1] . '/' . $matches[2]);
          $embedHtml = "<div class='bg-white shadow-sm mx-auto rounded overflow-hidden ratio ratio-16x9' style='max-width: 800px;'><iframe src='https://www.canva.com/design/{$embedId}/view?embed' allowfullscreen loading='lazy'></iframe></div>";
        } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
          $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($url);
          $embedHtml = "<div class='bg-white shadow-sm mx-auto rounded overflow-hidden ratio ratio-16x9' style='max-width: 800px;'><iframe src='" . h($embedUrl) . "' allowfullscreen loading='lazy'></iframe></div>";
        } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
          $vid = $matches[1];
          $embedHtml = "<div class='bg-black shadow-sm mx-auto rounded overflow-hidden ratio ratio-16x9' style='max-width: 800px;'><iframe src='https://www.youtube-nocookie.com/embed/{$vid}' allowfullscreen loading='lazy'></iframe></div>";
        }
        return "<div class='my-4 {$alignClass}'>{$embedHtml}</div>";

      case 'html':
        $code = $data['code'] ?? '';
        if ($code === '') return '';
        return "<div class='my-4'>{$code}</div>";
    }

    return null;
  }
}

/**
 * Render SNS share buttons.
 */
if (!function_exists('bootstrap_the_share_buttons')) {
  function bootstrap_the_share_buttons($url = null, $title = null)
  {
    // Use centralized helper
    $buttons = grinds_get_share_buttons($url, $title);
    if (empty($buttons)) return;

    echo '<div class="d-flex flex-wrap justify-content-center gap-2 mt-4 pt-4 border-top">';
    echo '<span class="d-flex align-items-center me-2 text-muted fw-bold small">' . theme_t('Share') . '</span>';

    foreach ($buttons as $button) {
      echo '<a href="' . h($button['share_url']) . '" target="_blank" rel="noopener noreferrer" class="d-flex align-items-center gap-1 text-white btn btn-sm" style="background-color:' . h($button['color']) . '; border-color:' . h($button['color']) . ';">';
      echo '<svg class="bi" width="16" height="16" fill="currentColor"><use href="' . h($button['sprite_url']) . '#' . h($button['icon']) . '"></use></svg>';
      echo '<span>' . h($button['display_name']) . '</span></a>';
    }

    echo '</div>';
  }
}
