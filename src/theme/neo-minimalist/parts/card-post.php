<?php

/**
 * card-post.php
 * Render post card.
 */
if (!defined('GRINDS_APP'))
    exit;
?>
<article
    class="flex flex-col bg-white border-2 border-slate-900 shadow-sharp hover:shadow-sharp-hover h-full overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:-translate-x-1">
    <a href="<?= h(resolve_url($post['slug'])) ?>"
        class="group block relative bg-slate-100 h-56 md:h-64 overflow-hidden shrink-0 border-b-2 border-slate-900"
        aria-label="<?= h($post['title']) ?>">
        <?php if ($post['thumbnail']): ?>
            <?= get_image_html($post['thumbnail'], ['class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500', 'alt' => h($post['title']), 'loading' => 'lazy']) ?>
        <?php
        else: ?>
            <div class="flex justify-center items-center h-full text-slate-400 text-sm font-bold uppercase tracking-widest">
                <?= theme_t('No Image') ?>
            </div>
        <?php
        endif; ?>
    </a>

    <div class="flex flex-col flex-grow p-6 md:p-8">
        <div class="flex items-center mb-4 text-slate-500 font-bold text-xs uppercase tracking-wider">
            <?php $postDate = !empty($post['published_at']) ? $post['published_at'] : $post['created_at']; ?>
            <time datetime="<?= date('c', strtotime($postDate)) ?>">
                <?= the_date($postDate) ?>
            </time>
            <?php if (!empty($post['category_name'])): ?>
                <span class="mx-2 text-slate-300">•</span>
                <a href="<?= h(resolve_url('category/' . $post['category_slug'])) ?>"
                    class="text-brand-600 hover:text-slate-900 transition-colors">
                    <?= h($post['category_name']) ?>
                </a>
            <?php
            endif; ?>
        </div>

        <h2 class="mb-4 font-heading font-extrabold text-2xl leading-tight tracking-tight">
            <a href="<?= h(resolve_url($post['slug'])) ?>" class="text-slate-900 hover:text-brand-600 transition-colors">
                <?= h($post['title']) ?>
            </a>
        </h2>

        <p class="flex-grow mb-6 text-slate-600 text-base line-clamp-3 leading-relaxed">
            <?= function_exists('neo_minimalist_get_highlighted_excerpt') ? neo_minimalist_get_highlighted_excerpt($post) : h(get_excerpt($post['content'], 80)) ?>
        </p>

        <div class="mt-auto pt-6 border-slate-900 border-t-2">
            <a href="<?= h(resolve_url($post['slug'])) ?>"
                class="inline-flex items-center justify-center font-heading font-bold text-slate-900 hover:text-brand-600 text-sm uppercase tracking-widest transition-colors group/link"
                aria-label="<?= h(sprintf(theme_t('read_more_aria'), $post['title'])) ?>">
                <?= theme_t('read_more') ?>
                <span class="ml-2 transform group-hover/link:translate-x-1 transition-transform">→</span>
            </a>
        </div>
    </div>
</article>
