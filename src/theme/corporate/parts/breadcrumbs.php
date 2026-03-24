<?php

if (!defined('GRINDS_APP')) exit;

/**
 * breadcrumbs.php
 * Render breadcrumb navigation.
 */
// Initialize breadcrumbs.
$crumbs = [['label' => theme_t('home'), 'url' => resolve_url('/')]];

// Add crumbs.
if ($pageType === 'category') {
  $crumbs[] = ['label' => $pageData['category']['name'], 'url' => ''];
} elseif ($pageType === 'single') {
  if (!empty($pageData['post']['category_id'])) {
    $catName = $pageData['post']['category_name'] ?? 'Category';
    $catSlug = $pageData['post']['category_slug'] ?? '';
    $catUrl = resolve_url('category/' . $catSlug);
    if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
      $catUrl .= '.html';
    }
    $crumbs[] = ['label' => $catName, 'url' => $catUrl];
  }
  $crumbs[] = ['label' => $pageData['post']['title'], 'url' => ''];
} elseif ($pageType === 'page') {
  $crumbs[] = ['label' => $pageData['post']['title'], 'url' => ''];
} elseif ($pageType === 'tag') {
  $crumbs[] = ['label' => '#' . ($pageData['tag']['name'] ?? 'Tag'), 'url' => ''];
} elseif ($pageType === 'search') {
  $crumbs[] = ['label' => theme_t('search_results'), 'url' => ''];
} elseif ($pageType === '404') {
  $crumbs[] = ['label' => theme_t('page_not_found'), 'url' => ''];
}
?>
<div class="bg-gray-50 py-3 border-corp-border border-b">
  <div class="mx-auto px-4 container">
    <!-- Render breadcrumbs. -->
    <nav class="flex text-gray-500 text-xs" aria-label="Breadcrumb">
      <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <?php foreach ($crumbs as $i => $crumb): ?>
          <li class="inline-flex items-center">
            <?php if ($i > 0): ?>
              <svg class="mx-1 w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            <?php endif; ?>

            <?php if (!empty($crumb['url'])): ?>
              <a href="<?= h($crumb['url']) ?>" class="hover:text-corp-accent transition-colors">
                <?= h($crumb['label']) ?>
              </a>
            <?php else: ?>
              <span class="max-w-[200px] font-medium text-gray-800 truncate">
                <?= h($crumb['label']) ?>
              </span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </nav>
  </div>
</div>
