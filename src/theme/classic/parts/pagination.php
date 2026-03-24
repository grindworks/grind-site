<?php

/**
 * pagination.php
 * Render pagination links.
 */
if (!defined('GRINDS_APP')) exit;

// Set localized labels
$txtPrev = theme_t('Prev');
$txtNext = theme_t('Next');

$paginator = $paginator ?? ($pageData['paginator'] ?? null);
if (!$paginator) return;

// Retrieve pagination data
$totalPages = $paginator->getNumPages();
$currentPage = $paginator->getPage();

if ($totalPages <= 1) return;

$range = 2;
?>
<div class="pagination-container">
    <ul class="pagination">
        <!-- Render previous link -->
        <?php if ($currentPage > 1): ?>
            <li class="prev"><a href="<?= h($paginator->createUrl($currentPage - 1)) ?>">&laquo; <?= $txtPrev ?></a></li>
        <?php endif; ?>

        <!-- Render page numbers -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)): ?>
                <?php if ($i == $currentPage): ?>
                    <li class="active"><span><?= $i ?></span></li>
                <?php else: ?>
                    <li><a href="<?= h($paginator->createUrl($i)) ?>"><?= $i ?></a></li>
                <?php endif; ?>
            <?php elseif ($i == $currentPage - $range - 1 || $i == $currentPage + $range + 1): ?>
                <li class="dots"><span>...</span></li>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Render next link -->
        <?php if ($currentPage < $totalPages): ?>
            <li class="next"><a href="<?= h($paginator->createUrl($currentPage + 1)) ?>"><?= $txtNext ?> &raquo;</a></li>
        <?php endif; ?>
    </ul>
</div>
