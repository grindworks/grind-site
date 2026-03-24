<?php

if (!defined('GRINDS_APP')) exit;

/**
 * footer.php
 * Render site footer.
 */
$footerMenus = function_exists('get_nav_menus') ? get_nav_menus('footer') : [];
?>
<footer class="lg:hidden mt-12 px-6 py-8 border-gray-100 border-t text-center">
  <?php if (!empty($footerMenus)): ?>
    <nav class="mb-8">
      <ul class="flex flex-wrap justify-center gap-6">
        <?php foreach ($footerMenus as $menu): ?>
          <li><a href="<?= h($menu['url']) ?>" class="font-bold text-gray-400 hover:text-black text-xs uppercase tracking-widest transition-colors" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>><?= h($menu['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  <?php else: ?>
    <?php
    global $pdo;
    $fCats = [];
    if (isset($pdo)) {
      try {
        $fCats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
      } catch (Exception $e) {
      }
    }
    if (!empty($fCats)):
    ?>
      <nav class="mb-8">
        <ul class="flex flex-wrap justify-center gap-6">
          <li><a href="<?= h(resolve_url('/')) ?>" class="font-bold text-gray-400 hover:text-black text-xs uppercase tracking-widest transition-colors"><?= theme_t('home') ?></a></li>
          <?php foreach ($fCats as $c): ?>
            <li><a href="<?= h(resolve_url('category/' . $c['slug'])) ?>" class="font-bold text-gray-400 hover:text-black text-xs uppercase tracking-widest transition-colors"><?= h($c['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (function_exists('display_banners')) display_banners('footer', $ctx ?? []); ?>
  <div class="text-gray-400 text-xs">
    <?php if ($copyright = get_option('footer_copyright')): ?>
      <?= h($copyright) ?>
    <?php elseif ($footerText = get_option('site_footer_text')): ?>
      <?= h($footerText) ?>
    <?php else: ?>
      <?= sprintf(theme_t('copyright'), date('Y'), h(get_option('site_name'))) ?>
    <?php endif; ?>
  </div>
</footer>
