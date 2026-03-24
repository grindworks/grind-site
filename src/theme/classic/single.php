<?php

if (!defined('GRINDS_APP')) exit;

/**
 * single.php
 * Render single post layout.
 */
global $post;
$post = $pageData['post'];
$GLOBALS['pageData'] = $pageData;
?>
<article class="single-post">
    <header class="entry-header">
        <h1 class="entry-title"><?php the_title(); ?></h1>

        <div class="entry-meta">
            <span class="date"><?php the_time(); ?></span>
            <span class="cat"><?php the_category(); ?></span>
        </div>

        <?php if (!empty($pageData['post']['thumbnail'])): ?>
            <div class="post-thumbnail">
                <?= get_image_html($pageData['post']['thumbnail'], ['alt' => h($pageData['post']['title']), 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
            </div>
        <?php endif; ?>
    </header>

    <div id="entry-content" class="entry-content">
        <?php if (!empty($pageData['post']['show_toc'])): ?>
            <?php
            $contentData = $pageData['post']['content_decoded'] ?? [];
            $headers = get_post_toc($contentData);
            ?>
            <?php if (!empty($headers)): ?>
                <div class="toc-container" style="background:#f9f9f9; padding:1.5rem; border:1px solid #ddd; border-radius:4px; margin-bottom:2rem;">
                    <h3 style="margin-top:0; font-size:1.2rem; border-bottom:1px solid #ddd; padding-bottom:0.5rem; margin-bottom:1rem;">
                        <?= h($pageData['post']['toc_title'] ?: theme_t('Contents')) ?>
                    </h3>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php foreach ($headers as $h): ?>
                            <li style="padding-left: <?= max(0, ($h['level'] - 2) * 1) ?>rem; margin-bottom: 0.5rem;">
                                <a href="#<?= $h['id'] ?>" style="text-decoration:none; color:#333;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                    <?= h($h['text']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
    </div>

    <footer class="entry-footer">
        <div class="tags-links">
            <?php the_tags(theme_t('Tags: '), ', '); ?>
        </div>
        <?php
        if ((!isset($post['show_share_buttons']) || !empty($post['show_share_buttons'])) && function_exists('classic_the_share_buttons')) {
            classic_the_share_buttons(get_permalink(), $post['title']);
        }
        ?>
    </footer>
</article>
