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
 * Render content block.
 */
function corporate_render_block($block, $pathFixer)
{
  $type = $block['type'] ?? '';
  $data = $block['data'] ?? [];
  $commonClass = "cms-block-" . $type;
  $spriteUrl = resolve_url('assets/img/sprite.svg') . '?v=' . CMS_VERSION;

  switch ($type) {
    // Render button.
    case 'button':
      $text = h($data['text'] ?? 'Button');
      $rawUrl = $data['url'] ?? '';
      if (empty($rawUrl) || $rawUrl === '#') return '';
      $url = resolve_url($rawUrl);
      $target = !empty($data['external']) ? 'target="_blank" rel="noopener"' : '';
      return "<div class='my-8 text-center'><a href='{$url}' {$target} class='inline-block bg-corp-accent hover:opacity-90 shadow-lg px-8 py-3 rounded-full font-bold !text-white !no-underline transition-transform hover:-translate-y-0.5'>{$text}</a></div>";

      // Render card.
    case 'card':
      $url = resolve_url($data['url'] ?? '#');
      $title = h($data['title'] ?? '');
      $desc = h($data['description'] ?? '');
      $img = $data['image'] ?? '';
      if (empty($title) && empty($desc) && empty($img)) return '';

      $html = "<a href='{$url}' class='group block bg-white my-8 border border-corp-border hover:border-corp-accent !no-underline transition-colors'>";
      $html .= "<div class='flex sm:flex-row flex-col'>";
      if ($img) {
        $html .= "<div class='bg-gray-100 sm:w-48 h-48 shrink-0'>" . get_image_html($img, ['alt' => $title, 'class' => 'w-full h-full object-cover', 'loading' => 'lazy']) . "</div>";
      }
      $html .= "<div class='flex flex-col justify-center p-6'>";
      if ($title) $html .= "<h4 class='mb-2 font-bold text-corp-main group-hover:text-corp-accent text-lg transition-colors'>{$title}</h4>";
      if ($desc) $html .= "<p class='text-gray-600 text-sm line-clamp-2'>{$desc}</p>";
      $html .= "</div></div></a>";
      return $html;

    case 'columns':
      $left = nl2br($pathFixer($data['leftText'] ?? ''));
      $right = nl2br($pathFixer($data['rightText'] ?? ''));
      if ($left === '' && $right === '') return '';
      return "<div class='gap-8 grid grid-cols-1 md:grid-cols-2 my-8'><div class='max-w-none prose prose-slate'>{$left}</div><div class='max-w-none prose prose-slate'>{$right}</div></div>";

    case 'section':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      $bgColor = $data['bgColor'] ?? 'gray';
      $bgClass = match ($bgColor) {
        'blue' => 'bg-blue-50 text-blue-900',
        'yellow' => 'bg-yellow-50 text-yellow-900',
        'red' => 'bg-red-50 text-red-900',
        'green' => 'bg-green-50 text-green-900',
        default => 'bg-gray-100 text-gray-800',
      };
      return "<div class='{$bgClass} my-8 p-8 rounded-lg prose prose-slate max-w-none'>{$text}</div>";

    case 'carousel':
      $images = $data['images'] ?? [];
      if (empty($images)) return '';
      $count = count($images);
      $html = "<div x-data='{ active: 0, total: {$count}, next() { this.active = (this.active + 1) % this.total }, prev() { this.active = (this.active - 1 + this.total) % this.total } }' class='{$commonClass} relative w-full my-8 rounded-lg overflow-hidden shadow-sm border border-slate-200 group'>";

      $html .= "<div class='relative bg-slate-100 w-full aspect-video'>";
      foreach ($images as $i => $img) {
        $src = $img['url'] ?? '';
        $caption = h($img['caption'] ?? '');
        $html .= "<div x-show='active === {$i}' x-transition:enter='transition ease-out duration-300' x-transition:enter-start='opacity-0' x-transition:enter-end='opacity-100' x-transition:leave='transition ease-in duration-200' x-transition:leave-start='opacity-100' x-transition:leave-end='opacity-0' class='absolute inset-0 w-full h-full'>";
        $html .= get_image_html($src, ['alt' => $caption, 'class' => 'w-full h-full object-cover', 'loading' => ($i === 0 ? 'eager' : 'lazy')]);
        if ($caption) {
          $html .= "<div class='right-0 bottom-0 left-0 absolute bg-slate-900/80 p-3 text-white text-sm text-center'>{$caption}</div>";
        }
        $html .= "</div>";
      }
      $html .= "</div>";

      if ($count > 1) {
        $html .= "<button @click='prev()' class='top-1/2 left-4 absolute bg-white/90 hover:bg-white opacity-0 group-hover:opacity-100 shadow-md p-2 rounded-full focus:outline-none text-slate-800 transition-all -translate-y-1/2 duration-200'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7'/></svg></button>";
        $html .= "<button @click='next()' class='top-1/2 right-4 absolute bg-white/90 hover:bg-white opacity-0 group-hover:opacity-100 shadow-md p-2 rounded-full focus:outline-none text-slate-800 transition-all -translate-y-1/2 duration-200'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/></svg></button>";

        $html .= "<div class='bottom-4 left-1/2 absolute flex space-x-2 -translate-x-1/2'>";
        for ($i = 0; $i < $count; $i++) {
          $html .= "<button @click='active = {$i}' :class=\"{'bg-white w-6': active === {$i}, 'bg-white/50 w-2': active !== {$i}}\" class='shadow-sm rounded-full focus:outline-none h-2 transition-all duration-300'></button>";
        }
        $html .= "</div>";
      }
      $html .= "</div>";
      return $html;

      // Fallback renderer.
    case 'accordion':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='my-8 border-gray-200 border-t'>";
      foreach ($items as $item) {
        $q = h($item['title'] ?? '');
        $a = nl2br($pathFixer($item['content'] ?? ''));
        $html .= "<details class='group border-gray-200 border-b'><summary class='flex justify-between items-center py-4 font-bold cursor-pointer list-none'>{$q}<span class='text-gray-400'>+</span></summary><div class='pb-4 text-gray-600'>{$a}</div></details>";
      }
      $html .= "</div>";
      return $html;

    case 'gallery':
      $images = $data['images'] ?? [];
      if (empty($images)) return '';
      $cols = (int)($data['columns'] ?? 3);
      $html = "<div class='grid grid-cols-2 md:grid-cols-{$cols} gap-4 my-8'>";
      foreach ($images as $img) {
        $src = $img['url'] ?? '';
        $cap = h($img['caption'] ?? '');
        if ($src) {
          $html .= "<div>";
          $html .= get_image_html($src, ['class' => 'w-full h-full object-cover rounded shadow-sm', 'loading' => 'lazy']);
          if ($cap) $html .= "<div class='mt-1 text-gray-500 text-xs text-center'>{$cap}</div>";
          $html .= "</div>";
        }
      }
      $html .= "</div>";
      return $html;

    case 'timeline':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='space-y-8 my-8 ml-3 pl-6 border-gray-200 border-l-2'>";
      foreach ($items as $item) {
        $date = h($item['date'] ?? '');
        $title = h($item['title'] ?? '');
        $content = nl2br($pathFixer($item['content'] ?? ''));
        $html .= "<div class='relative'><span class='top-1 -left-[31px] absolute bg-gray-400 border-2 border-white rounded-full w-4 h-4'></span>";
        if ($date) $html .= "<div class='font-bold text-gray-500 text-sm'>{$date}</div>";
        if ($title) $html .= "<h4 class='font-bold text-lg'>{$title}</h4>";
        $html .= "<div class='mt-1 text-gray-600'>{$content}</div></div>";
      }
      $html .= "</div>";
      return $html;

    case 'step':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='space-y-6 my-8'>";
      foreach ($items as $i => $item) {
        $num = $i + 1;
        $title = h($item['title'] ?? '');
        $desc = nl2br($pathFixer($item['desc'] ?? ''));
        $html .= "<div class='flex gap-4'><div class='flex justify-center items-center bg-corp-main rounded-full w-8 h-8 font-bold text-white shrink-0'>{$num}</div>";
        $html .= "<div><h4 class='font-bold text-lg'>{$title}</h4><div class='mt-1 text-gray-600'>{$desc}</div></div></div>";
      }
      $html .= "</div>";
      return $html;

    case 'price':
      $items = $data['items'] ?? [];
      if (empty($items)) return '';
      $html = "<div class='gap-6 grid grid-cols-1 md:grid-cols-3 my-8'>";
      foreach ($items as $item) {
        $plan = h($item['plan'] ?? '');
        $price = h($item['price'] ?? '');
        $feats = nl2br(h($item['features'] ?? ''));
        $rec = !empty($item['recommend']) ? 'border-corp-accent ring-2 ring-blue-100' : 'border-gray-200';
        $html .= "<div class='border rounded p-6 text-center {$rec}'><h3 class='font-bold text-gray-500'>{$plan}</h3><div class='my-4 font-bold text-3xl'>{$price}</div><div class='text-gray-600 text-sm text-left'>{$feats}</div></div>";
      }
      $html .= "</div>";
      return $html;

    case 'testimonial':
      $name = h($data['name'] ?? '');
      $role = h($data['role'] ?? '');
      $comment = nl2br($pathFixer($data['comment'] ?? ''));
      if ($comment === '') return '';
      $img = $data['image'] ?? '';
      $html = "<div class='bg-gray-50 my-8 p-6 border border-gray-100 rounded'><p class='mb-4 text-gray-600 italic'>\"{$comment}\"</p>";
      $html .= "<div class='flex items-center gap-3'>";
      if ($img) $html .= get_image_html($img, ['alt' => $name, 'class' => 'w-10 h-10 rounded-full object-cover', 'loading' => 'lazy']);
      $html .= "<div><div class='font-bold text-sm'>{$name}</div><div class='text-xs text-gray-500'>{$role}</div></div></div></div>";
      return $html;

    case 'callout':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      $style = $data['style'] ?? 'info';
      $colors = match ($style) {
        'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-800',
        'success' => 'bg-green-50 border-green-400 text-green-800',
        'danger' => 'bg-red-50 border-red-400 text-red-800',
        default => 'bg-blue-50 border-blue-400 text-blue-800'
      };
      return "<div class='p-4 rounded border-l-4 my-6 {$colors}'>{$text}</div>";

    case 'embed':
      $url = h($data['url'] ?? '');
      if ($url === '') return '';
      $align = $data['align'] ?? 'center';
      $alignClass = ($align === 'center') ? 'text-center mx-auto' : (($align === 'right') ? 'text-right ml-auto' : 'text-left');
      $embedHtml = "<a href='{$url}' target='_blank' rel='noopener' class='text-blue-600 underline'>{$url}</a>";
      if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $url, $matches)) {
        $embedId = h($matches[1] . '/' . $matches[2]);
        $embedHtml = "<div class='relative w-full aspect-video rounded-lg overflow-hidden shadow-sm bg-white {$alignClass} max-w-[800px]'><iframe src='https://www.canva.com/design/{$embedId}/view?embed' class='absolute inset-0 w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
      } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
        $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($url);
        $embedHtml = "<div class='relative w-full aspect-video rounded-lg overflow-hidden shadow-sm bg-white {$alignClass} max-w-[800px]'><iframe src='" . h($embedUrl) . "' class='absolute inset-0 w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
      } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
        $vid = $matches[1];
        $embedHtml = "<div class='relative w-full aspect-video rounded-lg overflow-hidden shadow-sm bg-black {$alignClass} max-w-[800px]'><iframe src='https://www.youtube-nocookie.com/embed/{$vid}' class='absolute inset-0 w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
      }
      return "<div class='my-8 {$alignClass}'>{$embedHtml}</div>";

    case 'map':
      $code = $data['code'] ?? '';
      if (preg_match('/<iframe\s+[^>]*src=["\']([^"\']+)["\']/i', $code, $matches)) {
        $src = $matches[1];
        $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        return "<div class='shadow-sm my-8 border border-gray-200 rounded-lg aspect-video overflow-hidden'><iframe src=\"{$safeSrc}\" width=\"100%\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\"></iframe></div>";
      }
      return '';

    case 'html':
      $code = $data['code'] ?? '';
      if ($code === '') return '';
      return "<div class='cms-block-html my-8'>{$code}</div>";

    case 'download':
      $title = h($data['title'] ?? theme_t('Download'));
      $url = $data['url'] ?? '';
      if (empty($url) || $url === '#') return '';
      $size = h($data['fileSize'] ?? '');
      return "<a href='{$url}' class='flex items-center bg-gray-50 hover:bg-gray-100 my-6 p-4 border border-gray-200 rounded text-gray-800 no-underline transition' download><span class='mr-4 text-2xl'>⬇️</span><div><div class='font-bold'>{$title}</div><div class='text-gray-500 text-xs'>{$size}</div></div></a>";

    case 'audio':
      $url = resolve_url($data['url'] ?? '');
      $title = h($data['title'] ?? '');
      if (!$url) return '';
      $html = "<div class='bg-gray-100 my-6 p-4 rounded'>";
      if ($title) $html .= "<div class='mb-2 font-bold text-sm'>{$title}</div>";
      $html .= "<audio controls src='{$url}' class='w-full'></audio></div>";
      return $html;

    case 'pdf':
      $url = resolve_url($data['url'] ?? '');
      if (!$url) return '';
      return "<div class='bg-gray-100 my-8 border border-gray-200 rounded h-[500px] overflow-hidden'><object data='{$url}' type='application/pdf' width='100%' height='100%'><p class='p-4 text-center'>" . theme_t('msg_pdf_error', 'Unable to display PDF.') . " <a href='{$url}' class='underline' aria-label='" . theme_t('download_pdf_aria') . "'>" . theme_t('btn_download', 'Download') . "</a></p></object></div>";

    case 'internal_card':
      $id = $data['id'] ?? '';
      if ($id) return "<div class='bg-gray-50 my-6 p-4 border border-gray-200 rounded text-gray-500 text-sm text-center'>Internal Link (ID: {$id}) - <a href='?p={$id}' class='underline'>View Post (ID: {$id})</a></div>";
      return '';

    case 'conversation':
      $pos = ($data['position'] ?? 'left');
      $isRight = ($pos === 'right');
      $dir = $isRight ? 'flex-row-reverse' : 'flex-row';
      $bg = $isRight ? 'bg-green-100' : 'bg-gray-100';
      $name = h($data['name'] ?? '');
      $img = $data['image'] ?? '';
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      $html = "<div class='flex gap-4 my-6 {$dir}'><div class='flex flex-col items-center gap-1 shrink-0'>";
      if ($img) $html .= get_image_html($img, ['alt' => $name, 'class' => 'w-12 h-12 rounded-full object-cover border border-gray-200', 'loading' => 'lazy']);
      else $html .= "<div class='flex justify-center items-center bg-gray-200 rounded-full w-12 h-12 text-xl'>👤</div>";
      if ($name) $html .= "<span class='max-w-[5rem] text-gray-500 text-xs truncate'>{$name}</span>";
      $html .= "</div><div class='relative p-4 rounded-xl {$bg} text-gray-800 leading-relaxed max-w-[80%] shadow-sm'>{$text}</div></div>";
      return $html;

    case 'proscons':
      $pTitle = h($data['pros_title'] ?? theme_t('Good'));
      $cTitle = h($data['cons_title'] ?? theme_t('Bad'));
      $pItems = $data['pros_items'] ?? [];
      $cItems = $data['cons_items'] ?? [];
      if (empty($pItems) && empty($cItems)) return '';
      $html = "<div class='gap-6 grid grid-cols-1 md:grid-cols-2 my-8'>";
      $html .= "<div class='bg-green-50 p-4 border border-green-200 rounded-lg'><div class='flex items-center gap-2 mb-3 font-bold text-green-800'><span class='text-green-500'>✔</span> {$pTitle}</div><ul class='space-y-2'>";
      foreach ($pItems as $item) if ($item) $html .= "<li class='flex items-start gap-2 text-gray-700 text-sm'><span class='mt-1 text-green-500'>●</span> " . h($item) . "</li>";
      $html .= "</ul></div><div class='bg-red-50 p-4 border border-red-200 rounded-lg'><div class='flex items-center gap-2 mb-3 font-bold text-red-800'><span class='text-red-500'>✖</span> {$cTitle}</div><ul class='space-y-2'>";
      foreach ($cItems as $item) if ($item) $html .= "<li class='flex items-start gap-2 text-gray-700 text-sm'><span class='mt-1 text-red-500'>●</span> " . h($item) . "</li>";
      $html .= "</ul></div></div>";
      return $html;

    case 'rating':
      $score = (float)($data['score'] ?? 5);
      $max = (int)($data['max'] ?? 5);
      $html = "<div class='flex items-center gap-4 bg-white shadow-sm my-6 p-4 border border-gray-200 rounded-lg w-fit'><div class='text-2xl font-bold text-yellow-400 tracking-widest'>";
      for ($i = 1; $i <= $max; $i++) $html .= ($i <= round($score)) ? '★' : '☆';
      $html .= "</div><span class='font-bold text-gray-700 text-xl'>{$score}<span class='font-normal text-gray-400 text-sm'> / {$max}</span></span></div>";
      return $html;

    case 'countdown':
      $deadline = h($data['deadline'] ?? '');
      $msg = h($data['message'] ?? theme_t('Finished'));
      $uid = 'timer-' . uniqid();
      $html = "<div id='{$uid}' class='bg-gray-900 shadow-lg my-8 p-6 rounded-xl text-white text-center'><div class='opacity-70 mb-2 text-sm'>" . theme_t('Time Remaining') . "</div><div class='font-mono font-bold text-3xl md:text-5xl tracking-widest timer-display'>00:00:00:00</div></div>";
      $html .= "<script>(function(){const end=new Date('{$deadline}').getTime();const el=document.querySelector('#{$uid} .timer-display');const timer=setInterval(()=>{const now=new Date().getTime();const dist=end-now;if(dist<0){clearInterval(timer);el.innerHTML='{$msg}';return;}const d=Math.floor(dist/(1000*60*60*24));const h=Math.floor((dist%(1000*60*60*24))/(1000*60));const m=Math.floor((dist%(1000*60*60))/(1000*60));const s=Math.floor((dist%(1000*60))/1000);el.innerText=d+'d '+h.toString().padStart(2,'0')+'h '+m.toString().padStart(2,'0')+'m '+s.toString().padStart(2,'0')+'s';},1000);})();</script>";
      return $html;

    case 'qrcode':
      $url = $data['url'] ?? '';
      $size = (int)($data['size'] ?? 150);
      if ($url) {
        $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
        return "<div class='my-8 text-center'><img src='{$qrSrc}' alt='QR Code' class='bg-white shadow-sm mx-auto p-2 border rounded' width='{$size}' height='{$size}'></div>";
      }
      return '';

    case 'spacer':
      $height = (int)($data['height'] ?? 50);
      return "<div style='height:{$height}px' aria-hidden='true'></div>";

      // Basic blocks.

    case 'header':
      $level = strtolower($data['level'] ?? 'h2');
      if (!preg_match('/^h[2-6]$/', $level)) $level = 'h2';
      $text = h($data['text'] ?? '');
      if ($text === '') return '';
      return "<{$level} class='mt-10 mb-6 font-bold text-slate-900'>{$text}</{$level}>";

    case 'paragraph':
      $text = nl2br($pathFixer($data['text'] ?? ''));
      if ($text === '') return '';
      return "<p class='mb-6 text-slate-700 leading-relaxed'>{$text}</p>";

    case 'image':
      $url = $data['url'] ?? '';
      if (!$url) return '';
      $caption = h($data['caption'] ?? '');
      $html = "<figure class='my-8'>";
      $attrs = [
        'loading' => 'lazy',
        'class' => 'w-full rounded-lg shadow-sm mx-auto border border-gray-100'
      ];
      if (!empty($data['alt'])) {
        $attrs['alt'] = h($data['alt']);
      } elseif (!empty($data['caption'])) {
        $attrs['alt'] = h($data['caption']);
      }
      $html .= get_image_html($url, $attrs);
      if ($caption) {
        $html .= "<figcaption class='mt-2 text-gray-500 text-xs text-center'>{$caption}</figcaption>";
      }
      $html .= "</figure>";
      return $html;

    case 'list':
      $style = ($data['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
      $listClass = $style === 'ol' ? 'list-decimal' : 'list-disc';
      $items = $data['items'] ?? [];
      if (!empty($items)) {
        $html = "<{$style} class='{$listClass} list-outside ml-6 mb-6 text-slate-700 space-y-1'>";
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
        $html = "<div class='shadow-sm my-8 border border-gray-200 rounded-lg overflow-x-auto'>";
        $html .= "<table class='divide-y divide-gray-200 min-w-full text-sm'>";
        foreach ($content as $rowIndex => $row) {
          if (!is_array($row)) continue;
          if ($withHeadings && $rowIndex === 0) {
            $html .= "<thead class='bg-gray-50'><tr>";
            foreach ($row as $cell) {
              $cellText = nl2br($pathFixer($cell ?? ''));
              $html .= "<th scope='col' class='px-6 py-3 border-gray-200 border-r last:border-r-0 font-bold text-gray-500 text-xs text-left uppercase tracking-wider'>{$cellText}</th>";
            }
            $html .= "</tr></thead><tbody class='bg-white divide-y divide-gray-200'>";
          } else {
            if (!$withHeadings && $rowIndex === 0) {
              $html .= "<tbody class='bg-white divide-y divide-gray-200'>";
            }
            $html .= "<tr class='hover:bg-gray-50/50 transition-colors'>";
            foreach ($row as $cell) {
              $cellText = nl2br($pathFixer($cell ?? ''));
              $html .= "<td class='px-6 py-4 border-gray-200 border-r last:border-r-0 text-slate-700 whitespace-normal'>{$cellText}</td>";
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
      $html = "<blockquote class='bg-gray-50 my-8 p-6 border-l-4 border-corp-accent rounded-r-lg text-slate-700 italic'>";
      $html .= "<p>{$text}</p>";
      if ($cite) {
        $html .= "<footer class='mt-2 text-gray-500 text-sm'>— <cite>{$cite}</cite></footer>";
      }
      $html .= "</blockquote>";
      return $html;

    case 'divider':
      return "<hr class='my-10 border-gray-200'>";

    case 'code':
      $lang = h($data['language'] ?? 'plaintext');
      $code = h($data['code'] ?? '');
      if ($code === '') return '';
      return "<pre class='bg-slate-800 my-6 p-4 rounded overflow-x-auto font-mono text-white text-sm'><code class='language-{$lang}'>{$code}</code></pre>";

    case 'search_box':
      $action = resolve_url('/');
      $ph = h($data['placeholder'] ?? theme_t('search_placeholder'));
      $html = "<form action='{$action}' method='get' class='flex my-8'>";
      $html .= "<input type='text' name='q' placeholder='{$ph}' class='flex-1 p-2 border border-gray-300 rounded-l focus:border-corp-accent outline-none'><button class='bg-corp-accent px-4 rounded-r text-white hover:opacity-90 transition'>" . theme_t('search', 'Search') . "</button></form>";
      return $html;

    default:
      return null;
  }
}

/**
 * Render share buttons.
 */
if (!function_exists('corporate_the_share_buttons')) {
  function corporate_the_share_buttons($url = null, $title = null)
  {
    // Use centralized helper
    $buttons = grinds_get_share_buttons($url, $title);
    if (empty($buttons)) return;

    echo '<div class="flex flex-wrap gap-2 mt-8 pt-8 border-slate-100 border-t">';
    echo '<span class="flex items-center mr-2 font-bold text-slate-500 text-sm">' . theme_t('share', 'Share:') . '</span>';

    foreach ($buttons as $button) {
      echo '<a href="' . h($button['share_url']) . '" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 hover:opacity-90 px-3 py-1.5 rounded font-bold text-white text-xs transition" style="background-color:' . h($button['color']) . ';">';
      echo '<svg class="w-3 h-3" fill="currentColor"><use href="' . h($button['sprite_url']) . '#' . h($button['icon']) . '"></use></svg> ' . h($button['display_name']) . '</a>';
    }

    echo '</div>';
  }
}
