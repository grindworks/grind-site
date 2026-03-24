<?php

/**
 * functions.php
 * Define theme helper functions.
 */
if (!defined('GRINDS_APP'))
  exit;

/**
 * Retrieve categories for fallback navigation.
 */
function neo_minimalist_get_categories()
{
  static $cachedCats = null;
  if ($cachedCats !== null) {
    return $cachedCats;
  }

  $pdo = App::db();
  if ($pdo) {
    try {
      $cachedCats = $pdo->query("SELECT name, slug FROM categories ORDER BY sort_order ASC")->fetchAll();
      return $cachedCats;
    } catch (Exception $e) {
    }
  }
  return [];
}

/**
 * Render SNS share buttons.
 */
function neo_minimalist_the_share_buttons($url = null, $title = null)
{
  // Use centralized helper
  $buttons = grinds_get_share_buttons($url, $title);
  if (empty($buttons))
    return;

  echo '<div class="flex flex-wrap gap-2 mt-8 pt-8 border-gray-100 border-t">';
  echo '<span class="flex items-center mr-2 font-bold text-gray-500 text-sm">' . theme_t('Share') . '</span>';

  foreach ($buttons as $button) {
    $displayName = $button['display_name'] ?? $button['name'];

    echo '<a href="' . h($button['share_url']) . '" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 hover:opacity-80 px-3 py-1.5 rounded font-bold text-white text-xs transition" style="background-color:' . h($button['color']) . ';">';
    echo '<svg class="w-3.5 h-3.5" fill="currentColor"><use href="' . h($button['sprite_url']) . '#' . h($button['icon']) . '"></use></svg>';
    echo '<span>' . h($displayName) . '</span></a>';
  }

  echo '</div>';
}

/**
 * Get highlighted excerpt for search results.
 */
function neo_minimalist_get_highlighted_excerpt($post)
{
  $text = (!empty($post['description'])) ? $post['description'] : get_excerpt($post['content'], 80);

  if (isset($_GET['q']) && $_GET['q'] !== '') {
    $keywords = preg_split('/[\s　]+/u', trim($_GET['q']), -1, PREG_SPLIT_NO_EMPTY);

    if (!empty($keywords)) {
      $plain = grinds_extract_text_from_content($post['content']);
      $pos = false;

      // Find the first occurrence of any keyword in the content
      foreach ($keywords as $kw) {
        $p = mb_stripos($plain, $kw, 0, 'UTF-8');
        if ($p !== false) {
          $pos = $p;
          break;
        }
      }

      if ($pos !== false) {
        $start = max(0, $pos - 40);
        $sub = mb_substr($plain, $start, 100, 'UTF-8');

        $marker = "\x01";
        $endMarker = "\x02";

        // Highlight all keywords
        foreach ($keywords as $kw) {
          $sub = preg_replace('/(' . preg_quote($kw, '/') . ')/iu', $marker . '$1' . $endMarker, $sub);
        }

        $escaped = h($sub);
        $final = str_replace([$marker, $endMarker], ['<mark class="bg-yellow-200 text-gray-900">', '</mark>'], $escaped);

        return '...' . $final . '...';
      }
    }
  }

  return h($text);
}

/**
 * Render content block.
 */
function neo_minimalist_render_block($block, $pathFixer)
{
  $type = $block['type'] ?? '';
  $data = $block['data'] ?? [];

  if ($type === 'embed') {
    $url = h($data['url'] ?? '');
    if ($url === '') return '';

    if (preg_match('/canva\.com\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)\/view/i', $url, $matches)) {
      $embedId = h($matches[1] . '/' . $matches[2]);
      return "<div class='my-12 aspect-video w-full bg-white border-2 border-slate-900 shadow-sharp overflow-hidden'><iframe src='https://www.canva.com/design/{$embedId}/view?embed' class='w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
    } elseif (preg_match('/figma\.com\/(file|proto)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
      $embedUrl = 'https://www.figma.com/embed?embed_host=share&url=' . urlencode($url);
      return "<div class='my-12 aspect-video w-full bg-white border-2 border-slate-900 shadow-sharp overflow-hidden'><iframe src='" . h($embedUrl) . "' class='w-full h-full' frameborder='0' allowfullscreen loading='lazy'></iframe></div>";
    } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
      return "<div class='my-12 aspect-video w-full bg-black border-2 border-slate-900 shadow-sharp overflow-hidden'><iframe src='https://www.youtube-nocookie.com/embed/{$matches[1]}' class='w-full h-full' frameborder='0' allowfullscreen></iframe></div>";
    }
    return "<div class='my-12 text-center'><a href='{$url}' target='_blank' class='font-bold text-slate-900 underline hover:text-brand-600'>{$url}</a></div>";
  }
  return null;
}
