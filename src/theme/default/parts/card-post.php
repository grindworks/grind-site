<?php

/**
 * card-post.php
 * Render post card.
 */
if (!defined('GRINDS_APP')) exit;
?>
<article class="flex flex-col bg-white shadow-md hover:shadow-xl border border-gray-100 rounded-lg h-full overflow-hidden transition-shadow duration-300">
    <a href="<?= h(resolve_url($post['slug'])) ?>" class="group block relative bg-gray-200 h-48 overflow-hidden shrink-0">
        <?php if ($post['thumbnail']): ?>
            <?= get_image_html($post['thumbnail'], ['class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500', 'alt' => h($post['title'])]) ?>
        <?php else: ?>
            <div class="flex justify-center items-center h-full text-gray-400"><?= theme_t('No Image') ?></div>
        <?php endif; ?>
    </a>

    <div class="flex flex-col flex-grow p-6">
        <div class="flex items-center mb-2 text-gray-600 text-xs">
            <?php $postDate = !empty($post['published_at']) ? $post['published_at'] : $post['created_at']; ?>
            <time datetime="<?= date('c', strtotime($postDate)) ?>"><?= the_date($postDate) ?></time>
            <?php if (!empty($post['category_name'])): ?>
                <span class="mx-2 text-gray-400">/</span>
                <a href="<?= h(resolve_url('category/' . $post['category_slug'])) ?>" class="text-grinds-red hover:underline"><?= h($post['category_name']) ?></a>
            <?php endif; ?>
        </div>

        <h2 class="mb-3 font-bold text-xl leading-tight">
            <a href="<?= h(resolve_url($post['slug'])) ?>" class="text-gray-900 hover:text-grinds-red transition-colors">
                <?= h($post['title']) ?>
            </a>
        </h2>

        <p class="flex-grow mb-4 text-gray-700 text-sm line-clamp-3 leading-relaxed"><?= function_exists('default_get_highlighted_excerpt') ? default_get_highlighted_excerpt($post) : h(get_excerpt($post['content'], 80)) ?></p>

        <div class="mt-auto pt-4 border-gray-100 border-t">
            <a href="<?= h(resolve_url($post['slug'])) ?>" class="inline-block font-bold text-grinds-red hover:text-grinds-dark text-sm transition" aria-label="<?= h(sprintf(theme_t('read_more_aria'), $post['title'])) ?>">
                <?= theme_t('read_more') ?>
            </a>
        </div>
    </div>
</article>
