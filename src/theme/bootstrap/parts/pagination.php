<?php

/**
 * pagination.php
 * Render pagination links.
 */
if (!defined('GRINDS_APP')) exit;

$txtPrev = theme_t('Prev');
$txtNext = theme_t('Next');

$paginator = $paginator ?? ($pageData['paginator'] ?? null);
if (!$paginator) return;

$page = $paginator->getPage();
$num_pages = $paginator->getNumPages();
$range = 2;

if ($num_pages <= 1) return;
?>
<nav aria-label="Page navigation" class="overflow-x-auto">
  <ul class="justify-content-center pagination flex-nowrap mb-0">
    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= ($page > 1) ? h($paginator->createUrl($page - 1)) : '#' ?>" aria-label="Previous">
        <span aria-hidden="true">&laquo; <?= $txtPrev ?></span>
      </a>
    </li>
    <?php for ($i = 1; $i <= $num_pages; $i++): ?>
      <?php if ($i == 1 || $i == $num_pages || ($i >= $page - $range && $i <= $page + $range)): ?>
        <?php if ($i == $page): ?>
          <li class="page-item active" aria-current="page"><span class="page-link"><?= $i ?></span></li>
        <?php else: ?>
          <li class="page-item"><a class="page-link" href="<?= h($paginator->createUrl($i)) ?>"><?= $i ?></a></li>
        <?php endif; ?>
      <?php elseif ($i == $page - $range - 1 || $i == $page + $range + 1): ?>
        <li class="page-item disabled"><span class="page-link">...</span></li>
      <?php endif; ?>
    <?php endfor; ?>
    <li class="page-item <?= ($page >= $num_pages) ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= ($page < $num_pages) ? h($paginator->createUrl($page + 1)) : '#' ?>" aria-label="Next">
        <span aria-hidden="true"><?= $txtNext ?> &raquo;</span>
      </a>
    </li>
  </ul>
</nav>
