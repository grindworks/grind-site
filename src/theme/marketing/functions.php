<?php

/**
 * functions.php
 * Define theme functions.
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Translate string.
 */
// function theme_t removed (centralized in core)

/**
 * Render share buttons.
 */
if (!function_exists('marketing_the_share_buttons')) {
  function marketing_the_share_buttons($url, $title)
  {
    // Use centralized helper
    $buttons = grinds_get_share_buttons($url, $title);
    if (empty($buttons)) return;

    echo '<div class="flex items-center gap-3 mt-8">';
    echo '<span class="font-bold text-slate-500 text-sm">' . theme_t('share') . '</span>';

    foreach ($buttons as $button) {
      echo '<a href="' . h($button['share_url']) . '" target="_blank" rel="noopener noreferrer" class="flex justify-center items-center bg-slate-100 rounded-full w-8 h-8 text-slate-600 transition-colors" aria-label="Share on ' . h($button['name']) . '" style="background-color: ' . h($button['color']) . '; color: white;">';
      echo '<svg class="w-4 h-4" fill="currentColor"><use href="' . h($button['sprite_url']) . '#' . h($button['icon']) . '"></use></svg>';
      echo '</a>';
    }

    echo '</div>';
  }
}

/**
 * Get highlighted excerpt for search results.
 */
if (!function_exists('marketing_get_highlighted_excerpt')) {
  function marketing_get_highlighted_excerpt($post, $length = 120)
  {
    $excerpt = (!empty($post['description'])) ? h($post['description']) : h(get_excerpt($post['content'], $length));
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
        $final = str_replace([$marker, $endMarker], ['<mark class="bg-brand-100 text-brand-900">', '</mark>'], $escaped);
        $excerpt = '...' . $final . '...';
      }
    }
    return $excerpt;
  }
}

/**
 * Render content block.
 */
if (!function_exists('marketing_render_block')) {
  function marketing_render_block($block, $pathFixer)
  {
    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];
    $commonClass = "mk-block-" . $type;

    switch ($type) {
      // Render button.
      case 'button':
        $text = h($data['text'] ?? 'Click Here');
        $rawUrl = $data['url'] ?? '';
        if (empty($rawUrl) || $rawUrl === '#') return '';
        $url = resolve_url($rawUrl);
        $color = $data['color'] ?? 'primary';
        $target = !empty($data['external']) ? "target='_blank' rel='noopener'" : "";

        $btnClass = "inline-flex items-center justify-center px-10 py-4 text-lg font-bold rounded-full shadow-lg transition-all duration-300 hover:shadow-xl focus:outline-none focus:ring-4 !no-underline";

        if ($color === 'primary') $btnClass .= " bg-brand-600 !text-white hover:bg-brand-500 focus:ring-brand-300";
        elseif ($color === 'success') $btnClass .= " bg-emerald-500 !text-slate-900 hover:bg-emerald-400 focus:ring-emerald-300";
        elseif ($color === 'danger') $btnClass .= " bg-accent-500 !text-slate-900 hover:bg-accent-400 focus:ring-accent-300";
        elseif ($color === 'warning') $btnClass .= " bg-yellow-400 !text-slate-900 hover:bg-yellow-300 focus:ring-yellow-300";
        else $btnClass .= " bg-slate-800 !text-white hover:bg-slate-700 focus:ring-slate-500";

        return "<div class='my-12 text-center'><a href='{$url}' {$target} class='{$btnClass}'>{$text} <span class='ml-2'>&rarr;</span></a></div>";

        // Render section.
      case 'section':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $bgColor = $data['bgColor'] ?? 'gray';

        $styleClass = match ($bgColor) {
          'blue' => 'bg-gradient-to-br from-brand-50 to-white border-brand-100 text-brand-900',
          'yellow' => 'bg-amber-50 border-amber-100 text-amber-900',
          'red' => 'bg-rose-50 border-rose-100 text-rose-900',
          default => 'bg-slate-50 border-slate-100 text-slate-800',
        };

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

        return "<section{$attrStr} class='{$commonClass} my-16 p-10 md:p-12 rounded-3xl border {$styleClass} shadow-inner leading-relaxed text-lg'>{$text}</section>";

        // Render pricing.
      case 'price':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='items-center gap-8 grid grid-cols-1 md:grid-cols-3 my-16'>";

        foreach ($items as $item) {
          $plan = h($item['plan'] ?? '');
          $price = h($item['price'] ?? '');
          $feats = array_filter(explode("\n", h($item['features'] ?? '')));
          $isRec = !empty($item['recommend']);

          $wrapper = $isRec
            ? "border-2 border-brand-500 shadow-2xl scale-105 z-10 bg-white"
            : "border border-slate-200 shadow-lg bg-white hover:shadow-xl transition-shadow";

          $html .= "<div class='{$wrapper} rounded-2xl p-8 flex flex-col h-full relative'>";
          if ($isRec) {
            $html .= "<div class='top-0 left-1/2 absolute bg-brand-600 px-4 py-1 rounded-full font-bold text-white text-xs uppercase tracking-wider -translate-x-1/2 -translate-y-1/2 transform'>" . theme_t('most_popular') . "</div>";
          }

          $html .= "<h3 class='mb-4 font-bold text-slate-600 text-xl text-center'>{$plan}</h3>";
          $html .= "<div class='mb-8 font-extrabold text-slate-900 text-4xl text-center'>{$price}</div>";

          $html .= "<ul class='flex-1 space-y-4 mb-8'>";
          foreach ($feats as $f) {
            $html .= "<li class='flex items-start text-slate-600 text-sm'><svg class='mr-3 w-5 h-5 text-brand-500 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/></svg><span>" . trim($f) . "</span></li>";
          }
          $html .= "</ul>";

          $btnColor = $isRec ? "bg-brand-600 !text-white hover:bg-brand-700" : "bg-slate-100 !text-slate-700 hover:bg-slate-200";
          $html .= "<a href='#' class='block w-full py-3 rounded-xl font-bold text-center transition-colors !no-underline {$btnColor}'>" . theme_t('choose_plan') . "</a>";
          $html .= "</div>";
        }
        $html .= "</div>";
        return $html;

        // Render testimonial.
      case 'testimonial':
        $name = h($data['name'] ?? '');
        $role = h($data['role'] ?? '');
        $comment = nl2br($pathFixer($data['comment'] ?? ''));
        if ($comment === '') return '';
        $img = resolve_url($data['image'] ?? '');

        $html = "<div class='{$commonClass} my-12 bg-white p-8 rounded-2xl shadow-lg border border-slate-100 relative'>";
        $html .= "<svg class='top-6 left-6 absolute w-8 h-8 text-brand-100' fill='currentColor' viewBox='0 0 24 24'><path d='M7.17 6C5.42 6 4 7.79 4 10c0 2.21 1.42 4 3.17 4 .2 0 .39-.02.58-.06L6.5 18h3l1.5-4.5c.67-2 .17-7.5-3.83-7.5zm9 0C14.42 6 13 7.79 13 10c0 2.21 1.42 4 3.17 4 .2 0 .39-.02.58-.06L15.5 18h3l1.5-4.5c.67-2 .17-7.5-3.83-7.5z'/></svg>";
        $html .= "<div class='z-10 relative pt-6'>";
        $html .= "<p class='mb-6 text-slate-700 text-lg leading-relaxed'>{$comment}</p>";
        $html .= "<div class='flex items-center gap-4 pt-4 border-slate-100 border-t'>";
        if ($img) {
          $html .= "<img src='{$img}' class='rounded-full ring-2 ring-brand-50 w-12 h-12 object-cover' alt='" . h($name) . "' loading='lazy'>";
        } else {
          $html .= "<div class='flex justify-center items-center bg-brand-100 rounded-full w-12 h-12 text-brand-600 text-xl'>★</div>";
        }
        $html .= "<div><div class='font-bold text-slate-900'>{$name}</div>";
        if ($role) $html .= "<div class='font-bold text-brand-600 text-xs uppercase'>{$role}</div>";
        $html .= "</div></div></div></div>";
        return $html;

        // Render pros/cons.
      case 'proscons':
        $pTitle = h($data['pros_title'] ?? 'Good');
        $cTitle = h($data['cons_title'] ?? 'Bad');
        $pItems = $data['pros_items'] ?? [];
        $cItems = $data['cons_items'] ?? [];
        if (empty($pItems) && empty($cItems)) return '';

        $html = "<div class='{$commonClass} grid grid-cols-1 md:grid-cols-2 gap-8 my-12'>";
        $html .= "<div class='bg-white shadow-md p-6 border-emerald-500 border-t-4 rounded-xl'>";
        $html .= "<h4 class='flex items-center mb-4 font-bold text-emerald-700 text-lg'><svg class='mr-2 w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>{$pTitle}</h4>";
        $html .= "<ul class='space-y-3'>";
        foreach ($pItems as $it) if ($it) $html .= "<li class='flex items-start text-slate-700'><span class='mr-2 text-emerald-500'>✓</span> " . h($it) . "</li>";
        $html .= "</ul></div>";
        $html .= "<div class='bg-white shadow-md p-6 border-rose-500 border-t-4 rounded-xl'>";
        $html .= "<h4 class='flex items-center mb-4 font-bold text-rose-700 text-lg'><svg class='mr-2 w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>{$cTitle}</h4>";
        $html .= "<ul class='space-y-3'>";
        foreach ($cItems as $it) if ($it) $html .= "<li class='flex items-start text-slate-700'><span class='mr-2 text-rose-500'>✕</span> " . h($it) . "</li>";
        $html .= "</ul></div>";
        $html .= "</div>";
        return $html;

        // Render search.
      case 'search_box':
        $action = resolve_url('/');
        $ph = h($data['placeholder'] ?? theme_t('search_placeholder'));
        $html = "<form action='{$action}' method='get' class='flex my-8'>";
        $html .= "<input type='text' name='q' placeholder='{$ph}' class='flex-1 p-2 border rounded-l'><button type='submit' class='bg-blue-600 px-4 rounded-r text-white'>" . theme_t('search') . "</button></form>";
        return $html;

      case 'carousel':
        $images = $data['images'] ?? [];
        if (empty($images)) return '';
        $count = count($images);
        $html = "<div x-data='{ active: 0, total: {$count}, next() { this.active = (this.active + 1) % this.total }, prev() { this.active = (this.active - 1 + this.total) % this.total } }' class='{$commonClass} relative w-full my-16 rounded-2xl overflow-hidden shadow-xl group'>";

        $html .= "<div class='relative bg-slate-100 w-full aspect-video'>";
        foreach ($images as $i => $img) {
          $src = resolve_url($img['url'] ?? '');
          if (!$src) continue;
          $caption = h($img['caption'] ?? '');
          $html .= "<div x-show='active === {$i}' x-transition:enter='transition ease-out duration-500' x-transition:enter-start='opacity-0 scale-105' x-transition:enter-end='opacity-100 scale-100' x-transition:leave='transition ease-in duration-300' x-transition:leave-start='opacity-100 scale-100' x-transition:leave-end='opacity-0 scale-105' class='absolute inset-0 w-full h-full'>";
          $html .= get_image_html($src, ['alt' => $caption, 'class' => 'w-full h-full object-cover', 'loading' => ($i === 0 ? 'eager' : 'lazy')]);
          if ($caption) {
            $html .= "<div class='right-0 bottom-0 left-0 absolute bg-gradient-to-t from-brand-900/90 to-transparent p-4 font-bold text-white text-sm text-center'>{$caption}</div>";
          }
          $html .= "</div>";
        }
        $html .= "</div>";

        if ($count > 1) {
          $html .= "<button @click='prev()' class='top-1/2 left-4 absolute bg-white/90 hover:bg-brand-600 opacity-0 group-hover:opacity-100 shadow-lg p-3 rounded-full focus:outline-none text-brand-900 hover:text-white hover:scale-110 transition-all -translate-y-1/2 duration-300 transform'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7'/></svg></button>";
          $html .= "<button @click='next()' class='top-1/2 right-4 absolute bg-white/90 hover:bg-brand-600 opacity-0 group-hover:opacity-100 shadow-lg p-3 rounded-full focus:outline-none text-brand-900 hover:text-white hover:scale-110 transition-all -translate-y-1/2 duration-300 transform'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/></svg></button>";

          $html .= "<div class='bottom-6 left-1/2 absolute flex space-x-3 -translate-x-1/2'>";
          for ($i = 0; $i < $count; $i++) {
            $html .= "<button @click='active = {$i}' :class=\"{'bg-brand-500 w-8': active === {$i}, 'bg-white/50 w-2': active !== {$i}}\" class='shadow-sm rounded-full focus:outline-none h-2 transition-all duration-300'></button>";
          }
          $html .= "</div>";
        }
        $html .= "</div>";
        return $html;

        // Fallback renderer.
      case 'accordion':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='my-12 border-slate-200 border-t'>";
        foreach ($items as $item) {
          $q = h($item['title'] ?? '');
          $a = nl2br($pathFixer($item['content'] ?? ''));
          $html .= "<details class='group border-slate-200 border-b'><summary class='flex justify-between items-center py-5 font-bold text-slate-800 cursor-pointer list-none'>{$q}<span class='text-slate-400'>+</span></summary><div class='pb-5 text-slate-600'>{$a}</div></details>";
        }
        $html .= "</div>";
        return $html;

      case 'gallery':
        $images = $data['images'] ?? [];
        if (empty($images)) return '';
        $cols = (int)($data['columns'] ?? 3);
        $html = "<div class='grid grid-cols-2 md:grid-cols-{$cols} gap-6 my-12'>";
        foreach ($images as $img) {
          $src = resolve_url($img['url'] ?? '');
          $cap = h($img['caption'] ?? '');
          if ($src) {
            $html .= "<div>";
            $html .= get_image_html($src, ['class' => 'w-full h-full object-cover rounded-xl shadow-md hover:shadow-xl transition-shadow', 'loading' => 'lazy']);
            if ($cap) $html .= "<div class='mt-2 text-slate-500 text-xs text-center'>{$cap}</div>";
            $html .= "</div>";
          }
        }
        $html .= "</div>";
        return $html;

      case 'card':
        $url = resolve_url($data['url'] ?? '#');
        $title = h($data['title'] ?? '');
        $desc = h($data['description'] ?? '');
        $img = $data['image'] ?? '';
        if (empty($title) && empty($desc) && empty($img)) return '';
        $html = "<a href='{$url}' target='_blank' class='group block my-12 no-underline'><div class='flex flex-col sm:flex-row bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-lg hover:shadow-xl hover:border-brand-200 transition-all max-w-3xl mx-auto'>";
        if ($img) {
          $html .= "<div class='relative bg-slate-100 sm:w-48 h-48 sm:h-auto shrink-0'>" . get_image_html($img, ['class' => 'w-full h-full object-cover absolute inset-0', 'loading' => 'lazy']) . "</div>";
        }
        $html .= "<div class='flex flex-col flex-1 justify-center p-6'><h4 class='mb-2 font-bold text-slate-900 group-hover:text-brand-600 text-xl transition-colors'>{$title}</h4>";
        if ($desc) $html .= "<p class='text-slate-600 text-sm line-clamp-2'>{$desc}</p>";
        $html .= "</div></div></a>";
        return $html;

      case 'callout':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $style = $data['style'] ?? 'info';
        $colors = match ($style) {
          'warning' => 'bg-amber-50 border-amber-400 text-amber-900',
          'success' => 'bg-emerald-50 border-emerald-400 text-emerald-900',
          'danger' => 'bg-rose-50 border-rose-400 text-rose-900',
          default => 'bg-blue-50 border-blue-400 text-blue-900'
        };
        return "<div class='p-6 rounded-xl border-l-8 my-10 {$colors} shadow-sm'>{$text}</div>";

      case 'timeline':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='space-y-10 my-12 ml-4 pl-8 border-slate-200 border-l-2'>";
        foreach ($items as $item) {
          $date = h($item['date'] ?? '');
          $title = h($item['title'] ?? '');
          $content = nl2br($pathFixer($item['content'] ?? ''));
          $html .= "<div class='relative'><span class='top-1.5 -left-[41px] absolute bg-brand-500 border-4 border-white rounded-full w-5 h-5 shadow-sm'></span>";
          if ($date) $html .= "<div class='font-bold text-brand-600 text-sm uppercase tracking-wider'>{$date}</div>";
          if ($title) $html .= "<h4 class='mb-2 font-bold text-slate-900 text-xl'>{$title}</h4>";
          $html .= "<div class='text-slate-600'>{$content}</div></div>";
        }
        $html .= "</div>";
        return $html;

      case 'step':
        $items = $data['items'] ?? [];
        if (empty($items)) return '';
        $html = "<div class='space-y-8 my-12'>";
        foreach ($items as $i => $item) {
          $num = $i + 1;
          $title = h($item['title'] ?? '');
          $desc = nl2br($pathFixer($item['desc'] ?? ''));
          $html .= "<div class='flex gap-6'><div class='flex justify-center items-center bg-slate-900 shadow-lg rounded-full w-12 h-12 font-bold text-white text-xl shrink-0'>{$num}</div>";
          $html .= "<div><h4 class='font-bold text-slate-900 text-xl'>{$title}</h4><div class='mt-2 text-slate-600'>{$desc}</div></div></div>";
        }
        $html .= "</div>";
        return $html;

      case 'embed':
        $url = h($data['url'] ?? '');
        if ($url === '') return '';
        $align = $data['align'] ?? 'center';
        $alignClass = ($align === 'center') ? 'text-center mx-auto' : (($align === 'right') ? 'text-right ml-auto' : 'text-left');
        $embedHtml = "<a href='{$url}' target='_blank' class='text-brand-600 underline'>{$url}</a>";
        if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $url, $matches)) {
          $embedId = h($matches[1] . '/' . $matches[2]);
          $embedHtml = "<div class='relative w-full aspect-video rounded-2xl overflow-hidden shadow-xl bg-white {$alignClass} max-w-[800px]'><iframe src='https://www.canva.com/design/{$embedId}/view?embed' class='absolute inset-0 w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
        } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
          $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($url);
          $embedHtml = "<div class='relative w-full aspect-video rounded-2xl overflow-hidden shadow-xl bg-white {$alignClass} max-w-[800px]'><iframe src='" . h($embedUrl) . "' class='absolute inset-0 w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
        } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
          $vid = $matches[1];
          $embedHtml = "<div class='relative w-full aspect-video rounded-2xl overflow-hidden shadow-xl bg-black {$alignClass} max-w-[800px]'><iframe src='https://www.youtube-nocookie.com/embed/{$vid}' class='absolute inset-0 w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
        }
        return "<div class='my-12 {$alignClass}'>{$embedHtml}</div>";

      case 'html':
        $code = $data['code'] ?? '';
        if ($code === '') return '';
        return "<div class='mk-block-html my-12'>{$code}</div>";

      case 'audio':
        $url = resolve_url($data['url'] ?? '');
        $title = h($data['title'] ?? '');
        if (!$url) return '';
        $html = "<div class='bg-slate-100 my-8 p-4 rounded-xl'>";
        if ($title) $html .= "<div class='mb-2 font-bold text-slate-700 text-sm'>{$title}</div>";
        $html .= "<audio controls src='" . h($url) . "' class='w-full'></audio></div>";
        return $html;

      case 'pdf':
        $url = resolve_url($data['url'] ?? '');
        if (!$url) return '';
        return "<div class='bg-slate-100 my-10 border border-slate-200 rounded-xl h-[500px] overflow-hidden'><object data='" . h($url) . "' type='application/pdf' width='100%' height='100%'><p class='p-4 text-center'>" . theme_t('unable_pdf') . " <a href='" . h($url) . "' class='text-brand-600 underline'>" . theme_t('download') . "</a></p></object></div>";

      case 'internal_card':
        $id = $data['id'] ?? '';
        if ($id) return "<div class='bg-slate-50 my-8 p-4 border border-slate-200 rounded-xl text-slate-500 text-sm text-center'>" . sprintf(theme_t('internal_link_id'), $id) . " - <a href='?p={$id}' class='text-brand-600 underline'>" . theme_t('view_post') . "</a></div>";
        return '';

      case 'conversation':
        $pos = ($data['position'] ?? 'left');
        $isRight = ($pos === 'right');
        $dir = $isRight ? 'flex-row-reverse' : 'flex-row';
        $bg = $isRight ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-100 text-slate-800';
        $name = h($data['name'] ?? '');
        $img = $data['image'] ?? '';
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $html = "<div class='flex gap-4 my-8 {$dir}'><div class='flex flex-col items-center gap-1 shrink-0'>";
        if ($img) $html .= get_image_html($img, ['class' => 'w-12 h-12 rounded-full object-cover border border-slate-200', 'loading' => 'lazy']);
        else $html .= "<div class='flex justify-center items-center bg-slate-200 rounded-full w-12 h-12 text-xl'>👤</div>";
        if ($name) $html .= "<span class='max-w-[5rem] text-slate-400 text-xs truncate'>{$name}</span>";
        $html .= "</div><div class='relative p-5 rounded-2xl {$bg} leading-relaxed max-w-[80%] shadow-sm'>{$text}</div></div>";
        return $html;

      case 'rating':
        $score = (float)($data['score'] ?? 5);
        $max = (int)($data['max'] ?? 5);
        $html = "<div class='flex items-center gap-4 bg-white shadow-md my-8 p-5 border border-slate-100 rounded-2xl w-fit'><div class='text-2xl font-bold text-amber-400 tracking-widest'>";
        for ($i = 1; $i <= $max; $i++) $html .= ($i <= round($score)) ? '★' : '☆';
        $html .= "</div><span class='font-bold text-slate-700 text-xl'>{$score}<span class='font-normal text-slate-400 text-sm'> / {$max}</span></span></div>";
        return $html;

      case 'countdown':
        $deadline = h($data['deadline'] ?? '');
        $msg = h($data['message'] ?? 'Finished');
        $uid = 'timer-' . uniqid();
        $html = "<div id='{$uid}' class='bg-slate-900 shadow-xl my-10 p-8 rounded-2xl text-white text-center'><div class='opacity-70 mb-2 font-bold text-xs uppercase tracking-widest'>" . theme_t('time_remaining') . "</div><div class='font-mono font-black text-4xl md:text-6xl tracking-widest timer-display'>00:00:00:00</div></div>";
        $html .= "<script>(function(){const end=new Date('{$deadline}').getTime();const el=document.querySelector('#{$uid} .timer-display');const timer=setInterval(()=>{const now=new Date().getTime();const dist=end-now;if(dist<0){clearInterval(timer);el.innerHTML='{$msg}';return;}const d=Math.floor(dist/(1000*60*60*24));const h=Math.floor((dist%(1000*60*60*24))/(1000*60));const m=Math.floor((dist%(1000*60*60))/(1000*60));const s=Math.floor((dist%(1000*60))/1000);el.innerText=d+'d '+h.toString().padStart(2,'0')+'h '+m.toString().padStart(2,'0')+'m '+s.toString().padStart(2,'0')+'s';},1000);})();</script>";
        return $html;

      case 'qrcode':
        $url = $data['url'] ?? '';
        $size = (int)($data['size'] ?? 150);
        if ($url) {
          $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
          return "<div class='my-10 text-center'><img src='{$qrSrc}' alt='QR Code' class='bg-white shadow-lg mx-auto p-3 border border-slate-100 rounded-xl' width='{$size}' height='{$size}'></div>";
        }
        return '';

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
        return "<div class='{$commonClass} grid grid-cols-1 {$gridClass} gap-8 my-12'><div>{$left}</div><div>{$right}</div></div>";

      case 'download':
        $title = h($data['title'] ?? 'Download');
        $rawUrl = $data['url'] ?? '';
        if (empty($rawUrl) || $rawUrl === '#') return '';
        $url = resolve_url($rawUrl);
        $size = h($data['fileSize'] ?? '');
        return "<a href='{$url}' class='group flex items-center bg-white hover:bg-slate-50 shadow-md hover:shadow-lg my-12 p-6 border border-slate-200 rounded-xl transition-all no-underline' download><div class='bg-brand-100 mr-6 p-4 rounded-full text-brand-600 group-hover:scale-110 transition-transform'><svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'/></svg></div><div><div class='font-bold text-slate-900 text-lg'>{$title}</div><div class='text-slate-500 text-sm'>{$size}</div></div></a>";

      case 'map':
        $code = $data['code'] ?? '';
        if (preg_match('/<iframe\s+[^>]*src=["\']([^"\']+)["\']/i', $code, $matches)) {
          $src = $matches[1];
          $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
          return "<div class='shadow-lg my-12 border border-slate-200 rounded-2xl aspect-video overflow-hidden'><iframe src=\"{$safeSrc}\" width=\"100%\" height=\"100%\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\"></iframe></div>";
        }
        return '';

      case 'spacer':
        $height = (int)($data['height'] ?? 50);
        return "<div style='height:{$height}px' aria-hidden='true'></div>";

        // Basic blocks.

      case 'header':
        $level = strtolower($data['level'] ?? 'h2');
        if (!preg_match('/^h[2-6]$/', $level)) $level = 'h2';
        $text = h(strip_tags($data['text'] ?? ''));
        if ($text === '') return '';
        return "<{$level} class='mt-12 mb-6 font-heading font-bold text-slate-900'>{$text}</{$level}>";

      case 'paragraph':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        return "<p class='mb-6 text-slate-600 leading-relaxed'>{$text}</p>";

      case 'image':
        $url = resolve_url($data['url'] ?? '');
        if (!$url) return '';
        $caption = h($data['caption'] ?? '');
        $html = "<figure class='my-10'>";
        $html .= get_image_html($url, [
          'alt' => $caption,
          'loading' => 'lazy',
          'class' => 'w-full rounded-2xl shadow-lg mx-auto'
        ]);
        if ($caption) {
          $html .= "<figcaption class='mt-3 text-slate-400 text-xs text-center'>{$caption}</figcaption>";
        }
        $html .= "</figure>";
        return $html;

      case 'list':
        $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $listClass = $style === 'ol' ? 'list-decimal' : 'list-disc';
        $markerClass = $style === 'ol' ? 'marker:font-bold marker:text-brand-500' : 'marker:text-brand-500';
        $items = $data['items'] ?? [];
        if (!empty($items)) {
          $html = "<{$style} class='{$listClass} {$markerClass} list-outside ml-6 mb-8 text-slate-600 space-y-2'>";
          foreach ($items as $item) {
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
          $html = "<div class='shadow-md my-10 border border-slate-100 rounded-xl overflow-x-auto'>";
          $html .= "<table class='divide-y divide-slate-100 min-w-full text-sm'>";
          foreach ($content as $rowIndex => $row) {
            if (!is_array($row)) continue;
            if ($withHeadings && $rowIndex === 0) {
              $html .= "<thead class='bg-slate-50'><tr>";
              foreach ($row as $cell) {
                $cellText = nl2br($pathFixer($cell ?? ''));
                $html .= "<th scope='col' class='px-6 py-4 font-bold text-slate-500 text-xs text-left uppercase tracking-wider'>{$cellText}</th>";
              }
              $html .= "</tr></thead><tbody class='bg-white divide-y divide-slate-100'>";
            } else {
              if (!$withHeadings && $rowIndex === 0) {
                $html .= "<tbody class='bg-white divide-y divide-slate-100'>";
              }
              $html .= "<tr class='hover:bg-slate-50/50 transition-colors'>";
              foreach ($row as $cell) {
                $cellText = nl2br($pathFixer($cell ?? ''));
                $html .= "<td class='px-6 py-4 text-slate-600 whitespace-normal'>{$cellText}</td>";
              }
              $html .= "</tr>";
            }
          }
          $html .= "</tbody></table></div>";
          return $html;
        }
        return '';

      case 'quote':
        $text = nl2br($pathFixer($data['text'] ?? ''));
        if ($text === '') return '';
        $cite = h($data['cite'] ?? '');
        $html = "<blockquote class='bg-brand-50/30 my-10 p-6 border-l-8 border-brand-500 rounded-r-xl text-slate-700 italic'>";
        $html .= "<p>{$text}</p>";
        if ($cite) {
          $html .= "<footer class='mt-3 font-bold text-brand-600 text-sm'>— <cite>{$cite}</cite></footer>";
        }
        $html .= "</blockquote>";
        return $html;

      case 'divider':
        return "<hr class='my-12 border-slate-200'>";

      case 'code':
        $lang = h($data['language'] ?? 'plaintext');
        $code = h($data['code'] ?? '');
        if ($code === '') return '';
        return "<pre class='bg-slate-800 my-8 p-5 rounded-xl overflow-x-auto font-mono text-white text-sm shadow-inner'><code class='language-{$lang}'>{$code}</code></pre>";

      default:
        return null;
    }
  }
}

// Apply consistent styling to all sidebar widgets
add_filter('grinds_widget_output', function ($html, $widget) {
  $html = trim($html);
  if (empty($html)) return $html;

  // Skip if already styled (custom parts)
  if (strpos($html, 'shadow-lg') !== false && strpos($html, 'rounded-3xl') !== false) {
    return $html;
  }

  // Style the widget title
  $html = preg_replace(
    '/<h3[^>]*class=["\'][^"\']*widget-title[^"\']*["\'][^>]*>(.*?)<\/h3>/i',
    '<h3 class="flex items-center mb-6 font-bold text-slate-800 text-xl"><span class="block bg-brand-600 mr-3 rounded-full w-2 h-6"></span>$1</h3>',
    $html
  );

  // Apply container styles
  $containerStyles = 'bg-white shadow-lg mb-12 p-8 border border-slate-100 rounded-3xl';

  if (preg_match('/^<div[^>]*class=["\'][^"\']*widget[^"\']*["\'][^>]*>/i', $html)) {
    $html = preg_replace('/^<div[^>]*class=["\']([^"\']*)["\'][^>]*>/i', '<div class="$1 ' . $containerStyles . '">', $html, 1);
  } else {
    $html = '<div class="widget ' . $containerStyles . '">' . $html . '</div>';
  }

  return $html;
});
