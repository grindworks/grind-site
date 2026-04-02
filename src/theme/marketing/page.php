<?php

if (!defined('GRINDS_APP')) exit;

/**
 * page.php
 * Display static page.
 */
?>
<article>

  <?php
  $heroSettings = $pageData['post']['hero_settings_decoded'] ?? json_decode($pageData['post']['hero_settings'] ?? '{}', true);
  $hasHeroImage = !empty($pageData['post']['hero_image']);
  $hasHeroTitle = !empty($heroSettings['title']);
  $showHero = $hasHeroImage || $hasHeroTitle;
  ?>

  <?php if ($showHero): ?>
    <?php get_template_part('parts/hero'); ?>
  <?php endif; ?>

  <div class="max-w-4xl mt-8">
    <?= get_breadcrumb_html([
      'wrapper_class' => 'flex flex-wrap text-sm text-slate-500',
      'link_class'    => 'hover:text-brand-600 transition-colors',
      'active_class'  => 'font-bold text-slate-700',
      'separator'     => '<span class="mx-2 text-slate-300">/</span>'
    ]) ?>
  </div>

  <header class="max-w-4xl text-center mb-16 <?php echo $showHero ? 'mt-12' : 'pt-12'; ?>">
    <?php if (!$showHero): ?>
      <h1 class="text-3xl md:text-5xl font-black text-slate-900 leading-tight mb-8 font-heading">
        <?= h($pageData['post']['title']) ?>
      </h1>
    <?php endif; ?>

    <?php if ($pageData['post']['thumbnail'] && !$hasHeroImage): ?>
      <div class="rounded-2xl overflow-hidden shadow-xl border border-slate-100">
        <?= get_image_html($pageData['post']['thumbnail'], ['class' => 'w-full h-auto object-cover max-h-[500px]', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
      </div>
    <?php endif; ?>
  </header>

  <div class="mx-auto max-w-3xl">
    <div id="page-content" class="entry-content">
      <?php if (!empty($pageData['post']['show_toc'])): ?>
        <?php
        $contentData = $pageData['post']['content_decoded'] ?? [];
        $headers = get_post_toc($contentData);
        ?>
        <?php if (!empty($headers)): ?>
          <details id="toc" open class="mb-12 p-6 border border-slate-200 rounded-2xl bg-slate-50 shadow-sm">
            <summary class="font-bold cursor-pointer text-slate-900 mb-4 text-lg outline-none flex items-center font-heading">
              <?= h($pageData['post']['toc_title'] ?: theme_t('toc_title', 'Contents')) ?>
            </summary>
            <ul class="space-y-3 list-none m-0 p-0 ml-0">
              <?php foreach ($headers as $h): ?>
                <?php
                $indentClass = 'ml-0 font-bold';
                if ($h['level'] === 3) $indentClass = 'ml-4 font-normal';
                elseif ($h['level'] === 4) $indentClass = 'ml-8 font-normal text-sm';
                elseif ($h['level'] >= 5) $indentClass = 'ml-12 font-normal text-xs';
                ?>
                <li class="<?= $indentClass ?>">
                  <a href="#<?= $h['id'] ?>" class="hover:underline text-slate-600 hover:text-brand-600 block transition-colors no-underline">
                    <?= h($h['text']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </details>
        <?php endif; ?>
      <?php endif; ?>
      <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
    </div>
  </div>

</article>
