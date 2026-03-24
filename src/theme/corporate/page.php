<?php

if (!defined('GRINDS_APP')) exit;

/**
 * page.php
 * Render page layout.
 */
?>
<article class="bg-white shadow-sm border border-corp-border rounded-lg overflow-hidden">

  <!-- Hero header. -->
  <?php get_template_part('parts/hero'); ?>

  <header class="p-8 border-gray-100 border-b">
    <?php
    $heroSettings = $pageData['post']['hero_settings_decoded'] ?? json_decode($pageData['post']['hero_settings'] ?? '{}', true);
    $hasHero = !empty($pageData['post']['hero_image']);
    $hasHeroTitle = $hasHero && !empty($heroSettings['title']);
    ?>

    <?php if (!$hasHeroTitle): ?>
      <h1 class="font-bold text-corp-main text-2xl md:text-3xl leading-tight">
        <?= h($pageData['post']['title']) ?>
      </h1>
    <?php endif; ?>
  </header>

  <?php if ($pageData['post']['thumbnail'] && !$hasHero): ?>
    <div class="bg-gray-100 w-full h-64 md:h-96">
      <?= get_image_html($pageData['post']['thumbnail'], ['class' => 'w-full h-full object-cover', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
    </div>
  <?php endif; ?>

  <div class="p-8 md:p-12">
    <div id="post-content" class="max-w-none cms-content prose prose-slate">
      <?php if (!empty($pageData['post']['show_toc'])): ?>
        <?php
        $contentData = $pageData['post']['content_decoded'] ?? [];
        $headers = get_post_toc($contentData);
        ?>
        <?php if (!empty($headers)): ?>
          <details id="toc" open class="mb-10 p-5 border border-slate-200 rounded-lg bg-slate-50 shadow-sm">
            <summary class="font-bold cursor-pointer text-slate-900 mb-3 text-lg outline-none flex items-center">
              <?= h($pageData['post']['toc_title'] ?: theme_t('toc_title', 'Contents')) ?>
            </summary>
            <ul class="space-y-2 list-none m-0 p-0 ml-0">
              <?php foreach ($headers as $h): ?>
                <?php
                $indentClass = 'ml-0 font-bold';
                if ($h['level'] === 3) $indentClass = 'ml-4 font-normal';
                elseif ($h['level'] === 4) $indentClass = 'ml-8 font-normal text-sm';
                elseif ($h['level'] >= 5) $indentClass = 'ml-12 font-normal text-xs';
                ?>
                <li class="<?= $indentClass ?>">
                  <a href="#<?= $h['id'] ?>" class="hover:underline text-slate-700 hover:text-blue-600 block py-0.5 transition-colors no-underline">
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
