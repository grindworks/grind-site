<?php

/**
 * home.php
 * Render homepage.
 */
if (!defined('GRINDS_APP')) exit;
$isSearch = (isset($pageType) && $pageType === 'search');
?>
<div class="w-full">

  <?php if (!$isSearch): ?>
    <!-- Hero Section. -->
    <section class="relative bg-corp-main -mt-12 py-20 md:py-32 overflow-hidden text-white">

      <?php
      $bgImage = 'theme/corporate/img/hero-bg.jpg';
      $bgStyle = '';

      $hConf = json_decode($pageData['post']['hero_settings'] ?? '{}', true);
      $heroTitle = $hConf['title'] ?? theme_t('hero_title', 'Next Generation<br><span class="text-blue-400">Corporate Solution</span>');
      $heroDesc = $hConf['subtext'] ?? theme_t('hero_desc', 'Empowering your business with reliable technology and proven track record.');

      // Set hero image.
      if (!empty($pageData['post']['hero_image'])) {
        $bgStyle = "background-image: url('" . resolve_url($pageData['post']['hero_image']) . "');";
      } elseif (file_exists(ROOT_PATH . '/' . $bgImage)) {
        $bgStyle = "background-image: url('" . resolve_url($bgImage) . "');";
      } else {
        $bgStyle = "background-image: radial-gradient(#334155 1px, transparent 1px); background-size: 20px 20px;";
      }
      ?>
      <div class="absolute inset-0 bg-cover bg-center opacity-30" style="<?= $bgStyle ?>"></div>

      <div class="absolute inset-0 bg-gradient-to-r from-corp-main/95 to-corp-main/60"></div>

      <div class="z-10 relative mx-auto px-4 container">
        <div class="max-w-3xl animate-fade-in-up">
          <span class="inline-block bg-blue-900/50 mb-6 px-3 py-1 border border-blue-500/50 rounded-full font-bold text-blue-200 text-xs tracking-wider">
            <?= theme_t('hero_badge', 'RELIABLE & TRUSTED') ?>
          </span>
          <h2 class="mb-6 font-bold text-white text-4xl md:text-6xl leading-tight tracking-tight">
            <?= $heroTitle ?>
          </h2>
          <p class="mb-10 max-w-2xl text-slate-300 text-lg leading-relaxed">
            <?= nl2br($heroDesc) ?>
          </p>
          <div class="flex flex-wrap gap-4">
            <?php if (!empty($hConf['buttons'])): ?>
              <?php foreach ($hConf['buttons'] as $btn):
                $bStyle = ($btn['style'] ?? 'primary') === 'outline'
                  ? 'hover:bg-white border border-white text-white hover:!text-slate-900'
                  : 'bg-corp-accent hover:opacity-90 shadow-lg text-white hover:!text-white';
              ?>
                <a href="<?= h(resolve_url($btn['url'])) ?>" class="inline-flex justify-center items-center px-8 py-3.5 rounded font-bold text-sm transition-colors <?= $bStyle ?>">
                  <?= h($btn['text']) ?>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <a href="<?= h(resolve_url('contact')) ?>" class="inline-flex justify-center items-center bg-corp-accent hover:opacity-90 shadow-lg px-8 py-3.5 rounded font-bold text-white hover:!text-white text-sm transition-colors">
                <?= theme_t('btn_contact', 'Contact Us') ?>
              </a>
              <a href="#services" class="inline-flex justify-center items-center hover:bg-white px-8 py-3.5 border border-white rounded font-bold text-white hover:!text-slate-900 text-sm transition-colors">
                <?= theme_t('btn_services', 'Our Services') ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Section. -->
    <section id="services" class="z-10 relative bg-white py-24">
      <div class="mx-auto px-4 container">
        <div class="mx-auto mb-16 max-w-2xl text-center">
          <h2 class="mb-4 font-bold text-slate-900 text-3xl tracking-tight"><?= theme_t('services_title', 'Our Services') ?></h2>
          <p class="text-slate-600 leading-relaxed">
            <?= theme_t('services_desc', 'We provide three solutions to accelerate your business based on the latest technology and years of experience.') ?>
          </p>
        </div>

        <div class="gap-8 grid grid-cols-1 md:grid-cols-3">
          <!-- Service 1. -->
          <div class="group flex flex-col bg-white hover:shadow-xl p-8 border border-slate-200 hover:border-corp-accent rounded-xl h-full transition-all duration-300">
            <div class="flex justify-center items-center bg-blue-50 group-hover:bg-corp-accent mb-6 rounded-lg w-14 h-14 text-corp-accent group-hover:text-white transition-colors duration-300">
              <svg class="w-7 h-7 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="mb-3 font-bold text-slate-900 group-hover:text-corp-accent text-xl transition-colors"><?= theme_t('service_1_title', 'IT Consulting') ?></h3>
            <p class="flex-grow mb-6 text-slate-600 text-sm leading-relaxed">
              <?= theme_t('service_1_desc', 'Solving management issues with digital technology. We support everything from strategy planning to system implementation and operation.') ?>
            </p>
            <a href="#" class="flex items-center gap-1 group-hover:gap-2 hover:opacity-80 mt-auto font-bold text-corp-accent text-sm transition-all" aria-label="<?= h(sprintf(theme_t('link_learn_more_aria'), theme_t('service_1_title'))) ?>">
              <?= theme_t('link_learn_more', 'Learn more') ?> <span aria-hidden="true">&rarr;</span>
            </a>
          </div>

          <!-- Service 2. -->
          <div class="group flex flex-col bg-white hover:shadow-xl p-8 border border-slate-200 hover:border-corp-accent rounded-xl h-full transition-all duration-300">
            <div class="flex justify-center items-center bg-blue-50 group-hover:bg-corp-accent mb-6 rounded-lg w-14 h-14 text-corp-accent group-hover:text-white transition-colors duration-300">
              <svg class="w-7 h-7 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
              </svg>
            </div>
            <h3 class="mb-3 font-bold text-slate-900 group-hover:text-corp-accent text-xl transition-colors"><?= theme_t('service_2_title', 'Marketing') ?></h3>
            <p class="flex-grow mb-6 text-slate-600 text-sm leading-relaxed">
              <?= theme_t('service_2_desc', 'Data-driven marketing strategies. Maximize customer attraction through SEO, ad operations, and SNS utilization.') ?>
            </p>
            <a href="#" class="flex items-center gap-1 group-hover:gap-2 hover:opacity-80 mt-auto font-bold text-corp-accent text-sm transition-all" aria-label="<?= h(sprintf(theme_t('link_learn_more_aria'), theme_t('service_2_title'))) ?>">
              <?= theme_t('link_learn_more', 'Learn more') ?> <span aria-hidden="true">&rarr;</span>
            </a>
          </div>

          <!-- Service 3. -->
          <div class="group flex flex-col bg-white hover:shadow-xl p-8 border border-slate-200 hover:border-corp-accent rounded-xl h-full transition-all duration-300">
            <div class="flex justify-center items-center bg-blue-50 group-hover:bg-corp-accent mb-6 rounded-lg w-14 h-14 text-corp-accent group-hover:text-white transition-colors duration-300">
              <svg class="w-7 h-7 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <h3 class="mb-3 font-bold text-slate-900 group-hover:text-corp-accent text-xl transition-colors"><?= theme_t('service_3_title', 'HR Support') ?></h3>
            <p class="flex-grow mb-6 text-slate-600 text-sm leading-relaxed">
              <?= theme_t('service_3_desc', 'Human resource development and recruitment support to sustain organizational growth. We also handle remote work environment setup.') ?>
            </p>
            <a href="#" class="flex items-center gap-1 group-hover:gap-2 hover:opacity-80 mt-auto font-bold text-corp-accent text-sm transition-all" aria-label="<?= h(sprintf(theme_t('link_learn_more_aria'), theme_t('service_3_title'))) ?>">
              <?= theme_t('link_learn_more', 'Learn more') ?> <span aria-hidden="true">&rarr;</span>
            </a>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- News Section. -->
  <section class="bg-slate-50 py-24 border-slate-200 border-t">
    <div class="mx-auto px-4 container">
      <div class="flex justify-between items-center mb-8 pb-4 border-slate-200 border-b">
        <h2 class="flex items-center gap-3 font-bold text-slate-900 text-2xl">
          <span class="block bg-corp-accent rounded-full w-1.5 h-8"></span>
          <?= $isSearch ? theme_t('search_results') : theme_t('latest_news') ?>
        </h2>
        <?php if (!$isSearch): ?>
          <a href="<?= h(resolve_url('category/news')) ?>" class="flex items-center gap-1 bg-white hover:opacity-80 shadow-sm hover:shadow px-4 py-2 border border-slate-200 rounded-full font-bold text-corp-accent text-sm transition-colors" aria-label="<?= theme_t('view_all_news_aria') ?>">
            <?= theme_t('view_all') ?> <span aria-hidden="true">&rarr;</span>
          </a>
        <?php endif; ?>
      </div>

      <?php if (!empty($pageData['posts'])): ?>
        <div class="bg-white shadow-sm border border-slate-200 rounded-xl divide-y divide-slate-100 overflow-hidden">
          <?php foreach ($pageData['posts'] as $post): ?>
            <article class="group flex sm:flex-row flex-col sm:items-center gap-3 sm:gap-6 hover:bg-blue-50/50 p-5 transition">
              <div class="min-w-[90px] font-mono text-slate-500 text-xs whitespace-nowrap">
                <?= date('Y.m.d', strtotime($post['published_at'])) ?>
              </div>
              <?php if ($post['category_name']): ?>
                <span class="bg-slate-100 px-2.5 py-0.5 border border-slate-200 rounded-full font-bold text-[10px] text-slate-600 uppercase whitespace-nowrap">
                  <?= h($post['category_name']) ?>
                </span>
              <?php endif; ?>
              <h3 class="flex-grow min-w-0">
                <a href="<?= h(resolve_url($post['slug'])) ?>" class="block font-bold text-slate-800 group-hover:text-corp-accent text-sm line-clamp-1 transition-colors">
                  <?= h($post['title']) ?>
                </a>
              </h3>
              <span class="hidden sm:block text-slate-300 group-hover:text-corp-accent transition-colors">&rarr;</span>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <!-- Empty state. -->
        <div class="bg-white shadow-sm p-12 border border-slate-200 rounded-xl text-center">
          <p class="mb-2 text-slate-500 text-sm"><?= theme_t('no_news', 'No news available.') ?></p>
          <p class="text-slate-400 text-xs"><?= theme_t('no_news_desc', 'Posts will appear here once you publish them from the admin panel.') ?></p>
        </div>
      <?php endif; ?>
    </div>
  </section>

</div>
