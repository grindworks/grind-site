<?php

if (!defined('GRINDS_APP')) exit;

/**
 * archive.php
 * Display archives (category, tag, search).
 */
$isSearch = (isset($pageType) && $pageType === 'search');
$isCategory = (isset($pageType) && $pageType === 'category');
?>
<header class="mb-12 md:mb-16 text-center">
  <span class="block mb-2 font-bold text-gray-400 text-xs uppercase tracking-widest">
    <?php if ($isSearch): ?><?= theme_t('Search Results') ?><?php elseif ($isCategory): ?><?= theme_t('Category') ?><?php else: ?><?= theme_t('Tag Archive') ?><?php endif; ?>
  </span>
  <h1 class="font-serif text-3xl md:text-5xl italic">
    <?php if ($isSearch): ?>
      "<?= h($_GET['q'] ?? '') ?>"
    <?php elseif ($isCategory): ?>
      <?= h($pageData['category']['name'] ?? '') ?>
    <?php elseif (isset($pageData['tag'])): ?>
      # <?= h($pageData['tag']['name']) ?>
    <?php else: ?>
      <?= theme_t('Archive') ?>
    <?php endif; ?>
  </h1>
</header>
<?php get_template_part('home'); ?>
