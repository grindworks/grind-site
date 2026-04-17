<?php

/**
 * footer.php
 * Render site footer.
 */
// Retrieve footer navigation menus
$footerMenus = [];
if (function_exists('get_nav_menus')) {
  $footerMenus = get_nav_menus('footer');
}

// Retrieve footer banners
$footerBanners = [];
if (function_exists('get_front_banners')) {
  $allBanners = get_front_banners();
  $footerBanners = $allBanners['footer'] ?? [];
}
?>
<footer class="bg-grinds-dark mt-auto py-10 border-white/10 border-t text-white">
  <div class="mx-auto px-4 text-center container">

    <?php if (!empty($footerBanners)): ?>
      <div class="mb-8">
        <?php if (function_exists('display_banners') && isset($ctx)) display_banners('footer', $ctx); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($footerMenus)): ?>
      <nav class="mb-8">
        <ul class="flex flex-wrap justify-center items-center gap-x-8 gap-y-4">
          <?php foreach ($footerMenus as $menu): ?>
            <li>
              <a href="<?= h($menu['url']) ?>"
                class="text-gray-200 hover:text-white text-sm hover:underline transition-colors"
                <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= h($menu['label']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php else: ?>
      <?php
      $fCats = [];
      global $pdo;
      if (isset($pdo) && $pdo instanceof PDO) {
        try {
          $fCats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
        } catch (Exception $e) {
          // ignore
        }
      }
      if (!empty($fCats)):
      ?>
        <nav class="mb-8">
          <ul class="flex flex-wrap justify-center items-center gap-x-8 gap-y-4">
            <li><a href="<?= h(resolve_url('/')) ?>" class="text-gray-200 hover:text-white text-sm hover:underline transition-colors"><?= theme_t('home') ?></a></li>
            <?php foreach ($fCats as $c): ?>
              <li><a href="<?= h(resolve_url('category/' . $c['slug'])) ?>" class="text-gray-200 hover:text-white text-sm hover:underline transition-colors"><?= h($c['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Render copyright and credit -->
    <div class="flex justify-center mt-8 pt-6 border-white/5 border-t">
      <?php if ($copyright = get_option('footer_copyright')): ?>
        <p class="text-gray-400 text-xs"><?= h($copyright) ?></p>
      <?php elseif ($footerText = get_option('site_footer_text')): ?>
        <p class="text-gray-400 text-xs"><?= h($footerText) ?></p>
      <?php else: ?>
        <p class="text-gray-400 text-xs">&copy; <?= date('Y') ?> <?= h(get_option('site_name')) ?>.</p>
      <?php endif; ?>
    </div>

  </div>
</footer>
