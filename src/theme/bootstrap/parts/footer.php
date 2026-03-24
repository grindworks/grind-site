<?php

if (!defined('GRINDS_APP')) exit;

/**
 * footer.php
 * Render site footer.
 */
// Fetch footer menus and banners.
$footerMenus = function_exists('get_nav_menus') ? get_nav_menus('footer') : [];
$footerBanners = [];
if (function_exists('get_front_banners')) {
  $allBanners = get_front_banners();
  $footerBanners = $allBanners['footer'] ?? [];
}
?>
<footer class="bg-dark text-white py-5 mt-auto border-top border-secondary">
  <div class="container text-center">

    <!-- Render footer banners -->
    <?php if (!empty($footerBanners)): ?>
      <div class="mb-4">
        <?php if (function_exists('display_banners') && isset($ctx)) display_banners('footer', $ctx); ?>
      </div>
    <?php endif; ?>

    <!-- Render footer menu -->
    <?php if (!empty($footerMenus)): ?>
      <nav class="mb-4">
        <ul class="list-inline m-0">
          <?php foreach ($footerMenus as $menu): ?>
            <li class="list-inline-item mx-3">
              <a href="<?= h($menu['url']) ?>"
                class="text-light text-decoration-none hover-opacity"
                <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= h($menu['label']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <!-- Render copyright text -->
    <p class="small text-secondary mb-0">
      <?php if ($copyright = get_option('footer_copyright')): ?>
        <?= h($copyright) ?>
      <?php else: ?>
        <?= h(get_option('site_footer_text')) ?>
      <?php endif; ?>
    </p>
  </div>
</footer>

<style>
  .hover-opacity:hover {
    opacity: 0.7;
  }
</style>
