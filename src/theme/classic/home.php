<?php

if (!defined('GRINDS_APP')) exit;

/**
 * home.php
 * Display post list.
 */
if (isset($pageType) && $pageType === 'search') {
    get_template_part('search');
    return;
}
?>
<div class="posts-list">
    <?php if (!empty($pageData['posts'])): ?>
        <?php foreach ($pageData['posts'] as $item):
            global $post;
            $post = $item;
            // Set global post data.
            $GLOBALS['pageData']['post'] = $post;
        ?>
            <article class="post-entry">
                <?php if (has_post_thumbnail()): ?>
                    <div class="post-thumbnail">
                        <a href="<?= get_permalink() ?>">
                            <?php the_post_thumbnail(); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <h2 class="post-title">
                    <a href="<?= get_permalink() ?>"><?php the_title(); ?></a>
                </h2>

                <div class="post-meta">
                    <span class="date"><?php the_time(); ?></span>
                    <span class="cat"><?php the_category(); ?></span>
                </div>

                <div class="post-excerpt">
                    <?php
                    if (function_exists('classic_get_highlighted_excerpt')) {
                        echo classic_get_highlighted_excerpt($post, 100);
                    } else {
                        echo (!empty($post['description'])) ? h($post['description']) : get_excerpt($post['content']);
                    }
                    ?>
                </div>

                <a href="<?= get_permalink() ?>" class="read-more" aria-label="<?= h(sprintf(theme_t('Read More about %s'), $post['title'])) ?>"><?= theme_t('Read More') ?> &rarr;</a>
            </article>
        <?php endforeach; ?>

        <?php the_pagination(); ?>

    <?php else: ?>
        <div class="no-posts">
            <p><?= theme_t('No posts found.') ?></p>
        </div>
    <?php endif; ?>
</div>
