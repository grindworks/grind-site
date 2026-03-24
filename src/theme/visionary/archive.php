<?php
if (!defined('GRINDS_APP')) exit;
$isSearch = (isset($pageType) && $pageType === 'search');
?>
<div class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200 mb-10">
    <header class="p-6 md:p-10 pb-8 border-b border-gray-100 bg-gray-50">
        <?php if (isset($pageType) && $pageType === 'category'): ?>
            <span class="font-bold text-theme-accent text-sm uppercase tracking-widest"><?= theme_t('category') ?></span>
            <h1 class="font-serif text-3xl md:text-4xl font-bold text-theme-primary leading-tight mt-2">
                <?= h($pageData['category']['name']) ?>
            </h1>
        <?php else: ?>
            <span class="font-bold text-theme-accent text-sm uppercase tracking-widest">
                <?= $isSearch ? theme_t('search_results') : theme_t('tag_archive') ?>
            </span>
            <h1 class="font-serif text-3xl md:text-4xl font-bold text-theme-primary leading-tight mt-2">
                <?php if ($isSearch): ?>
                    <?= h($_GET['q'] ?? '') ?>
                <?php elseif (isset($pageData['tag'])): ?>
                    # <?= h($pageData['tag']['name']) ?>
                <?php else: ?>
                    <?= theme_t('archive') ?>
                <?php endif; ?>
            </h1>
        <?php endif; ?>
    </header>

    <div class="p-6 md:p-10">
        <?php if (empty($pageData['posts'])): ?>
            <div class="text-center py-16 text-gray-500">
                <p class="text-lg"><?= theme_t('no_posts_found') ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($pageData['posts'] as $post): ?>
                    <article class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow group flex flex-col">
                        <a href="<?= h(resolve_url($post['slug'])) ?>" class="block relative h-48 overflow-hidden bg-gray-100 shrink-0">
                            <?php if ($post['thumbnail']): ?>
                                <?= get_image_html(resolve_url($post['thumbnail']), ['class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500']) ?>
                            <?php endif; ?>
                        </a>
                        <div class="p-6 flex-grow flex flex-col">
                            <time class="text-xs font-bold text-theme-accent mb-2 block tracking-wider">
                                <?= date('Y.m.d', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                            </time>
                            <h3 class="text-lg font-bold mb-3 line-clamp-2">
                                <a href="<?= h(resolve_url($post['slug'])) ?>" class="hover:text-theme-primary transition-colors">
                                    <?= h($post['title']) ?>
                                </a>
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 flex-grow">
                                <?= h(get_excerpt($post['content'], 80)) ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="mt-12">
                <?php if (function_exists('the_pagination')) the_pagination(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
