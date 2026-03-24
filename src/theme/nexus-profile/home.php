<?php
if (!defined('GRINDS_APP')) exit;
// Get profile avatar or fallback to site OGP image
$avatarUrl = get_option('nexus_profile_avatar');
if (empty($avatarUrl)) {
    $avatarUrl = get_option('site_ogp');
}
if (empty($avatarUrl)) {
    $avatarUrl = grinds_asset_url('assets/img/default-avatar.png'); // Fallback image
}
?>

<div class="bg-white/60 backdrop-blur-2xl rounded-[3rem] p-8 sm:p-12 shadow-[0_8px_40px_rgb(0,0,0,0.04)] border border-white animate-fade-in-up opacity-0">
    <header class="text-center mb-12 flex flex-col items-center">
        <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
            <img src="<?= h($avatarUrl) ?>" alt="Avatar" class="relative w-32 h-32 sm:w-40 sm:h-40 rounded-full object-cover border-4 border-white shadow-xl bg-white transition-transform duration-500 group-hover:scale-105">
            <!-- Render online status indicator -->
            <div class="absolute bottom-3 right-3 w-6 h-6 bg-green-500 border-4 border-white rounded-full"></div>
        </div>

        <h1 class="mt-8 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-600">
            <?= h(get_option('site_name', CMS_NAME)) ?>
        </h1>

        <p class="mt-4 text-base sm:text-lg text-slate-600 font-medium max-w-lg mx-auto leading-relaxed">
            <?= h(get_option('site_description', 'Welcome to my profile.')) ?>
        </p>
    </header>

    <main class="grid grid-cols-1 sm:grid-cols-2 gap-4 animate-fade-in-up delay-100 opacity-0">
        <?php if (!empty($pageData['posts'])): ?>
            <?php foreach ($pageData['posts'] as $post): ?>
                <a href="<?= h(resolve_url($post['slug'])) ?>" class="group block relative p-5 bg-white/50 hover:bg-white rounded-2xl border border-slate-100 hover:border-indigo-100 hover:shadow-lg hover:shadow-indigo-500/10 transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex flex-col gap-4 h-full">
                        <div class="flex items-center justify-between">
                            <?php if (!empty($post['thumbnail'])): ?>
                                <img src="<?= h(resolve_url($post['thumbnail'])) ?>" class="w-12 h-12 rounded-xl object-cover shadow-sm group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-right"></use>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 mt-2">
                            <h2 class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1"><?= h($post['title']) ?></h2>
                            <?php if (!empty($post['description'])): ?>
                                <p class="text-sm text-slate-500 line-clamp-2 mt-1.5 leading-snug"><?= h($post['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<footer class="mt-12 text-center text-sm text-slate-400 font-medium animate-fade-in-up delay-200 opacity-0 relative z-10">
    &copy; <?= date('Y') ?> <?= h(get_option('site_name', CMS_NAME)) ?>
</footer>
