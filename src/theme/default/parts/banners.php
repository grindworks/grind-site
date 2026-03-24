<?php

/**
 * banners.php
 * Render banner images.
 */
if (!empty($position_banners)):
?>
  <div class="banner-area mb-6 grid gap-4 banner-pos-<?= h($position) ?>">
    <?php foreach ($position_banners as $b): ?>
      <?php
      $bType = $b['type'] ?? 'image';
      if ($bType === 'html') {
        echo '<div class="banner-html">' . $b['html_code'] . '</div>';
      } else {
        $imgUrl = $b['image_url'];
        $linkUrl = resolve_url($b['link_url']);
        $anchorStyle = $b['anchor_style'] ?? '';
        $anchorClass = ($b['anchor_class'] ?? 'block') . ' transition hover:opacity-80';
        $imgClass = ($b['image_class'] ?? 'rounded w-full') . ' shadow border border-gray-100';

        if (!empty($linkUrl)) {
          echo '<a href="' . h($linkUrl) . '" target="_blank" class="' . $anchorClass . '" style="' . $anchorStyle . '">';
        } else {
          echo '<div class="' . $anchorClass . '" style="' . $anchorStyle . '">';
        }
        echo get_image_html($imgUrl, ['alt' => 'Banner', 'class' => $imgClass, 'loading' => 'lazy', 'decoding' => 'async']);
        if (!empty($linkUrl)) {
          echo '</a>';
        } else {
          echo '</div>';
        }
      }
      ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
