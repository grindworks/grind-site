<?php

if (!defined('GRINDS_APP')) exit;

/**
 * single.php
 * Render single post layout.
 */
?>

<!-- Render article container -->
<article class="bg-white shadow-lg mb-10 border border-gray-100 rounded-lg overflow-hidden">
  <header class="p-6 md:p-10 pb-0">
    <!-- Breadcrumbs -->
    <div class="mb-6">
      <?= get_breadcrumb_html([
        'wrapper_class' => 'flex flex-wrap text-sm text-gray-500',
        'item_class'    => 'flex items-center',
        'link_class'    => 'hover:text-grinds-red transition-colors',
        'separator'     => '<svg class="w-3 h-3 mx-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . resolve_url('assets/img/sprite.svg') . '#outline-chevron-right"></use></svg>',
        'active_class'  => 'text-gray-800 font-bold'
      ]) ?>
    </div>
    <!-- Render meta information area -->
    <div class="flex flex-wrap items-center gap-4 mb-4 text-gray-600 text-sm">

      <!-- Display category if enabled -->
      <?php if (!empty($pageData['post']['show_category']) && $pageData['post']['category_name']): ?>
        <a href="<?= h(resolve_url('category/' . $pageData['post']['category_slug'])) ?>"
          class="bg-grinds-red hover:opacity-80 px-2 py-1 rounded font-bold text-white text-xs transition">
          <?= h($pageData['post']['category_name']) ?>
        </a>
      <?php endif; ?>

      <!-- Display date if enabled -->
      <?php if (!empty($pageData['post']['show_date'])): ?>
        <?php
        $pubTs = $pageData['post']['pub_ts'] ?? time();
        $modTs = $pageData['post']['mod_ts'] ?? $pubTs;
        $pubDateStr = $pageData['post']['published_at'] ?? $pageData['post']['created_at'];
        $modDateStr = $pageData['post']['updated_at'] ?? $pubDateStr;
        ?>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
          <time class="flex items-center" datetime="<?= date('c', $pubTs) ?>">
            <svg class="mr-1 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <?= the_date($pubDateStr) ?>
          </time>
          <?php
          $postAuthor = $pageData['post']['hero_settings_decoded']['seo_author'] ?? '';
          if ($postAuthor):
          ?>
            <span class="flex items-center text-gray-500">
              <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
              </svg>
              <span><?= h($postAuthor) ?></span>
            </span>
          <?php endif; ?>
          <?php if ($modTs - $pubTs > 86400): ?>
            <time class="flex items-center text-gray-500" datetime="<?= date('c', $modTs) ?>" title="Updated">
              <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <span class="sr-only sm:not-sr-only sm:mr-1 text-xs opacity-80">
                <?= (grinds_get_current_language() === 'ja') ? '更新:' : 'Updated:' ?>
              </span>
              <?= the_date($modDateStr) ?>
            </time>
          <?php endif; ?>

          <?php
          $headerMetaData = json_decode($pageData['post']['meta_data'] ?? '{}', true);
          if (!empty($headerMetaData['reading_time'])):
          ?>
            <span class="flex items-center text-gray-500">
              <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span><?= h($headerMetaData['reading_time']) ?> <?= theme_t('min read') ?></span>
            </span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['admin_logged_in']) && !isset($_GET['preview'])): ?>
        <!-- Admin edit link if logged in -->
        <a href="<?= h(resolve_url('admin/posts.php?action=edit&id=' . $pageData['post']['id'])) ?>" target="_blank" class="group flex items-center gap-1 bg-gray-800 hover:bg-grinds-red shadow-sm ml-auto px-3 py-1 rounded-full font-bold text-white text-xs transition" aria-label="Edit Post">
          <svg class="w-3 h-3 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
          </svg>
          <?= theme_t('Edit') ?>
        </a>
      <?php endif; ?>
    </div>

    <?php
    $heroSettings = $pageData['post']['hero_settings_decoded'] ?? [];
    $hasHero = !empty($pageData['post']['hero_image']);
    $hasHeroTitle = $hasHero && !empty($heroSettings['title']);
    ?>

    <?php if (!$hasHeroTitle): ?>
      <!-- Render post title -->
      <h1 class="mb-6 font-bold text-grinds-dark text-3xl md:text-4xl leading-tight">
        <?= h($pageData['post']['title']) ?>
      </h1>
    <?php endif; ?>

    <!-- Render thumbnail if exists and no hero image -->
    <?php if ($pageData['post']['thumbnail'] && !$hasHero): ?>
      <div class="-mx-6 md:-mx-10 mb-8">
        <?= get_image_html($pageData['post']['thumbnail'], ['alt' => h($pageData['post']['title']), 'class' => 'w-full h-auto max-h-[500px] object-cover', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
      </div>
    <?php endif; ?>
  </header>

  <div class="px-6 md:px-10 pb-10">
    <!-- Render post content -->
    <div id="post-content" class="mx-auto text-gray-800 leading-relaxed">
      <?php
      $postMeta = json_decode($pageData['post']['meta_data'] ?? '{}', true);
      if (!empty($postMeta['sponsored_link'])):
      ?>
        <div class="mb-8 p-4 bg-gray-50 border border-gray-200 rounded-lg text-center text-sm text-gray-600 flex flex-col sm:flex-row items-center justify-center gap-3">
          <div>
            <span class="inline-block border border-gray-400 text-gray-500 text-[10px] px-1.5 py-0.5 rounded-sm mr-1 font-bold leading-none tracking-wider">PR</span>
            <?= theme_t('This article contains promotional links.') ?>
          </div>
          <a href="<?= h(resolve_url(grinds_url_to_view($postMeta['sponsored_link']))) ?>" target="_blank" rel="noopener sponsored" class="inline-flex items-center text-theme-primary hover:underline font-bold text-sm">
            <?= theme_t('View Sponsored Link') ?>
            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
          </a>
        </div>
      <?php endif; ?>

      <?php if (!empty($pageData['post']['show_toc'])): ?>
        <?php get_template_part('parts/toc'); ?>
      <?php endif; ?>
      <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>

      <?php
      // --- Custom Fields (Meta Data) Output ---
      $metaData = json_decode($pageData['post']['meta_data'] ?? '{}', true);

      // --- Author Note Output ---
      if (!empty($metaData['author_note'])):
      ?>
        <div class="mt-10 p-5 bg-blue-50 border-l-4 border-blue-400 text-blue-800 rounded-r-lg">
          <div class="font-bold text-sm mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            <?= theme_t('Author Note') ?>
          </div>
          <div class="text-sm leading-relaxed">
            <?= nl2br(h($metaData['author_note'])) ?>
          </div>
        </div>
      <?php endif; ?>

      <?php
      // デフォルトテーマで表示対応しているキーのみをチェックし、中身が空でないか判定する
      $displayKeys = ['price', 'event_date', 'product_type', 'cover_image'];
      $hasVisibleMeta = false;
      if (is_array($metaData)) {
        foreach ($displayKeys as $key) {
          if (!empty($metaData[$key])) {
            $hasVisibleMeta = true;
            break;
          }
        }
      }

      // 表示すべき有効なデータがある場合のみ外枠を描画する
      if ($hasVisibleMeta):
      ?>
        <div class="mt-12 p-6 bg-gray-50 border border-gray-200 rounded-xl shadow-sm">
          <h3 class="text-lg font-bold mb-4 text-gray-800 border-b border-gray-200 pb-2"><?= theme_t('Post Details') ?></h3>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <?php if (!empty($metaData['price'])): ?>
              <div>
                <dt class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1"><?= theme_t('Price') ?></dt>
                <dd class="text-gray-900 font-bold"><?= h($metaData['price']) ?></dd>
              </div>
            <?php endif; ?>
            <?php if (!empty($metaData['event_date'])): ?>
              <div>
                <dt class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1"><?= theme_t('Event Date') ?></dt>
                <dd class="text-gray-900 font-bold"><?= h($metaData['event_date']) ?></dd>
              </div>
            <?php endif; ?>
            <?php if (!empty($metaData['product_type'])): ?>
              <div>
                <dt class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1"><?= theme_t('Product Type') ?></dt>
                <dd class="text-gray-900 font-bold"><?= h(theme_t($metaData['product_type'])) ?></dd>
              </div>
            <?php endif; ?>
          </dl>
          <?php if (!empty($metaData['cover_image'])): ?>
            <div class="mt-4">
              <span class="block text-gray-500 text-xs font-bold uppercase tracking-wider mb-2"><?= theme_t('Cover Image') ?></span>
              <img src="<?= h(get_media_url($metaData['cover_image'])) ?>" class="w-48 rounded-lg shadow-sm border border-gray-200" alt="Cover">
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php
      if (function_exists('get_sidebar_widgets')) {
        $widgets = get_sidebar_widgets();
        $profileWidget = null;
        foreach ($widgets as $w) {
          if ($w['type'] === 'profile') {
            $profileWidget = $w;
            break;
          }
        }
        if ($profileWidget) {
          $pSettings = json_decode($profileWidget['settings'] ?? '{}', true);
          $pName = $pSettings['name'] ?? get_option('site_name');
          $pImage = resolve_url($pSettings['image'] ?? '');
          $rawText = $profileWidget['content'] ?? $pSettings['text'] ?? '';
          $resolvedText = function_exists('grinds_url_to_view') ? grinds_url_to_view($rawText) : $rawText;
          $pText = nl2br(h(function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($resolvedText) : strip_tags($resolvedText)));
      ?>
          <div class="mt-16 p-6 bg-gray-50 border border-gray-100 rounded-xl flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <?php if ($pImage): ?>
              <img src="<?= h($pImage) ?>" alt="<?= h($pName) ?>" class="w-20 h-20 rounded-full object-cover shadow-sm shrink-0" loading="lazy">
            <?php else: ?>
              <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 shrink-0">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
                </svg>
              </div>
            <?php endif; ?>
            <div class="text-center sm:text-left">
              <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Written by</div>
              <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h($pName) ?></h3>
              <p class="text-sm text-gray-600 leading-relaxed"><?= $pText ?></p>
              <?php
              $socialLinksRaw = get_option('official_social_links', '');
              $socialLinks = array_filter(array_map('trim', explode("\n", $socialLinksRaw)));
              if (!empty($socialLinks)):
              ?>
                <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-4">
                  <?php foreach ($socialLinks as $link): ?>
                    <a href="<?= h($link) ?>" target="_blank" rel="noopener noreferrer me" class="text-xs font-bold text-grinds-red hover:underline flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-link"></use>
                      </svg>
                      <?= h(parse_url($link, PHP_URL_HOST)) ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
      <?php
        }
      }
      ?>
    </div>
  </div>

  <footer class="bg-gray-50 px-6 md:px-10 py-6 border-gray-100 border-t">
    <!-- Render tags if available -->
    <?php if (!empty($pageData['tags'])): ?>
      <div class="flex flex-wrap gap-2 mb-4">
        <?php foreach ($pageData['tags'] as $tag): ?>
          <a href="<?= h(resolve_url('tag/' . $tag['slug'])) ?>" class="bg-white px-3 py-1 border border-gray-200 hover:border-grinds-red rounded-full text-gray-700 hover:text-grinds-red text-sm transition">
            # <?= h($tag['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <!-- SNS Share Buttons (Conditionally Rendered) -->
    <?php
    $post = $pageData['post'] ?? [];
    if ((!isset($post['show_share_buttons']) || !empty($post['show_share_buttons'])) && function_exists('default_the_share_buttons')) {
      $shareUrl = resolve_url($post['slug']);
      $shareTitle = $post['title'];
      default_the_share_buttons($shareUrl, $shareTitle);
    }
    ?>
    <!-- Render back to home link -->
    <div class="flex justify-between items-center mt-6">
      <a href="<?= h(resolve_url('/')) ?>" class="font-bold text-gray-600 hover:text-grinds-dark text-sm">
        &larr; <?= theme_t('Back to Home') ?>
      </a>
    </div>
  </footer>
</article>
