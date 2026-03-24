<?php

if (!defined('GRINDS_APP')) exit;

/**
 * single.php
 * Render single post layout.
 */
?>
<article class="bg-white shadow-sm mb-5 p-4 p-md-5 border rounded">

  <!-- Include Hero Header -->
  <?php get_template_part('parts/hero'); ?>

  <div class="container mt-4">
    <?= get_breadcrumb_html([
      'wrapper_class' => 'breadcrumb',
      'item_class'    => 'breadcrumb-item',
      'link_class'    => 'text-decoration-none',
      'active_class'  => 'active',
      'separator'     => ''
    ]) ?>
  </div>

  <header class="mb-5 text-center">
    <div class="mb-3">
      <span class="bg-danger badge"><?= h($pageData['post']['category_name'] ?? theme_t('Uncategorized')) ?></span>
      <span class="ms-2 text-muted small">
        <i class="bi bi-clock"></i> <?= the_date($pageData['post']['published_at'] ?? $pageData['post']['created_at']) ?>
      </span>
    </div>

    <?php
    $heroSettings = $pageData['post']['hero_settings_decoded'] ?? json_decode($pageData['post']['hero_settings'] ?? '{}', true);
    $hasHero = !empty($pageData['post']['hero_image']);
    $hasHeroTitle = $hasHero && !empty($heroSettings['title']);
    ?>

    <?php if (!$hasHeroTitle): ?>
      <h1 class="mb-4 display-5 fw-bold"><?= h($pageData['post']['title']) ?></h1>
    <?php endif; ?>

    <?php if ($pageData['post']['thumbnail'] && !$hasHero): ?>
      <?= get_image_html($pageData['post']['thumbnail'], ['class' => 'mb-4 rounded w-100 img-fluid', 'alt' => h($pageData['post']['title']), 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
    <?php endif; ?>
  </header>

  <div id="post-content" class="post-content">
    <?php if (!empty($pageData['post']['show_toc'])): ?>
      <?php
      $contentData = $pageData['post']['content_decoded'] ?? [];
      $headers = get_post_toc($contentData);
      ?>
      <?php if (!empty($headers)): ?>
        <div class="card mb-5 bg-light border-0">
          <div class="card-body">
            <h5 class="card-title fw-bold mb-3"><?= h($pageData['post']['toc_title'] ?: theme_t('Contents')) ?></h5>
            <ul class="list-unstyled m-0">
              <?php foreach ($headers as $h): ?>
                <li class="mb-2" style="<?= ($h['level'] > 2) ? 'padding-left: ' . (($h['level'] - 2) * 1) . 'rem;' : '' ?>">
                  <a href="#<?= $h['id'] ?>" class="text-decoration-none text-secondary hover-primary">
                    <?= h($h['text']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
  </div>

  <footer class="mt-5 pt-4 border-top">
    <?php
    $post = $pageData['post'] ?? [];
    if ((!isset($post['show_share_buttons']) || !empty($post['show_share_buttons'])) && function_exists('bootstrap_the_share_buttons')) {
      $shareUrl = resolve_url($post['slug']);
      $shareTitle = $post['title'];
      bootstrap_the_share_buttons($shareUrl, $shareTitle);
    }
    ?>
    <div class="mt-4 text-center">
      <a href="<?= h(resolve_url('/')) ?>" class="px-4 rounded-pill btn-outline-secondary btn">
        <i class="bi-arrow-left me-2 bi"></i><?= theme_t('Back to Home') ?>
      </a>
    </div>
  </footer>
</article>
