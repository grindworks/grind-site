<?php
if (!defined('GRINDS_APP')) exit;
$isSearch = (isset($pageType) && $pageType === 'search');
?>
<div class="max-w-4xl mx-auto">
    <?php if ($isSearch): ?>
        <h1 class="text-3xl font-extrabold text-zinc-900 mb-8">Search Results for "<?= h($_GET['q'] ?? '') ?>"</h1>

        <?php if (!empty($pageData['posts'])): ?>
            <div class="space-y-6">
                <?php foreach ($pageData['posts'] as $post): ?>
                    <article class="bg-white p-6 rounded-2xl shadow-sm border border-zinc-200 hover:border-indigo-300 transition-colors">
                        <div class="text-xs font-semibold text-indigo-600 mb-1"><?= h($post['category_name'] ?? 'Doc') ?></div>
                        <h2 class="text-xl font-bold text-zinc-900 mb-2">
                            <a href="<?= h(resolve_url($post['slug'])) ?>"><?= h($post['title']) ?></a>
                        </h2>
                        <p class="text-zinc-600 text-sm line-clamp-2"><?= h(get_excerpt($post['content'], 120)) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="mt-10"><?php the_pagination(); ?></div>
        <?php else: ?>
            <div class="text-center py-20 bg-zinc-100 rounded-2xl border border-zinc-200">
                <p class="text-zinc-500">No results found. Please try another keyword.</p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Hero section -->
        <div class="text-center py-16 md:py-24">
            <h1 class="text-4xl md:text-6xl font-extrabold text-zinc-900 tracking-tight mb-6">
                How can we help you?
            </h1>
            <p class="text-lg md:text-xl text-zinc-600 max-w-2xl mx-auto mb-10">
                Welcome to the official documentation. Search below to find answers, tutorials, and API references.
            </p>

            <form action="<?= h(resolve_url('/')) ?>" method="get" class="relative max-w-2xl mx-auto shadow-lg rounded-2xl">
                <svg class="pointer-events-none absolute top-4 left-5 h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                </svg>
                <input type="text" name="q" class="h-14 w-full rounded-2xl border border-zinc-200 bg-white pl-14 pr-4 text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg" placeholder="Search docs (e.g. Installation, API...)" required>
                <button type="submit" class="absolute right-2 top-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl font-bold transition-colors">Search</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <?php foreach (nexus_get_sidebar_tree() as $group): ?>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-zinc-200 hover:shadow-md transition-shadow">
                    <h2 class="text-xl font-bold text-zinc-900 mb-4"><?= h($group['category']['name']) ?></h2>
                    <ul class="space-y-3">
                        <?php foreach (array_slice($group['posts'], 0, 5) as $p): ?>
                            <li>
                                <a href="<?= h(resolve_url($p['slug'])) ?>" class="text-zinc-600 hover:text-indigo-600 flex items-center gap-2">
                                    <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                                    </svg>
                                    <?= h($p['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
