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
    <!-- Display regular content cleanly as a fallback -->
    <div class="fallback-container">
        <div style="background-color: #fffbeb; color: #b45309; padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 2.5rem; border: 1px solid #fde68a;">
            <strong style="font-size: 1.1rem; display: block; margin-bottom: 0.25rem;">ℹ️ <?= theme_t('presentation_not_found', 'Presentation not found.') ?></strong>
            <?= theme_t('presentation_not_found_desc', 'This page uses the presentation theme, but no presentation embed block was found in the content. Displaying standard content instead.') ?>
        </div>

        <?php if (!empty($pageData['post']['title'])): ?>
            <h1 style="font-size: 2.2rem; font-weight: bold; margin: 0 0 1.5rem 0; color: #111827; border-bottom: 2px solid #f3f4f6; padding-bottom: 0.75rem;">
                <?= h($pageData['post']['title']) ?>
            </h1>
        <?php endif; ?>

        <div style="font-size: 1.1rem; color: #374151; line-height: 1.8;">
            <?= render_content($postContent) ?>
        </div>

        <div style="margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; text-align: center;">
            <a href="<?= h(resolve_url('/')) ?>" style="display: inline-block; padding: 0.75rem 1.5rem; background-color: #f3f4f6; color: #374151; font-weight: 500; text-decoration: none; border-radius: 0.5rem; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#e5e7eb'" onmouseout="this.style.backgroundColor='#f3f4f6'">&larr; <?= theme_t('back_to_home', 'Back to Home') ?></a>
        </div>
    </div>
<?php endif; ?>
