<?php

/**
 * widget-html.php
 * Render custom HTML widget.
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-white shadow-sm mb-8 p-6 border border-gray-200 rounded-lg widget-html">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-4 pb-2 border-gray-100 border-b font-bold text-lg">
            <span class="bg-grinds-red mr-2 rounded-full w-1 h-4"></span><?= h($title) ?>
        </h3>
    <?php endif; ?>
    <?php
    if (!empty($content)) {
        echo grinds_url_to_view($content);
    }
    ?>
</div>
