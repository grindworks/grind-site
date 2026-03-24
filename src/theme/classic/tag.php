<?php

if (!defined('GRINDS_APP')) exit;

/**
 * tag.php
 * Display tag archive.
 */
?>
<header class="archive-header">
    <h1 class="archive-title"><?= theme_t('Tag: %s', h($ctx['data']['tag']['name'])) ?></h1>
</header>

<?php get_template_part('home'); ?>
