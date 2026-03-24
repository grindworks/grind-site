<?php

/**
 * Render single embed block as fullscreen presentation.
 */
if (!defined('GRINDS_APP')) exit;

// Extract embed block from post content
global $pageData;
$postContent = $pageData['post']['content_decoded'] ?? [];
$targetBlock = null;

if (isset($postContent['blocks']) && is_array($postContent['blocks'])) {
    foreach ($postContent['blocks'] as $block) {
        if (($block['type'] ?? '') === 'embed') {
            $targetBlock = $block;
            break;
        }
    }
}
?>

<?php if ($targetBlock): ?>
    <!-- Render presentation wrapper -->
    <div class="presentation-wrapper">
        <?= render_content(['blocks' => [$targetBlock]]) ?>
    </div>
<?php else: ?>
    <!-- Display fallback message -->
    <div style="display:flex; height:100vh; align-items:center; justify-content:center; color:#999; font-family:sans-serif; text-align: center; padding: 1rem;">
        <p><?= theme_t('presentation_not_found', 'Presentation content not found.') ?></p>
    </div>
<?php endif; ?>
