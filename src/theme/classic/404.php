<?php

if (!defined('GRINDS_APP')) exit;

/**
 * 404.php
 * Display 404 Not Found error.
 */
?>
<div class="error-404 not-found">
    <header class="page-header">
        <h1 class="page-title"><?= theme_t('404 Not Found') ?></h1>
    </header>

    <div class="page-content">
        <p><?= theme_t('It looks like nothing was found at this location. Maybe try a search?') ?></p>

        <div class="search-form-container">
            <form action="<?= h(resolve_url('/')) ?>" method="get" class="grinds-search-form">
                <input type="text" name="q" placeholder="<?= theme_t('Search...') ?>">
                <button type="submit" aria-label="<?= theme_t('Search') ?>"><svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                    </svg></button>
            </form>
        </div>

        <div class="back-home">
            <a href="<?= site_url() ?>"><?= theme_t('Back to Home') ?></a>
        </div>
    </div>
</div>
