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

function photographer_the_share_buttons($url, $title)
{
  // Use centralized helper
  $buttons = grinds_get_share_buttons($url, $title);
  if (empty($buttons)) return;

  echo '<div class="flex justify-center items-center gap-6 mt-12">';
  echo '<span class="font-bold text-gray-400 text-xs uppercase tracking-widest">' . theme_t('Share') . '</span>';

  foreach ($buttons as $button) {
    echo '<a href="' . h($button['share_url']) . '" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-black transition-colors" aria-label="Share on ' . h($button['name']) . '">';
    echo '<svg class="w-5 h-5" fill="currentColor"><use href="' . h($button['sprite_url']) . '#' . h($button['icon']) . '"></use></svg>';
    echo '</a>';
  }

  echo '</div>';
}

/**
 * Render block.
 */
function photographer_render_block($block, $pathFixer)
{
  $type = $block['type'] ?? '';
  $data = $block['data'] ?? [];

  if ($type === 'gallery') {
    $images = $data['images'] ?? [];
    if (empty($images)) return '';
    $html = '<div class="gap-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 my-8">';
    foreach ($images as $img) {
      $src = resolve_url($img['url'] ?? '');
      if (!$src) continue;
      $html .= '<div class="group relative overflow-hidden">';
      $html .= get_image_html($src, ['class' => 'w-full h-auto object-cover group-hover:scale-105 transition duration-700', 'loading' => 'lazy']);
      if (!empty($img['caption'])) {
        $html .= '<p class="mt-2 text-gray-500 text-xs text-right italic">' . h($img['caption']) . '</p>';
      }
      $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
  }

  if ($type === 'carousel') {
    $images = $data['images'] ?? [];
    if (empty($images)) return '';

    $html = '<div class="group relative my-12 w-full" x-data="{ active: 0, total: ' . count($images) . ', next() { this.active = (this.active + 1) % this.total }, prev() { this.active = (this.active - 1 + this.total) % this.total } }">';

    $html .= '<div class="relative bg-gray-100 aspect-[3/2] md:aspect-[16/9] overflow-hidden">';
    foreach ($images as $index => $img) {
      $src = resolve_url($img['url'] ?? '');
      if (!$src) continue;

      $html .= '<div x-show="active === ' . $index . '" class="absolute inset-0 w-full h-full" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">';
      $html .= get_image_html($src, ['class' => 'w-full h-full object-cover', 'alt' => h($img['caption'] ?? '')]);
      if (!empty($img['caption'])) {
        $html .= '<div class="right-0 bottom-0 left-0 absolute bg-gradient-to-t from-black/60 to-transparent p-4 font-serif text-white text-sm text-center italic">' . h($img['caption']) . '</div>';
      }
      $html .= '</div>';
    }
    $html .= '</div>';

    if (count($images) > 1) {
      $html .= '<button @click="prev()" class="top-1/2 left-4 absolute opacity-0 group-hover:opacity-100 drop-shadow-md p-2 text-white hover:text-gray-200 transition-colors -translate-y-1/2 duration-300"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"></path></svg></button>';
      $html .= '<button @click="next()" class="top-1/2 right-4 absolute opacity-0 group-hover:opacity-100 drop-shadow-md p-2 text-white hover:text-gray-200 transition-colors -translate-y-1/2 duration-300"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path></svg></button>';
      $html .= '<div class="right-0 bottom-4 left-0 absolute flex justify-center gap-2">';
      for ($i = 0; $i < count($images); $i++) {
        $html .= '<button @click="active = ' . $i . '" class="drop-shadow-sm rounded-full w-2 h-2 transition-all" :class="active === ' . $i . ' ? \'bg-white scale-125\' : \'bg-white/50 hover:bg-white/80\'"></button>';
      }
      $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
  }

  // Fallback renderer.
  switch ($type) {
    case 'header':
      $level = strtolower($data['level'] ?? 'h2');
      if (!preg_match('/^h[2-6]$/', $level)) $level = 'h2';
      $text = h($data['text'] ?? '');
      if ($text === '') return '';
      return "<{$level} class='mt-12 mb-6 font-light text-gray-900 tracking-tight'>{$text}</{$level}>";

    case 'paragraph':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      return "<p class='mb-8 text-gray-600 leading-loose font-light'>{$text}</p>";

    case 'image':
      $url = resolve_url($data['url'] ?? '');
      if (!$url) return '';
      $caption = h($data['caption'] ?? '');
      $html = "<figure class='my-12'>";
      $html .= get_image_html($url, ['class' => 'w-full h-auto shadow-sm', 'loading' => 'lazy']);
      if ($caption) {
        $html .= "<figcaption class='mt-3 text-gray-400 text-xs text-center italic tracking-wider'>{$caption}</figcaption>";
      }
      $html .= "</figure>";
      return $html;

    case 'quote':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      $cite = h($data['cite'] ?? '');
      $html = "<blockquote class='my-12 pl-6 border-l border-gray-300 italic text-gray-500'>";
      $html .= "<p class='text-xl font-serif'>{$text}</p>";
      if ($cite) $html .= "<footer class='mt-4 text-xs uppercase tracking-widest'>— {$cite}</footer>";
      $html .= "</blockquote>";
      return $html;

    case 'divider':
      return "<hr class='my-16 border-gray-100 w-1/2 mx-auto'>";

    case 'button':
      $text = h($data['text'] ?? 'Button');
      $rawUrl = $data['url'] ?? '';
      if (empty($rawUrl) || $rawUrl === '#') return '';
      $url = resolve_url($rawUrl);
      return "<div class='my-12 text-center'><a href='{$url}' class='inline-block border border-gray-900 px-8 py-3 text-gray-900 hover:bg-gray-900 hover:text-white text-xs uppercase tracking-widest transition-colors duration-300'>{$text}</a></div>";

    case 'html':
      $code = $data['code'] ?? '';
      if ($code === '') return '';
      return "<div class='my-12'>{$code}</div>";

    case 'columns':
      $left = nl2br($pathFixer($data['leftText'] ?? ''));
      $right = nl2br($pathFixer($data['rightText'] ?? ''));
      if ($left === '' && $right === '') return '';
      return "<div class='gap-8 grid grid-cols-1 md:grid-cols-2 my-12'><div>{$left}</div><div>{$right}</div></div>";

      // Missing blocks.

    case 'spacer':
      $height = (int)($data['height'] ?? 50);
      return "<div style='height:{$height}px' aria-hidden='true'></div>";

    case 'section':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      return "<div class='bg-gray-50 my-12 p-12 text-center'>{$text}</div>";

    case 'callout':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      return "<div class='border-l-2 border-black my-12 pl-6 py-2'>{$text}</div>";

    case 'card':
      $url = resolve_url($data['url'] ?? '#');
      $title = h($data['title'] ?? '');
      $desc = h($data['description'] ?? '');
      $img = $data['image'] ?? '';
      if (empty($title) && empty($desc) && empty($img)) return '';
      $html = "<a href='{$url}' target='_blank' class='block border border-gray-200 my-12 hover:opacity-70 transition-opacity no-underline group'>";
      if ($img) $html .= "<div class='aspect-video bg-gray-100 overflow-hidden'>" . get_image_html(resolve_url($img), ['class' => 'w-full h-full object-cover']) . "</div>";
      $html .= "<div class='p-6'>";
      if ($title) $html .= "<h4 class='font-serif text-xl mb-2'>{$title}</h4>";
      if ($desc) $html .= "<p class='text-gray-500 text-sm'>{$desc}</p>";
      $html .= "</div></a>";
      return $html;

    case 'accordion':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='my-12 border-t border-gray-200'>";
      foreach ($items as $item) {
        $q = h($item['title'] ?? '');
        $a = nl2br($pathFixer($item['content'] ?? ''));
        $html .= "<details class='group border-b border-gray-200'><summary class='flex justify-between items-center py-4 cursor-pointer list-none font-serif'>{$q}<span class='text-gray-400'>+</span></summary><div class='pb-4 text-gray-600 font-light'>{$a}</div></details>";
      }
      $html .= "</div>";
      return $html;

    case 'timeline':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='my-12 space-y-8'>";
      foreach ($items as $item) {
        $date = h($item['date'] ?? '');
        $title = h($item['title'] ?? '');
        $content = nl2br($pathFixer($item['content'] ?? ''));
        $html .= "<div class='flex flex-col md:flex-row gap-2 md:gap-8'><div class='md:w-32 shrink-0 font-bold text-xs uppercase tracking-widest text-gray-400'>{$date}</div>";
        $html .= "<div><h4 class='font-serif text-lg mb-2'>{$title}</h4><div class='text-gray-600 font-light'>{$content}</div></div></div>";
      }
      $html .= "</div>";
      return $html;

    case 'step':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='my-12 space-y-8'>";
      foreach ($items as $i => $item) {
        $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
        $title = h($item['title'] ?? '');
        $desc = nl2br($pathFixer($item['desc'] ?? ''));
        $html .= "<div class='flex gap-6'><div class='text-4xl font-serif text-gray-200'>{$num}</div>";
        $html .= "<div><h4 class='font-bold text-sm uppercase tracking-widest mb-1'>{$title}</h4><div class='text-gray-600 font-light'>{$desc}</div></div></div>";
      }
      $html .= "</div>";
      return $html;

    case 'price':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='grid grid-cols-1 md:grid-cols-3 gap-8 my-12'>";
      foreach ($items as $item) {
        $plan = h($item['plan'] ?? '');
        $price = h($item['price'] ?? '');
        $feats = nl2br(h($item['features'] ?? ''));
        $border = !empty($item['recommend']) ? 'border-black' : 'border-gray-200';
        $html .= "<div class='border {$border} p-8 text-center'><h3 class='text-xs font-bold uppercase tracking-widest mb-4'>{$plan}</h3><div class='text-3xl font-serif mb-6'>{$price}</div><div class='text-sm text-gray-500 font-light'>{$feats}</div></div>";
      }
      $html .= "</div>";
      return $html;

    case 'testimonial':
      $name = h($data['name'] ?? '');
      $comment = nl2br($pathFixer($data['comment'] ?? ''));
      if ($comment === '') return '';
      return "<div class='my-12 text-center'><p class='font-serif text-xl italic mb-4'>\"{$comment}\"</p><div class='text-xs font-bold uppercase tracking-widest'>{$name}</div></div>";

    case 'embed':
      $url = h($data['url'] ?? '');
      if ($url === '') return '';
      if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $url, $matches)) {
        $embedId = h($matches[1] . '/' . $matches[2]);
        return "<div class='my-12 aspect-video bg-white'><iframe src='https://www.canva.com/design/{$embedId}/view?embed' class='w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
      } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
        $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($url);
        return "<div class='my-12 aspect-video bg-white'><iframe src='" . h($embedUrl) . "' class='w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
      } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
        return "<div class='my-12 aspect-video bg-black'><iframe src='https://www.youtube-nocookie.com/embed/{$matches[1]}' class='w-full h-full' frameborder='0' allowfullscreen></iframe></div>";
      }
      return "<div class='my-12 text-center'><a href='{$url}' target='_blank' class='underline'>{$url}</a></div>";

    case 'map':
      $code = $data['code'] ?? '';
      if (preg_match('/<iframe\s+[^>]*src=["\']([^"\']+)["\']/i', $code, $matches)) {
        $src = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        return "<div class='my-12 aspect-video bg-gray-100 grayscale'><iframe src=\"{$src}\" class='w-full h-full' style='border:0;' allowfullscreen loading='lazy'></iframe></div>";
      }
      return '';

    case 'download':
      $title = h($data['title'] ?? 'Download');
      $rawUrl = $data['url'] ?? '';
      if (empty($rawUrl) || $rawUrl === '#') return '';
      $url = resolve_url($rawUrl);
      $spriteUrl = resolve_url('assets/img/sprite.svg');
      return "<a href='{$url}' class='flex items-center justify-between border border-gray-200 p-4 my-12 hover:bg-gray-50 transition no-underline' download><span class='font-bold text-sm'>{$title}</span><svg class='w-5 h-5 text-gray-800' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-arrow-down'></use></svg></a>";

    case 'audio':
      $url = resolve_url($data['url'] ?? '');
      if (!$url) return '';
      return "<div class='my-12'><audio controls src='{$url}' class='w-full'></audio></div>";

    case 'pdf':
      $url = resolve_url($data['url'] ?? '');
      if (!$url) return '';
      return "<div class='my-12 border border-gray-200'><object data='{$url}' type='application/pdf' width='100%' height='500'><p class='p-4 text-center'>" . theme_t('Unable to display PDF.') . " <a href='{$url}' class='underline' aria-label='" . theme_t('download_pdf_aria') . "'>" . theme_t('Download') . "</a></p></object></div>";

    case 'search_box':
      $action = resolve_url('/');
      $ph = h($data['placeholder'] ?? theme_t('Search...'));
      return "<form action='{$action}' method='get' class='my-12 flex border-b border-black'><input type='text' name='q' placeholder='{$ph}' class='flex-1 py-2 outline-none bg-transparent font-serif'><button class='text-xs uppercase tracking-widest'>" . theme_t('Search') . "</button></form>";

    case 'internal_card':
      $id = $data['id'] ?? '';
      if ($id) return "<div class='my-12 p-6 border border-gray-200 text-center'><span class='block text-xs uppercase tracking-widest text-gray-400 mb-2'>" . theme_t('Internal Link') . "</span><a href='?p={$id}' class='font-serif text-xl italic hover:underline'>" . sprintf(theme_t('View Post (ID: %s)'), $id) . "</a></div>";
      return '';

    case 'conversation':
      $name = h($data['name'] ?? '');
      $img = resolve_url($data['image'] ?? '');
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      $html = "<div class='my-12 flex gap-6 items-start'>";
      if ($img) $html .= get_image_html($img, ['class' => 'w-12 h-12 rounded-full object-cover grayscale']);
      else $html .= "<div class='w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-400'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='" . resolve_url('assets/img/sprite.svg') . "#outline-user-circle'></use></svg></div>";
      $html .= "<div><div class='text-xs font-bold uppercase tracking-widest mb-1'>{$name}</div><div class='text-gray-600 font-light'>{$text}</div></div></div>";
      return $html;

    case 'proscons':
      $pTitle = h($data['pros_title'] ?? 'Pros');
      $cTitle = h($data['cons_title'] ?? 'Cons');
      $pItems = $data['pros_items'] ?? [];
      $cItems = $data['cons_items'] ?? [];
      if (empty($pItems) && empty($cItems)) return '';
      $html = "<div class='my-12 grid grid-cols-1 md:grid-cols-2 gap-8'>";
      $html .= "<div><h4 class='font-serif italic text-lg mb-4'>{$pTitle}</h4><ul class='list-disc pl-5 space-y-2 text-gray-600 font-light'>";
      foreach ($pItems as $item) if ($item) $html .= "<li>" . h($item) . "</li>";
      $html .= "</ul></div>";
      $html .= "<div><h4 class='font-serif italic text-lg mb-4'>{$cTitle}</h4><ul class='list-disc pl-5 space-y-2 text-gray-600 font-light'>";
      foreach ($cItems as $item) if ($item) $html .= "<li>" . h($item) . "</li>";
      $html .= "</ul></div></div>";
      return $html;

    case 'rating':
      $score = (float)($data['score'] ?? 5);
      $max = (int)($data['max'] ?? 5);
      $spriteUrl = resolve_url('assets/img/sprite.svg');
      $html = "<div class='my-12 flex items-center gap-4'><div class='flex items-center gap-0.5 text-gray-800'>";
      for ($i = 1; $i <= $max; $i++) {
        $html .= ($i <= round($score)) ? "<svg class='w-5 h-5' fill='currentColor' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-star'></use></svg>" : "<svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><use href='{$spriteUrl}#outline-star'></use></svg>";
      }
      $html .= "</div><span class='font-serif italic'>{$score} / {$max}</span></div>";
      return $html;

    case 'countdown':
      $deadline = h($data['deadline'] ?? '');
      $msg = h($data['message'] ?? 'Finished');
      $uid = 'timer-' . uniqid();
      $html = "<div id='{$uid}' class='my-12 p-8 border border-black text-center'><div class='text-xs uppercase tracking-widest mb-2'>" . theme_t('Time Remaining') . "</div><div class='font-serif text-4xl timer-display'>00:00:00:00</div></div>";
      $html .= "<script>(function(){const end=new Date('{$deadline}').getTime();const el=document.querySelector('#{$uid} .timer-display');const timer=setInterval(()=>{const now=new Date().getTime();const dist=end-now;if(dist<0){clearInterval(timer);el.innerHTML='{$msg}';return;}const d=Math.floor(dist/(1000*60*60*24));const h=Math.floor((dist%(1000*60*60*24))/(1000*60));const m=Math.floor((dist%(1000*60*60))/(1000*60));const s=Math.floor((dist%(1000*60))/1000);el.innerText=d+'d '+h.toString().padStart(2,'0')+'h '+m.toString().padStart(2,'0')+'m '+s.toString().padStart(2,'0')+'s';},1000);})();</script>";
      return $html;

    case 'qrcode':
      $url = $data['url'] ?? '';
      $size = (int)($data['size'] ?? 150);
      if ($url) {
        $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
        return "<div class='my-12 text-center'><img src='{$qrSrc}' alt='QR Code' class='inline-block border p-2' width='{$size}' height='{$size}'></div>";
      }
      return '';

    case 'list':
      $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
      $listClass = $style === 'ol' ? 'list-decimal' : 'list-disc';
      $items = $data['items'] ?? [];
      if (!empty($items)) {
        $html = "<{$style} class='{$listClass} list-outside ml-6 mb-8 text-gray-600 font-light space-y-2'>";
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
        $html = "<div class='my-12 overflow-x-auto'><table class='w-full text-sm text-left'>";
        foreach ($content as $rowIndex => $row) {
          if (!is_array($row)) continue;
          if ($withHeadings && $rowIndex === 0) {
            $html .= "<thead class='border-b border-black'><tr>";
            foreach ($row as $cell) $html .= "<th class='py-2 px-4 font-normal uppercase tracking-widest'>" . nl2br($pathFixer($cell ?? '')) . "</th>";
            $html .= "</tr></thead><tbody>";
          } else {
            if (!$withHeadings && $rowIndex === 0) $html .= "<tbody>";
            $html .= "<tr class='border-b border-gray-100'>";
            foreach ($row as $cell) $html .= "<td class='py-2 px-4 text-gray-600 font-light'>" . nl2br($pathFixer($cell ?? '')) . "</td>";
            $html .= "</tr>";
          }
        }
        $html .= "</tbody></table></div>";
        return $html;
      }
      return '';

    case 'code':
      $code = h($data['code'] ?? '');
      if ($code === '') return '';
      return "<pre class='bg-gray-50 my-12 p-6 overflow-x-auto font-mono text-xs text-gray-600'><code>{$code}</code></pre>";

    default:
      return null;
  }
}
