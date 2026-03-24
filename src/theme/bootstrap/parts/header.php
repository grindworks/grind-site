<?php

if (!defined('GRINDS_APP')) exit;

/**
 * header.php
 * Render site header.
 */
$menus = [];
if (function_exists('get_nav_menus')) {
  $menus = get_nav_menus('header');
}
?>
<nav class="sticky-top bg-dark shadow navbar navbar-expand-lg navbar-dark py-3">
  <div class="container">
    <?php $isHome = (isset($pageType) && $pageType === 'home'); ?>
    <?php if ($isHome): ?>
      <h1 class="navbar-brand text-uppercase fw-bold my-0">
        <a href="<?= h(resolve_url('/')) ?>" class="text-reset text-decoration-none">
          <i class="me-2 bi bi-box-seam"></i><?= h(get_option('site_name', CMS_NAME)) ?>
        </a>
      </h1>
    <?php else: ?>
      <a class="text-uppercase navbar-brand fw-bold" href="<?= h(resolve_url('/')) ?>">
        <i class="me-2 bi bi-box-seam"></i><?= h(get_option('site_name', CMS_NAME)) ?>
      </a>
    <?php endif; ?>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="me-auto mb-2 mb-lg-0 navbar-nav">
        <?php if (!empty($menus)): ?>
          <?php foreach ($menus as $menu): ?>
            <?php
            $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
            $currentPath = rtrim($reqPath, '/') ?: '/';
            $menuUrl = $menu['url'];
            $isActive = false;

            if (strpos($menuUrl, '#') !== 0 && strpos($menuUrl, 'javascript:') !== 0) {
              $isActive = grinds_is_menu_active($menuUrl);
            }
            ?>
            <li class="nav-item">
              <a class="nav-link <?= $isActive ? 'active' : '' ?>"
                href="<?= h($menu['url']) ?>"
                <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= h($menu['label']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= h(resolve_url('/')) ?>"><?= theme_t('home') ?></a></li>
        <?php endif; ?>
      </ul>

      <form class="d-flex" action="<?= h(resolve_url('/')) ?>" method="get">
        <div class="input-group input-group-sm">
          <input class="form-control" type="search" name="q" placeholder="<?= theme_t('search_placeholder') ?>" aria-label="Search">
          <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i></button>
        </div>
      </form>
    </div>
  </div>
</nav>
