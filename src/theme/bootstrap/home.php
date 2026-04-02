<?php

if (!defined('GRINDS_APP')) exit;

/**
 * home.php
 * Display post list.
 */
?>
<!-- Include Hero Header -->
<?php get_template_part('parts/hero'); ?>

<div class="mb-5 pb-3 border-bottom">
  <span class="text-muted text-uppercase small fw-bold"><?= theme_t('Updates') ?></span>
  <h1 class="display-5 fw-bold text-dark">
    <?php
    if (isset($pageType) && $pageType === 'search') {
      echo theme_t('Search results for: %s', h($_GET['q'] ?? ''));
    } else {
      echo theme_t('Latest Posts');
    }
    ?>
  </h1>
</div>

<?php if (empty($pageData['posts'])): ?>
  <div class="text-center alert alert-warning">
    <?= theme_t('No posts found.') ?>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php foreach ($pageData['posts'] as $index => $post): ?>
      <div class="col">
        <div class="hover-shadow shadow-sm border-0 h-100 transition card">
          <a href="<?= h(resolve_url($post['slug'])) ?>" class="d-block bg-light ratio ratio-16x9">
            <?php if ($post['thumbnail']): ?>
              <?php
              $imgAttrs = ['class' => 'card-img-top object-fit-cover', 'alt' => h($post['title'])];
              if ($index < 2) {
                $imgAttrs['loading'] = 'eager';
                $imgAttrs['fetchpriority'] = 'high';
              } else {
                $imgAttrs['loading'] = 'lazy';
              }
              ?>
              <?= get_image_html($post['thumbnail'], $imgAttrs) ?>
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center w-100 h-100 text-muted">
                <i class="bi bi-image fs-1"></i>
              </div>
            <?php endif; ?>
          </a>
          <div class="d-flex flex-column card-body">
            <div class="mb-2 text-muted small">
              <i class="me-1 bi bi-clock"></i><?= the_date($post['published_at'] ?? $post['created_at']) ?>
              <?php if ($post['category_name']): ?>
                <span class="mx-2">/</span>
                <span class="bg-secondary badge"><?= h($post['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <h5 class="card-title fw-bold">
              <a href="<?= h(resolve_url($post['slug'])) ?>" class="text-dark text-decoration-none stretched-link">
                <?= h($post['title']) ?>
              </a>
            </h5>
            <p class="flex-grow-1 text-secondary card-text small">
              <?php
              if (function_exists('bootstrap_get_highlighted_excerpt')) {
                echo bootstrap_get_highlighted_excerpt($post, 80);
              } else {
                echo (!empty($post['description'])) ? h($post['description']) : get_excerpt($post['content'], 80);
              }
              ?>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination controls -->
  <div class="d-flex justify-content-center mt-5">
    <?php
    if (isset($pageData['paginator'])) {
      $paginator = $pageData['paginator'];
      get_template_part('parts/pagination', null, ['paginator' => $paginator]);
    }
    ?>
  </div>
<?php endif; ?>
