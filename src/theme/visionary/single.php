<?php
if (!defined('GRINDS_APP')) exit;
?>
<article class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">

    <header class="p-6 md:p-10 pb-0">
        <!-- Render breadcrumbs -->
        <div class="mb-4">
            <?= get_breadcrumb_html(['link_class' => 'hover:text-theme-primary transition-colors']) ?>
        </div>

        <!-- Render article title -->
        <h1 class="font-serif text-3xl md:text-4xl font-bold text-gray-900 leading-tight mb-4">
            <?= h($pageData['post']['title']) ?>
        </h1>

        <!-- Render date and category -->
        <div class="flex items-center gap-4 text-sm text-gray-500 mb-8 pb-4 border-b border-gray-100">
            <?php if (!empty($pageData['post']['category_name'])): ?>
                <a href="<?= h(resolve_url('category/' . $pageData['post']['category_slug'])) ?>" class="bg-theme-accent text-white px-3 py-1 rounded-sm font-bold text-xs hover:opacity-80">
                    <?= h($pageData['post']['category_name']) ?>
                </a>
            <?php endif; ?>
            <time><?= the_date($pageData['post']['published_at'] ?? $pageData['post']['created_at']) ?></time>
        </div>

        <!-- Render thumbnail image -->
        <?php if (!empty($pageData['post']['thumbnail'])): ?>
            <div class="mb-8 rounded-lg overflow-hidden">
                <?= get_image_html(resolve_url($pageData['post']['thumbnail']), ['class' => 'w-full h-auto object-cover']) ?>
            </div>
        <?php endif; ?>
    </header>

    <div class="px-6 md:px-10 pb-10">
        <!-- Render table of contents -->
        <?php if (!empty($pageData['post']['show_toc'])): ?>
            <?php $headers = get_post_toc($pageData['post']['content_decoded'] ?? []); ?>
            <?php if (!empty($headers)): ?>
                <details open class="mb-10 p-6 bg-gray-50 border border-gray-200 rounded-lg">
                    <summary class="font-bold text-lg text-theme-primary cursor-pointer mb-4 outline-none">
                        <?= h($pageData['post']['toc_title'] ?: theme_t('table_of_contents')) ?>
                    </summary>
                    <ul class="space-y-2 list-none pl-4">
                        <?php foreach ($headers as $h): ?>
                            <li class="<?= $h['level'] === 3 ? 'ml-4' : '' ?>">
                                <a href="#<?= $h['id'] ?>" class="text-theme-text hover:text-theme-accent hover:underline"><?= h($h['text']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Render main content -->
        <div class="prose max-w-none text-lg leading-loose text-gray-800">
            <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
        </div>
    </div>

    <footer class="px-6 md:px-10 py-8 bg-gray-50 border-t border-gray-100">
        <!-- Render social share buttons -->
        <?php if (!empty($pageData['post']['show_share_buttons']) && function_exists('default_the_share_buttons')): ?>
            <?php default_the_share_buttons(resolve_url($pageData['post']['slug']), $pageData['post']['title']); ?>
        <?php endif; ?>
    </footer>
</article>
