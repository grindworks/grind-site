<?php

if (!defined('GRINDS_APP')) exit;

/**
 * search.php
 * Render search results.
 */
?>

<header class="page-header">
    <h1 class="page-title">
        <?= theme_t('Search Results for: %s', '<span>' . h($_GET['q'] ?? '') . '</span>') ?>
    </h1>
</header>

<?php if (have_posts()): ?>
    <div class="posts-list">
        <?php while (have_posts()): the_post(); ?>
            <article class="post-entry">
                <header class="entry-header">
                    <h2 class="entry-title">
                        <a href="<?= get_permalink() ?>"><?= the_title() ?></a>
                    </h2>
                    <div class="entry-meta">
                        <span class="posted-on"><?= get_the_date() ?></span>
                    </div>
                </header>

                <div class="entry-summary">
                    <?php
                    $excerpt = (!empty($post['description'])) ? h($post['description']) : get_excerpt($post['content'], 120);
                    if (isset($_GET['q']) && $_GET['q'] !== '') {
                        $q = trim($_GET['q']);
                        // Strip HTML tags.
                        global $post;
                        $plain = strip_tags($post['content']);
                        $pos = mb_stripos($plain, $q, 0, 'UTF-8');
                        if ($pos !== false) {
                            $start = max(0, $pos - 60);
                            $sub = mb_substr($plain, $start, 120, 'UTF-8');
                            // Mark search term.
                            $marker = '[[MARK]]';
                            $endMarker = '[[/MARK]]';
                            $subWithPlaceholders = preg_replace('/(' . preg_quote($q, '/') . ')/iu', $marker . '$1' . $endMarker, $sub);
                            $escaped = h($subWithPlaceholders);
                            $final = str_replace([$marker, $endMarker], ['<mark>', '</mark>'], $escaped);
                            $excerpt = '...' . $final . '...';
                        }
                    }
                    echo $excerpt;
                    ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>

    <?php the_pagination(); ?>

<?php else: ?>
    <div class="no-results">
        <p><?= theme_t('Sorry, but nothing matched your search terms. Please try again with some different keywords.') ?></p>
        <form action="<?= h(resolve_url('/')) ?>" method="get" class="grinds-search-form">
            <input type="text" name="q" placeholder="<?= h(theme_t('Search...')) ?>" value="<?= h($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="<?= theme_t('Search') ?>"><svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                </svg></button>
        </form>
    </div>
<?php endif; ?>
