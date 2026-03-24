<?php

/**
 * widget-banner.php
 * Render banner widget.
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="mb-8 widget-banner">
    <?php if (!empty($settings['image'])):
        $imgUrl = $settings['image'];
        $altText = h($settings['alt'] ?? 'Banner');
        $imgHtml = get_image_html($imgUrl, [
            'alt' => $altText,
            'class' => 'hover:opacity-90 shadow-sm rounded-lg w-full transition-opacity',
            'loading' => 'lazy'
        ]);
        if (!empty($settings['link'])) {
            echo '<a href="' . h(resolve_url($settings['link'])) . '" target="_blank" rel="noopener" class="block">' . $imgHtml . '</a>';
        } else {
            echo $imgHtml;
        }
    endif; ?>
</div>
