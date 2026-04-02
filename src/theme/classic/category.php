<?php

if (!defined('GRINDS_APP')) exit;

/**
 * category.php
 * Display category archive.
 */
?>
<header class="archive-header">
    <h1 class="archive-title"><?= theme_t('Category: %s', h($pageData['category']['name'] ?? '')) ?></h1>
</header>

<?php get_template_part('home'); ?>
