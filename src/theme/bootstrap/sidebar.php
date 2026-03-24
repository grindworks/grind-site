<?php

/**
 * sidebar.php
 * Render sidebar.
 */
if (!defined('GRINDS_APP')) exit;

$widgets = function_exists('get_sidebar_widgets') ? get_sidebar_widgets() : [];
?>
<div class="col-lg-3">
    <div class="position-sticky" style="top: 2rem;">
        <?php if (!empty($widgets)): ?>
            <?php foreach ($widgets as $widget): ?>
                <?php
                ob_start();
                render_widget($widget);
                $html = ob_get_clean();
                if (function_exists('bootstrap_transform_widget_output')) {
                    echo bootstrap_transform_widget_output($html);
                } else {
                    echo $html;
                }
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-4 mb-3 bg-light rounded">
                <h4 class="fst-italic">Sidebar</h4>
                <p class="mb-0">Please add widgets from the admin panel.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
