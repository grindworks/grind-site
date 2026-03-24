<?php
if (!defined('GRINDS_APP')) exit;
// Get latest activity reports
$pdo = App::db();
$repo = new PostRepository($pdo);
$recent_activities = $repo->fetch(['status' => 'published', 'type' => 'post'], 4, 0, 'p.published_at DESC');

// Get hero settings
$heroSettings = $pageData['post']['hero_settings_decoded'] ?? [];
$heroImage = !empty($pageData['post']['hero_image']) ? resolve_url($pageData['post']['hero_image']) : '';
$slogan = $heroSettings['title'] ?? theme_t('hero_slogan');
$subtext = $heroSettings['subtext'] ?? theme_t('hero_subtext');
?>

<!-- Render hero section -->
<div class="relative bg-theme-primary text-white overflow-hidden">
    <div class="absolute inset-0 z-0">
        <?php if ($heroImage): ?>
            <?= get_image_html($heroImage, ['class' => 'w-full h-full object-cover opacity-40', 'loading' => 'eager']) ?>
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br from-blue-900 to-theme-primary opacity-90"></div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <div class="relative z-10 mx-auto px-4 py-32 md:py-48 container max-w-4xl text-center">
        <h2 class="font-serif text-4xl md:text-6xl lg:text-7xl font-bold leading-tight mb-8 tracking-widest drop-shadow-lg">
            <?= nl2br(h($slogan)) ?>
        </h2>
        <p class="text-xl md:text-2xl font-medium mb-12 leading-relaxed text-gray-100 drop-shadow-md max-w-2xl mx-auto">
            <?= nl2br(h($subtext)) ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-6 justify-center">
            <a href="<?= h(resolve_url('about')) ?>" class="btn-primary bg-white text-theme-primary hover:bg-gray-100 px-10 py-4 text-lg"><?= theme_t('view_profile') ?></a>
            <a href="<?= h(resolve_url('policy')) ?>" class="btn-primary border-2 border-white bg-transparent hover:bg-white hover:text-theme-primary px-10 py-4 text-lg"><?= theme_t('my_policy') ?></a>
        </div>
    </div>
</div>

<!-- Render main content -->
<?php if (!empty($pageData['post']['content'])): ?>
    <div class="mx-auto px-4 py-16 container max-w-4xl text-lg leading-loose text-gray-800">
        <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
    </div>
<?php endif; ?>

<!-- Render latest activity reports -->
<div class="bg-gray-50 py-20 border-t border-gray-200">
    <div class="mx-auto px-4 container max-w-6xl">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-serif font-bold text-theme-primary mb-3"><?= theme_t('activity_report') ?></h2>
            <p class="text-gray-500"><?= theme_t('activity_report_desc') ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($recent_activities as $post): ?>
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow group">
                    <a href="<?= h(resolve_url($post['slug'])) ?>" class="block relative h-48 overflow-hidden bg-gray-100">
                        <?php if ($post['thumbnail']): ?>
                            <?= get_image_html(resolve_url($post['thumbnail']), ['class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500']) ?>
                        <?php endif; ?>
                    </a>
                    <div class="p-6">
                        <time class="text-xs font-bold text-theme-accent mb-2 block tracking-wider">
                            <?= date('Y.m.d', strtotime($post['published_at'])) ?>
                        </time>
                        <h3 class="text-lg font-bold mb-3 line-clamp-2">
                            <a href="<?= h(resolve_url($post['slug'])) ?>" class="hover:text-theme-primary transition-colors">
                                <?= h($post['title']) ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                            <?= h(get_excerpt($post['content'], 80)) ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="<?= h(resolve_url('category/news')) ?>" class="btn-primary bg-gray-800 hover:bg-black text-white">
                <?= theme_t('view_all_activities') ?>
            </a>
        </div>
    </div>
</div>
