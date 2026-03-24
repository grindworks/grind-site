<?php

if (!defined('GRINDS_APP')) exit;

/**
 * banners.php
 * Render banner images.
 */
if (!empty($position_banners)):
    $extraClass = ($position === 'header_top') ? 'pt-24 container mx-auto px-6' : '';
?>
    <div class="banner-area mb-8 grid gap-6 banner-pos-<?= h($position) ?> <?= $extraClass ?>">
        <?php foreach ($position_banners as $b): ?>
            <?php
            $bType = $b['type'] ?? 'image';
            if ($bType === 'html') {
                echo '<div class="banner-html">' . $b['html_code'] . '</div>';
            } else {
                $imgUrl = $b['image_url'];
                $linkUrl = resolve_url($b['link_url']);
                $anchorStyle = $b['anchor_style'] ?? '';
                $anchorClass = ($b['anchor_class'] ?? 'block') . ' group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1';
                $imgClass = ($b['image_class'] ?? 'w-full h-auto') . ' object-cover';

                if (!empty($linkUrl)) echo '<a href="' . h($linkUrl) . '" target="_blank" rel="noopener" class="' . $anchorClass . '" style="' . $anchorStyle . '"><div class="z-10 absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors"></div>';
                else echo '<div class="group block relative shadow-lg rounded-2xl overflow-hidden" style="' . $anchorStyle . '">';
                echo get_image_html($imgUrl, ['alt' => 'Banner', 'class' => $imgClass, 'loading' => 'lazy']);
                if (!empty($linkUrl)) echo '</a>';
                else echo '</div>';
            }
            ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
