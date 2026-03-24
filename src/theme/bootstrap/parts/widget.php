<?php

/**
 * widget.php
 * Render single widget.
 */
if (!defined('GRINDS_APP')) exit;

// Capture the default widget output
ob_start();
render_widget($widget);
$html = trim(ob_get_clean());

if (empty($html)) return;

// Parse the widget output to restructure it as a Bootstrap Card
// 1. Extract Widget Type from class attribute
$widgetType = 'generic';
if (preg_match('/widget-([a-zA-Z0-9_-]+)/', $html, $matches)) {
    $widgetType = $matches[1];
}

// 2. Extract Title (assuming h3)
$title = '';
if (preg_match('/<h3[^>]*>(.*?)<\/h3>/si', $html, $matches)) {
    $title = $matches[1];
}

// 3. Extract Content
// First, remove the title h3 tag if it exists
$content = preg_replace('/<h3[^>]*>.*?<\/h3>\s*/si', '', $html, 1);

// Next, remove the outer wrapper div tags
$content = preg_replace('/^.*?<div[^>]*>/si', '', $content, 1);
$content = preg_replace('/<\/div>\s*$/si', '', $content, 1);

$content = trim($content);

?>
<div class="card mb-4 border-0 shadow-sm widget-<?= h($widgetType) ?>">
    <?php if ($title): ?>
        <div class="card-header bg-white border-0 fw-bold text-uppercase text-secondary small pt-4 pb-2">
            <?= $title ?>
        </div>
    <?php endif; ?>
    <div class="card-body pt-2">
        <?= $content ?>
    </div>
</div>
