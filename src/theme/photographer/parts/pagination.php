<?php

/**
 * pagination.php
 * Render pagination.
 */
if (!defined('GRINDS_APP')) exit;

$lang = defined('SITE_LANG') ? SITE_LANG : 'en';
$txtPrev = ($lang === 'ja') ? '前へ' : 'Prev';
$txtNext = ($lang === 'ja') ? '次へ' : 'Next';

$page = $paginator->getPage();
$num_pages = $paginator->getNumPages();
$range = 2;

if ($num_pages <= 1) return;
?>

<?php if ($page > 1): ?>
    <a href="<?= h($paginator->createUrl($page - 1)) ?>">&larr; <?= $txtPrev ?></a>
<?php endif; ?>

<?php for ($i = 1; $i <= $num_pages; $i++): ?>
    <?php if ($i == 1 || $i == $num_pages || ($i >= $page - $range && $i <= $page + $range)): ?>
        <a href="<?= h($paginator->createUrl($i)) ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
    <?php elseif ($i == $page - $range - 1 || $i == $page + $range + 1): ?>
        <span>...</span>
    <?php endif; ?>
<?php endfor; ?>

<?php if ($page < $num_pages): ?>
    <a href="<?= h($paginator->createUrl($page + 1)) ?>"><?= $txtNext ?> &rarr;</a>
<?php endif; ?>
