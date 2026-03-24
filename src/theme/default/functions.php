<?php

/**
 * functions.php
 * Define theme helper functions.
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Retrieve categories for fallback navigation.
 */
if (!function_exists('default_get_categories')) {
  function default_get_categories()
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
}

/**
 * Render SNS share buttons.
 */
if (!function_exists('default_the_share_buttons')) {
  function default_the_share_buttons($url = null, $title = null)
  {
    // Use centralized helper
    $buttons = grinds_get_share_buttons($url, $title);
    if (empty($buttons)) return;

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
}

/**
 * Get highlighted excerpt for search results.
 */
if (!function_exists('default_get_highlighted_excerpt')) {
  function default_get_highlighted_excerpt($post)
  {
    $plain = null;
    $text = '';

    if (!empty($post['description'])) {
      $text = $post['description'];
    } else {
      $plain = grinds_extract_text_from_content((string)($post['content'] ?? ''));
      $excerpt = mb_strimwidth($plain, 0, 80, '...', 'UTF-8');
      $text = apply_filters('grinds_get_excerpt', $excerpt);
    }

    $rawQ = isset($_GET['q']) && is_scalar($_GET['q']) ? (string)$_GET['q'] : '';
    if ($rawQ !== '') {
      $keywords = preg_split('/[\s　]+/u', trim($rawQ), -1, PREG_SPLIT_NO_EMPTY);
      $keywords = array_unique($keywords);

      if (!empty($keywords)) {
        if ($plain === null) {
          $plain = grinds_extract_text_from_content((string)($post['content'] ?? ''));
        }
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

          $marker = "[[MARK_" . bin2hex(random_bytes(8)) . "_START]]";
          $endMarker = "[[MARK_" . bin2hex(random_bytes(8)) . "_END]]";

          usort($keywords, function ($a, $b) {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
          });

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
}
