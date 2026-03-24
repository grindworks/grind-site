<?php

if (!defined('GRINDS_APP')) exit;

/**
 * 404.php
 * Display 404 Not Found error.
 */
?>
<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center min-vh-50">
  <div class="display-1 fw-bold text-secondary mb-3 opacity-25">404</div>
  <h1 class="h2 mb-3"><?= theme_t('Page Not Found') ?></h1>
  <p class="text-muted mb-5 lead">
    <?= theme_t('The page you are looking for does not exist.') ?>
  </p>

  <a href="<?= h(resolve_url('/')) ?>" class="btn btn-primary rounded-pill px-5 shadow-sm">
    <i class="bi bi-house-door me-2"></i><?= theme_t('Back to Home') ?>
  </a>
</div>
