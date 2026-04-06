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
<div class="flex justify-center items-center mt-12">
  <nav class="inline-flex isolate -space-x-px bg-white shadow-sm rounded-md max-w-full overflow-x-auto no-scrollbar" aria-label="Pagination">

    <!-- Render previous link -->
    <?php
    $baseClass = "relative inline-flex items-center px-3 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 transition-colors";
    if ($page > 1): ?>
      <a href="<?= h($paginator->createUrl($page - 1)) ?>" class="<?= $baseClass ?> rounded-l-md" rel="prev">
        <span class="sr-only">Previous</span>
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
        </svg>
        <span class="hidden sm:inline ml-1"><?= $txtPrev ?></span>
      </a>
    <?php else: ?>
      <span class="<?= $baseClass ?> rounded-l-md opacity-40 cursor-not-allowed">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
        </svg>
      </span>
    <?php endif; ?>

    <!-- Render page numbers -->
    <?php for ($i = 1; $i <= $num_pages; $i++): ?>
      <?php if ($i == 1 || $i == $num_pages || ($i >= $page - $range && $i <= $page + $range)): ?>
        <?php if ($i == $page): ?>
          <span aria-current="page" class="inline-flex z-10 focus:z-20 relative items-center bg-gray-900 px-4 py-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-gray-900 focus-visible:outline-offset-2 font-semibold text-white text-sm">
            <?= $i ?>
          </span>
        <?php else: ?>
          <a href="<?= h($paginator->createUrl($i)) ?>" class="inline-flex focus:z-20 relative items-center hover:bg-gray-50 px-4 py-2 focus:outline-offset-0 ring-1 ring-gray-300 ring-inset font-semibold text-gray-900 text-sm">
            <?= $i ?>
          </a>
        <?php endif; ?>
      <?php elseif ($i == $page - $range - 1 || $i == $page + $range + 1): ?>
        <span class="inline-flex relative items-center px-4 py-2 focus:outline-offset-0 ring-1 ring-gray-300 ring-inset font-semibold text-gray-700 text-sm">...</span>
      <?php endif; ?>
    <?php endfor; ?>

    <!-- Render next link -->
    <?php if ($page < $num_pages): ?>
      <a href="<?= h($paginator->createUrl($page + 1)) ?>" class="<?= $baseClass ?> rounded-r-md" rel="next">
        <span class="hidden sm:inline mr-1"><?= $txtNext ?></span>
        <span class="sr-only">Next</span>
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
        </svg>
      </a>
    <?php else: ?>
      <span class="<?= $baseClass ?> rounded-r-md opacity-40 cursor-not-allowed">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
        </svg>
      </span>
    <?php endif; ?>

  </nav>
</div>
