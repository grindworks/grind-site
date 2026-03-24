<?php
if (!defined('GRINDS_APP')) exit;
$post = $pageData['post'];
$contentData = $post['content_decoded'] ?? json_decode($post['content'] ?? '{}', true);
?>

<article class="bg-white/60 backdrop-blur-2xl rounded-[3rem] p-8 sm:p-12 shadow-[0_8px_40px_rgb(0,0,0,0.04)] border border-white animate-fade-in-up opacity-0">

    <a href="<?= h(resolve_url('/')) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-indigo-600 transition-colors mb-8 bg-slate-50 hover:bg-indigo-50 px-4 py-2 rounded-full">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-left"></use>
        </svg>
        Back to Profile
    </a>

    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-8 bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-600">
        <?= h($post['title']) ?>
    </h1>

    <div class="prose prose-slate prose-indigo max-w-none">
        <?= render_content($contentData) ?>
    </div>

</article>
