<?php

if (!defined('GRINDS_APP')) exit;

/**
 * banners.php
 * Render banner images.
 */
if (!empty($position_banners)):
?>
  <div class="banner-area mb-4 d-grid gap-3 banner-pos-<?= h($position) ?>">
    <?php foreach ($position_banners as $b): ?>
      <?php
      $bType = $b['type'] ?? 'image';
      if ($bType === 'html') {
        echo '<div class="banner-html">' . $b['html_code'] . '</div>';
      } else {
        $imgUrl = $b['image_url'];
        $linkUrl = resolve_url($b['link_url']);
        $anchorStyle = $b['anchor_style'] ?? '';
        $anchorClass = 'd-block ' . ($b['anchor_class'] ?? '') . ' hover-shadow transition';
        $imgClass = 'img-fluid w-100 ' . ($b['image_class'] ?? 'rounded') . ' border border-light';

        if (!empty($linkUrl)) {
          echo '<a href="' . h($linkUrl) . '" target="_blank" rel="noopener" class="' . $anchorClass . '" style="' . $anchorStyle . '">';
        } else {
          echo '<div class="' . $anchorClass . '" style="' . $anchorStyle . '">';
        }
        echo get_image_html($imgUrl, ['alt' => 'Banner', 'class' => $imgClass, 'loading' => 'lazy']);
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
