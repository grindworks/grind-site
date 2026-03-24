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
<footer class="bg-white mt-auto py-16 border-slate-900 border-t-2 text-slate-900">
  <div class="mx-auto px-4 text-center container">

    <?php if (!empty($footerBanners)): ?>
    <div class="mb-8">
      <?php if (function_exists('display_banners') && isset($ctx))
    display_banners('footer', $ctx); ?>
    </div>
    <?php
endif; ?>

    <?php if (!empty($footerMenus)): ?>
    <nav class="mb-12">
      <ul class="flex flex-wrap justify-center items-center gap-x-10 gap-y-6">
        <?php foreach ($footerMenus as $menu): ?>
        <li>
          <a href="<?= h($menu['url'])?>"
            class="text-slate-900 font-heading font-bold uppercase tracking-widest text-sm hover:text-brand-600 transition-colors"
            <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= h($menu['label'])?>
          </a>
        </li>
        <?php
  endforeach; ?>
      </ul>
    </nav>
    <?php
else: ?>
    <?php
  $fCats = [];
  if (function_exists('neo_minimalist_get_categories')) {
    $fCats = neo_minimalist_get_categories();
  }
  if (!empty($fCats)):
?>
    <nav class="mb-12">
      <ul class="flex flex-wrap justify-center items-center gap-x-10 gap-y-6">
        <li><a href="<?= h(resolve_url('/'))?>"
            class="text-slate-900 font-heading font-bold uppercase tracking-widest text-sm hover:text-brand-600 transition-colors">
            <?= theme_t('home')?>
          </a></li>
        <?php foreach ($fCats as $c): ?>
        <li><a href="<?= h(resolve_url('category/' . $c['slug']))?>"
            class="text-slate-900 font-heading font-bold uppercase tracking-widest text-sm hover:text-brand-600 transition-colors">
            <?= h($c['name'])?>
          </a></li>
        <?php
    endforeach; ?>
      </ul>
    </nav>
    <?php
  endif; ?>
    <?php
endif; ?>

    <!-- Render copyright and credit -->
    <div class="flex justify-center mt-12 pt-8 border-slate-900 border-t-2">
      <?php if ($copyright = get_option('footer_copyright')): ?>
      <p class="text-slate-500 font-medium text-xs uppercase tracking-widest">
        <?= h($copyright)?>
      </p>
      <?php
elseif ($footerText = get_option('site_footer_text')): ?>
      <p class="text-slate-500 font-medium text-xs uppercase tracking-widest">
        <?= h($footerText)?>
      </p>
      <?php
else: ?>
      <p class="text-slate-500 font-medium text-xs uppercase tracking-widest">&copy;
        <?= date('Y')?>
        <?= h(get_option('site_name'))?>.
      </p>
      <?php
endif; ?>
    </div>

  </div>
</footer>
