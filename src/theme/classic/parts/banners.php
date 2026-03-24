<?php

if (!defined('GRINDS_APP')) exit;

/**
 * banners.php
 * Render banner images.
 */
if (!empty($position_banners)):
?>
    <div class="grinds-banners banner-pos-<?= h($position) ?>" style="margin-bottom: 2rem;">
        <?php foreach ($position_banners as $b): ?>
            <div class="banner-item" style="margin-bottom: 1rem;">
                <?php
                $bType = $b['type'] ?? 'image';
                if ($bType === 'html'):
                    echo $b['html_code'];
                else:
                    $imgUrl = $b['image_url'];
                    $linkUrl = resolve_url($b['link_url']);

                    $anchorStyle = $b['anchor_style'] ?? 'width: 100%;';
                    $anchorClass = $b['anchor_class'] ?? 'block';

                    if (strpos($anchorStyle, 'display:') === false) {
                        $anchorStyle .= ' display: block;';
                    }

                    $imgStyle = 'width: 100%; height: auto; max-width: 100%;';

                    if (!empty($linkUrl)) {
                        echo '<a href="' . h($linkUrl) . '" target="_blank" class="' . $anchorClass . '" style="' . $anchorStyle . '">';
                    } else {
                        echo '<div class="' . $anchorClass . '" style="' . $anchorStyle . '">';
                    }

                    echo get_image_html($imgUrl, ['alt' => 'Banner', 'style' => $imgStyle, 'loading' => 'lazy']);

                    if (!empty($linkUrl)) {
                        echo '</a>';
                    } else {
                        echo '</div>';
                    }
                endif;
                ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
