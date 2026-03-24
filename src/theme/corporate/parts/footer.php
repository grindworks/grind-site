<?php

if (!defined('GRINDS_APP')) exit;

/**
 * footer.php
 * Render site footer.
 */
$footerMenus = function_exists('get_nav_menus') ? get_nav_menus('footer') : [];
$logo = get_option('admin_logo');
?>
<footer class="bg-slate-900 mt-auto py-12 border-slate-800 border-t text-white">
  <div class="mx-auto px-4 container">
    <div class="flex md:flex-row flex-col justify-between items-center gap-8">

      <!-- Render brand. -->
      <div class="md:text-left text-center">
        <div class="flex justify-center md:justify-start items-center gap-2 mb-2 font-bold text-2xl">
          <?php if ($logo): ?>
            <?= get_image_html(grinds_url_to_view($logo), ['alt' => h(get_option('site_name')), 'class' => 'bg-white rounded w-auto h-8', 'loading' => 'lazy']) ?>
          <?php else: ?>
            <div class="flex justify-center items-center bg-white rounded w-6 h-6 text-slate-900 text-sm"><?= h(mb_substr(get_option('site_name'), 0, 1)) ?></div>
          <?php endif; ?>
          <?= h(get_option('site_name')) ?>
        </div>
        <p class="text-slate-400 text-sm"><?= h(get_option('site_description')) ?></p>
      </div>

      <!-- Render navigation. -->
      <?php if (!empty($footerMenus)): ?>
        <nav>
          <ul class="flex flex-wrap justify-center gap-6">
            <?php foreach ($footerMenus as $menu): ?>
              <li>
                <a href="<?= h($menu['url']) ?>" class="font-bold text-slate-400 hover:text-white text-sm transition-colors" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
                  <?= h($menu['label']) ?>
                </a>
              </li>
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
          <nav>
            <ul class="flex flex-wrap justify-center gap-6">
              <li><a href="<?= h(resolve_url('/')) ?>" class="font-bold text-slate-400 hover:text-white text-sm transition-colors"><?= theme_t('home') ?></a></li>
              <?php foreach ($fCats as $c): ?>
                <li><a href="<?= h(resolve_url('category/' . $c['slug'])) ?>" class="font-bold text-slate-400 hover:text-white text-sm transition-colors"><?= h($c['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="mt-10 pt-6 border-slate-800 border-t text-slate-500 text-xs text-center">
      <?php if ($copyright = get_option('footer_copyright')): ?>
        <?= h($copyright) ?>
      <?php elseif ($footerText = get_option('site_footer_text')): ?>
        <?= h($footerText) ?>
      <?php else: ?>
        <?= '© ' . date('Y') . ' ' . h(get_option('site_name')) ?>
      <?php endif; ?>
    </div>
  </div>
</footer>
