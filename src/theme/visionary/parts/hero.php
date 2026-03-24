<?php
if (!defined('GRINDS_APP')) exit;

if (($pageType ?? '') === 'home') return;

$heroImage = !empty($pageData['post']['hero_image']) ? resolve_url($pageData['post']['hero_image']) : '';
$heroSettings = $pageData['post']['hero_settings_decoded'] ?? [];
$title = $heroSettings['title'] ?? ($pageData['post']['title'] ?? '');
$subtext = $heroSettings['subtext'] ?? '';

if (!$heroImage && empty($heroSettings['title'])) return;
?>
<div class="relative bg-theme-primary text-white overflow-hidden py-16 md:py-24">
    <div class="absolute inset-0 z-0">
        <?php if ($heroImage): ?>
            <?= get_image_html($heroImage, ['class' => 'w-full h-full object-cover opacity-30']) ?>
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br from-blue-900 to-theme-primary opacity-90"></div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <div class="relative z-10 mx-auto px-4 container max-w-6xl text-center">
        <h1 class="font-serif text-3xl md:text-5xl font-bold tracking-widest drop-shadow-md">
            <?= nl2br(h($title)) ?>
        </h1>
        <?php if ($subtext): ?>
            <p class="mt-4 text-lg text-blue-100 drop-shadow-sm">
                <?= nl2br(h($subtext)) ?>
            </p>
        <?php endif; ?>
    </div>
</div>
