<?php

/**
 * home.php
 * Render home post list as a fallback for fullscreen presentation theme.
 */
if (!defined('GRINDS_APP')) exit;
?>

<!-- Display regular content cleanly as a fallback -->
<div class="fallback-container">
    <div style="background-color: #fffbeb; color: #b45309; padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 2.5rem; border: 1px solid #fde68a;">
        <strong style="font-size: 1.1rem; display: block; margin-bottom: 0.25rem;">ℹ️ <?= theme_t('presentation_not_found', 'Presentation not found.') ?></strong>
        <?= theme_t('presentation_not_found_desc', 'This page uses the presentation theme, but no presentation embed block was found. Displaying standard content instead.') ?>
    </div>

    <h1 style="font-size: 2.2rem; font-weight: bold; margin: 0 0 1.5rem 0; color: #111827; border-bottom: 2px solid #f3f4f6; padding-bottom: 0.75rem;">
        <?= h(get_option('site_name', 'Home')) ?>
    </h1>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?php if (!empty($pageData['posts'])): ?>
            <?php foreach ($pageData['posts'] as $post): ?>
                <article style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; background-color: #f9fafb;">
                    <div style="font-size: 0.9rem; color: #6b7280; margin-bottom: 0.5rem;">
                        <time><?= date('Y.m.d', strtotime($post['published_at'] ?? $post['created_at'])) ?></time>
                        <?php if (!empty($post['category_name'])): ?>
                            <span style="margin: 0 0.5rem;">/</span>
                            <span style="color: #3b82f6;"><?= h($post['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: bold;">
                        <a href="<?= h(resolve_url($post['slug'])) ?>" style="color: #111827; text-decoration: none;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#111827'">
                            <?= h($post['title']) ?>
                        </a>
                    </h2>
                    <?php if (!empty($post['description'])): ?>
                        <p style="margin: 0 0 1rem 0; color: #4b5563; line-height: 1.6;">
                            <?= h($post['description']) ?>
                        </p>
                    <?php endif; ?>
                    <a href="<?= h(resolve_url($post['slug'])) ?>" style="display: inline-block; font-size: 0.9rem; font-weight: 600; color: #3b82f6; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        <?= theme_t('Read More', 'Read More') ?> &rarr;
                    </a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #6b7280;"><?= theme_t('No posts found.', 'No posts found.') ?></p>
        <?php endif; ?>
    </div>
</div>
