<?php

/**
 * admin_menu.php
 *
 * Define admin sidebar menu structure.
 */
if (!defined('GRINDS_APP')) exit;

// Get admin menu structure
function get_admin_menu()
{
  $menu = [];

  // Add dashboard
  $menu['dashboard'] = [
    'label' => _t('menu_dashboard'),
    'url'   => './',
    'icon'  => 'outline-home'
  ];

  // Add posts
  $menu['posts'] = [
    'label' => _t('menu_posts'),
    'url'   => 'posts.php',
    'icon'  => 'outline-document-text'
  ];

  // Add Custom Post Types (CPT) dynamically
  if (function_exists('grinds_get_theme_post_types')) {
    $cpts = grinds_get_theme_post_types();
    foreach ($cpts as $slug => $cpt) {
      $menu['cpt_' . $slug] = [
        'label' => function_exists('_t') && isset($cpt['label']) ? _t($cpt['label']) : ($cpt['label'] ?? ucfirst($slug)),
        'url'   => 'posts.php?type=' . urlencode($slug),
        'icon'  => $cpt['icon'] ?? 'outline-document-text'
      ];
    }
  }

  // Add media
  $menu['media'] = [
    'label' => _t('title_media_library'),
    'url'   => 'media.php',
    'icon'  => 'outline-photo'
  ];

  // Add categories and tags
  if (current_user_can('manage_categories')) {
    $menu['categories'] = [
      'label' => _t('menu_categories'),
      'url'   => 'categories.php',
      'icon'  => 'outline-rectangle-stack'
    ];
    $menu['tags'] = [
      'label' => _t('menu_tags'),
      'url'   => 'tags.php',
      'icon'  => 'outline-tag'
    ];
  }

  // Add menus
  if (current_user_can('manage_menus')) {
    $menu['menus'] = [
      'label' => _t('menu_menus'),
      'url'   => 'menus.php',
      'icon'  => 'outline-bars-3'
    ];
  }

  // Add widgets
  if (current_user_can('manage_widgets')) {
    $menu['widgets'] = [
      'label' => _t('menu_widgets'),
      'url'   => 'widgets.php',
      'icon'  => 'outline-squares-plus'
    ];
  }

  // Add banners
  if (current_user_can('manage_banners')) {
    $menu['banners'] = [
      'label' => _t('menu_banners'),
      'url'   => 'banners.php',
      'icon'  => 'outline-megaphone'
    ];
  }

  // Add tools
  if (current_user_can('manage_tools')) {
    $menu['static_export'] = [
      'label' => _t('ssg_title'),
      'url'   => 'static_export.php',
      'icon'  => 'outline-cube-transparent'
    ];

    // Add migration checklist
    $menu['migration_check'] = [
      'label' => _t('menu_migration_check'),
      'url'   => 'migration_checklist.php',
      'icon'  => 'outline-wrench-screwdriver'
    ];

    // Add link checker
    $menu['link_checker'] = [
      'label' => _t('menu_link_checker'),
      'url'   => 'link_checker.php',
      'icon'  => 'outline-link'
    ];

    // Add unused uploads
    $menu['unused_uploads'] = [
      'label' => _t('mt_unused_uploads'),
      'url'   => 'check_unused_uploads.php',
      'icon'  => 'outline-trash'
    ];
  }

  // Add settings or profile
  if (current_user_can('manage_settings')) {
    $menu['settings'] = [
      'label' => _t('menu_settings'),
      'url'   => 'settings.php',
      'icon'  => 'outline-cog-6-tooth'
    ];
  } else {
    // Link to profile for limited users
    $menu['settings'] = [
      'label' => _t('st_profile_title'),
      'url'   => 'settings.php?tab=profile',
      'icon'  => 'outline-user-circle'
    ];
  }

  // Apply filters
  $menu = apply_filters('grinds_admin_menu', $menu);

  return $menu;
}
