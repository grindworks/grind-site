<?php

/**
 * header.php
 *
 * Render the <head> section and initialize global JavaScript data.
 */
if (!defined('GRINDS_APP'))
  exit;

// Get current user info
$appUser = App::user();
$currentUser = [
  'username' => $appUser['username'] ?? 'Admin',
  'avatar' => $appUser['avatar'] ?? ''
];

$skin = require __DIR__ . '/../load_skin.php';

global $pdo;

// Check system status
clearstatcache(true, ROOT_PATH . '/install.php');
$installer_exists = file_exists(ROOT_PATH . '/install.php');
$sysStatus = get_system_status();
$licStatus = get_license_status();

// Check for updates
$hasUpdate = false;
$latestVersion = get_option('latest_version');
if ($latestVersion && version_compare($latestVersion, CMS_VERSION, '>')) {
  $hasUpdate = true;
}

// If installer exists, force danger status regardless of session cache
if ($installer_exists && $sysStatus['status'] !== 'danger') {
  $sysStatus = ['status' => 'danger', 'msg' => _t('chk_install_file')];
}

// Prepare search data
$search_items = [];
foreach ($admin_menu as $key => $item) {
  $search_items[] = ['title' => $item['label'], 'url' => $item['url'], 'type' => 'Menu', 'icon' => $item['icon']];
}
$search_items[] = ['title' => _t('view_site'), 'url' => resolve_url('/'), 'type' => 'Action', 'icon' => 'outline-arrow-top-right-on-square'];
$search_items[] = ['title' => _t('logout'), 'url' => 'logout.php', 'type' => 'Action', 'icon' => 'outline-arrow-right-on-rectangle'];

$search_json = json_encode($search_items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

$alpineSearchData = <<<'JS'
{
    searchOpen: false,
    mobileOpen: false,
    searchQuery: '',
    selectedIndex: -1,
    items: window.grindsSearchItems || [],
    searchResults: [],
    shortcutLabel: navigator.userAgent.indexOf('Mac') !== -1 ? '⌘K' : 'Ctrl K',
    fetchTimer: null,

    get filteredItems() {
        if (this.searchQuery === '') return this.items.slice(0, 10);
        const staticResults = this.items.filter(i => i.title.toLowerCase().includes(this.searchQuery.toLowerCase()));
        return [...staticResults, ...this.searchResults];
    },

    init() {
        this.$watch('searchQuery', (val) => this.performSearch(val));
    },

    performSearch(query) {
        if (this.fetchTimer) clearTimeout(this.fetchTimer);
        if (!query) {
            this.searchResults = [];
            return;
        }
        this.fetchTimer = setTimeout(() => {
            fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/post_search.php?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    const list = Array.isArray(data) ? data : (data.data || []);
                    this.searchResults = list.map(p => ({
                        title: p.title,
                        url: 'posts.php?action=edit&id=' + p.id,
                        type: p.type === 'page' ? 'Page' : 'Post',
                        icon: p.type === 'page' ? 'outline-document' : 'outline-document-text'
                    }));
                }).catch(() => {});
        }, 300);
    },

    move(direction) {
        const listLen = this.filteredItems.length;
        const maxIndex = listLen;

        let nextIndex = this.selectedIndex + direction;

        if (nextIndex < -1) nextIndex = maxIndex;
        if (nextIndex > maxIndex) nextIndex = -1;

        this.focusItem(nextIndex);
    },

    focusItem(index) {
        this.selectedIndex = index;
        this.$nextTick(() => {
            if (index === -1) {
                this.$refs.searchInput.focus();
            } else if (index === this.filteredItems.length) {
                const footerBtn = document.getElementById('search-footer-create');
                if (footerBtn) {
                    footerBtn.focus();
                    footerBtn.scrollIntoView({ block: 'nearest' });
                }
            } else {
                const el = document.getElementById('search-item-' + index);
                if (el) {
                    el.focus();
                    el.scrollIntoView({ block: 'nearest' });
                }
            }
        });
    },

    reset() {
        this.searchQuery = '';
        this.selectedIndex = -1;
    }
}
JS;

// Determine color scheme
$colors = $skin['colors'] ?? [];
$is_dark_mode = $skin['is_dark'] ?? false;
$color_scheme = $is_dark_mode ? 'dark' : 'light';
$is_sidebar_dark = $skin['is_sidebar_dark'] ?? true;

// Check if date picker is needed
$load_flatpickr = (isset($current_page) &&
  ($current_page === 'posts' && isset($action) && $action !== 'list')
);

// Language detection
$lang = grinds_detect_language();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Set color-scheme meta tag -->
  <meta name="color-scheme" content="<?= $color_scheme ?>">

  <!-- Block Search Engines and AI Crawlers -->
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">

  <title>
    <?= h($page_title ?? 'Admin') ?> |
    <?= h(get_option('site_name', SITE_NAME)) ?>
  </title>

  <link rel="icon" href="<?= h(get_favicon_url()) ?>">

  <?php require __DIR__ . '/assets_loader.php'; ?>

  <?php if ($load_flatpickr): ?>
    <?php if (get_option('disable_external_assets') && file_exists(ROOT_PATH . '/assets/js/vendor/flatpickr.min.js') && file_exists(ROOT_PATH . '/assets/css/vendor/flatpickr.min.css')): ?>
      <link rel="stylesheet" href="<?= grinds_asset_url('assets/css/vendor/flatpickr.min.css') ?>">
      <script src="<?= grinds_asset_url('assets/js/vendor/flatpickr.min.js') ?>"></script>
      <?php if ($lang === 'ja'): ?>
        <script src="<?= grinds_asset_url('assets/js/vendor/flatpickr_ja.js') ?>"></script>
      <?php endif; ?>
    <?php else: ?>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
      <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
      <?php if ($lang === 'ja'): ?>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
      <?php endif; ?>
    <?php endif; ?>
  <?php
  endif; ?>

  <script>
    window.grindsBaseUrl = <?= json_encode(resolve_url('/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    window.grindsLang = <?= json_encode($lang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    window.grindsSearchItems = <?= $search_json ?>;
    window.grindsDebug = <?= (defined('DEBUG_MODE') && DEBUG_MODE) ? 'true' : 'false' ?>;
    window.grindsTranslations = {
      select_action: <?= json_encode(_t('err_select_action'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
      no_items: <?= json_encode(_t('no_items_selected'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
      confirm_delete: <?= json_encode(_t('confirm_delete'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
      select_category: <?= json_encode(_t('lbl_select_category'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
      error: <?= json_encode(_t('js_error'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
      loading: <?= json_encode(_t('js_loading'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
      more: <?= json_encode(_t('more'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>
    };
    window.grindsTrans = window.grindsTranslations;
  </script>
  <script src="<?= grinds_asset_url('assets/js/admin_list_actions.js') ?>" defer></script>

  <?php if (!empty($media_bg_css)):
    echo '<style>.bg-checker { ' . strip_tags($media_bg_css) . ' }</style>';
  endif; ?>

  <?php if ($load_flatpickr): ?>
    <style>
      .flatpickr-calendar {
        background: rgb(var(--color-surface) / var(--color-surface-alpha, 1)) !important;
        border: var(--border-width) solid rgb(var(--color-border) / var(--color-border-alpha, 1)) !important;
        box-shadow: var(--box-shadow) !important;
        border-radius: var(--border-radius) !important;
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
      }

      .flatpickr-calendar.arrowTop:before,
      .flatpickr-calendar.arrowTop:after {
        border-bottom-color: rgb(var(--color-border) / var(--color-border-alpha, 1)) !important;
      }

      .flatpickr-calendar.arrowBottom:before,
      .flatpickr-calendar.arrowBottom:after {
        border-top-color: rgb(var(--color-border) / var(--color-border-alpha, 1)) !important;
      }

      .flatpickr-month {
        background: rgb(var(--color-bg) / var(--color-bg-alpha, 1)) !important;
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
        fill: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
        border-bottom: 1px solid rgb(var(--color-border) / var(--color-border-alpha, 1)) !important;
        border-top-left-radius: var(--border-radius);
        border-top-right-radius: var(--border-radius);
      }

      .flatpickr-current-month input.cur-year {
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
        font-weight: bold !important;
      }

      .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: rgb(var(--color-bg) / var(--color-bg-alpha, 1)) !important;
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
      }

      .flatpickr-weekday {
        background: rgb(var(--color-surface) / var(--color-surface-alpha, 1)) !important;
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
        opacity: 0.6;
      }

      .flatpickr-day {
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
        border-radius: var(--border-radius) !important;
      }

      .flatpickr-day:hover,
      .flatpickr-day:focus {
        background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)) !important;
        border-color: transparent !important;
      }

      .flatpickr-day.selected,
      .flatpickr-day.startRange,
      .flatpickr-day.endRange,
      .flatpickr-day.selected.inRange,
      .flatpickr-day.startRange.inRange,
      .flatpickr-day.endRange.inRange,
      .flatpickr-day.selected:focus,
      .flatpickr-day.startRange:focus,
      .flatpickr-day.endRange:focus,
      .flatpickr-day.selected:hover,
      .flatpickr-day.startRange:hover,
      .flatpickr-day.endRange:hover,
      .flatpickr-day.selected.prevMonthDay,
      .flatpickr-day.startRange.prevMonthDay,
      .flatpickr-day.endRange.prevMonthDay,
      .flatpickr-day.selected.nextMonthDay,
      .flatpickr-day.startRange.nextMonthDay,
      .flatpickr-day.endRange.nextMonthDay {
        background: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
        color: rgb(var(--color-on-primary) / var(--color-on-primary-alpha, 1)) !important;
        border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
      }

      .flatpickr-day.today {
        border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
      }

      .flatpickr-day.today:hover {
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
        background: transparent !important;
      }

      .flatpickr-time {
        border-top: 1px solid rgb(var(--color-border) / var(--color-border-alpha, 1)) !important;
      }

      .flatpickr-time .flatpickr-time-separator,
      .flatpickr-time .flatpickr-am-pm,
      .flatpickr-time input {
        color: rgb(var(--color-text) / var(--color-text-alpha, 1)) !important;
      }

      .flatpickr-time input:hover,
      .flatpickr-time .flatpickr-am-pm:hover,
      .flatpickr-time input:focus,
      .flatpickr-time .flatpickr-am-pm:focus {
        background: rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.1)) !important;
      }
    </style>
  <?php
  endif; ?>
</head>
