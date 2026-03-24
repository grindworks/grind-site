<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * archive.php
 * Display tag archive.
 */
$isSearch = (isset($pageType) && $pageType === 'search');
?>
<?php if (isset($pageType) && $pageType === 'category'): ?>
  <div class="mb-5 pb-3 border-bottom">
    <span class="bg-secondary mb-2 badge"><?= theme_t('Category') ?></span>
    <h1 class="display-5 fw-bold text-dark">
      <?= h($pageData['category']['name']) ?>
    </h1>
  </div>
<?php elseif ($isSearch): ?>
  <div class="mb-5 pb-3 border-bottom">
    <span class="text-muted text-uppercase small fw-bold"><?= theme_t('Search Results') ?></span>
    <h1 class="display-5 fw-bold text-dark">
      "<?= h($_GET['q'] ?? '') ?>"
    </h1>
  </div>
<?php else: // Tag
?>
  <div class="mb-5 pb-3 border-bottom">
    <span class="text-muted text-uppercase small fw-bold"><?= theme_t('Tag Archive') ?></span>
    <h1 class="display-5 fw-bold text-dark">
      <span class="text-primary">#</span><?= h($pageData['tag']['name'] ?? '') ?>
    </h1>
  </div>
<?php endif; ?>

<?php if (empty($pageData['posts'])): ?>
  <div class="text-center alert alert-light border">
    <?= theme_t('No posts found.') ?>
  </div>
  <div class="text-center mt-4">
    <a href="<?= h(resolve_url('/')) ?>" class="btn btn-outline-secondary">
      <?= theme_t('Back to Home') ?>
    </a>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <?php foreach ($pageData['posts'] as $post): ?>
      <div class="col">
        <div class="hover-shadow shadow-sm border-0 h-100 transition card">
          <a href="<?= h(resolve_url($post['slug'])) ?>" class="d-block bg-light ratio ratio-16x9">
            <?php if ($post['thumbnail']): ?>
              <?= get_image_html($post['thumbnail'], ['class' => 'card-img-top object-fit-cover', 'alt' => h($post['title']), 'loading' => 'lazy']) ?>
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
              <?= (!empty($post['description'])) ? h($post['description']) : h(get_excerpt($post['content'], 80)) ?>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination controls -->
  <div class="d-flex justify-content-center mt-5">
    <?php if (isset($pageData['paginator'])) echo $pageData['paginator']->renderFrontend(); ?>
  </div>
<?php endif; ?>
