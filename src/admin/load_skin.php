<?php

/**
 * load_skin.php
 *
 * Load admin skin settings.
 */
if (!defined('GRINDS_APP'))
  exit;

// Define defaults
$defaults = require __DIR__ . '/config/skin_defaults.php';

// Determine which skin to use (user preference vs global setting)
$currentSkin = get_option('admin_skin', 'default');
$currentUser = App::user();
if ($currentUser && isset($currentUser['id'])) {
  $userSkin = $_SESSION['admin_skin'] ?? null;
  if (!empty($userSkin) && $userSkin !== 'system') {
    $currentSkin = $userSkin;
  }
}

$skinData = [];

// Load custom skin
if ($currentSkin === 'custom') {
  // Map DB options
  foreach ($defaults as $key => $val) {
    if ($key === 'colors' && is_array($val)) {
      if (!isset($skinData['colors']))
        $skinData['colors'] = [];
      foreach ($val as $cKey => $cVal) {
        // Convert keys
        $dbKey = grinds_generate_skin_key($cKey, 'colors');
        $skinData['colors'][$cKey] = get_option($dbKey, $cVal);
      }
    } elseif ($key !== 'css') {
      $dbKey = grinds_generate_skin_key($key);

      $optionVal = get_option($dbKey, $val);
      if (in_array($key, ['font_url', 'media_bg_css'])) {
        $optionVal = grinds_url_to_view($optionVal);
      }
      $skinData[$key] = $optionVal;
    }
  }
}
// Load predefined skin
else {
  // Sanitize name
  $safeSkinName = basename($currentSkin);
  if (!preg_match('/^[a-zA-Z0-9_-]+$/', $safeSkinName)) {
    $safeSkinName = 'default';
  }

  $skinDir = defined('ROOT_PATH') ? ROOT_PATH . '/admin/skins/' : __DIR__ . '/skins/';
  $skinFile = $skinDir . $safeSkinName . '.json';

  // Fallback to default
  if (!file_exists($skinFile)) {
    // default.json is loaded in skin_defaults.php later, so we skip it here.
    $safeSkinName = 'default';
  }

  // Load skin file
  if ($safeSkinName !== 'default' && file_exists($skinFile)) {
    $jsonContent = file_get_contents($skinFile);
    $loaded = json_decode($jsonContent, true);
    if (is_array($loaded)) {
      $skinData = $loaded;
      if (isset($skinData['font_url']))
        $skinData['font_url'] = grinds_url_to_view($skinData['font_url']);
      if (isset($skinData['media_bg_css']))
        $skinData['media_bg_css'] = grinds_url_to_view($skinData['media_bg_css']);
    }
  }
}

// Merge settings
$finalSkin = array_merge($defaults, $skinData);

// Clear raw CSS
$finalSkin['css'] = '';

// Merge colors
$finalSkin['colors'] = array_merge($defaults['colors'], $skinData['colors'] ?? []);

// Load assets
$assetsMap = [];
$assetsFile = __DIR__ . '/config/skin_assets.php';
if (file_exists($assetsFile)) {
  $assetsMap = require $assetsFile;
}

// Resolve texture
if (!empty($finalSkin['texture']) && isset($assetsMap['textures'][$finalSkin['texture']])) {
  $finalSkin['texture'] = $assetsMap['textures'][$finalSkin['texture']];
} else {
  $finalSkin['texture'] = '';
}

// Resolve media background
if (!empty($finalSkin['media_bg_css']) && isset($assetsMap['media_backgrounds'][$finalSkin['media_bg_css']])) {
  $finalSkin['media_bg_css'] = $assetsMap['media_backgrounds'][$finalSkin['media_bg_css']];
} else {
  // Generate checkerboard
  $c1 = $finalSkin['colors']['media_checker_1'] ?? '#e2e8f0';
  $c2 = $finalSkin['colors']['media_checker_2'] ?? '#f8fafc';
  $finalSkin['media_bg_css'] = "
    background-color: {$c2};
    background-image:
      linear-gradient(45deg, {$c1} 25%, transparent 25%),
      linear-gradient(-45deg, {$c1} 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, {$c1} 75%),
      linear-gradient(-45deg, transparent 75%, {$c1} 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    ";
}

// Process effects
if (isset($finalSkin['effects']) && is_array($finalSkin['effects'])) {
  $effectsMap = [];
  $effectsFile = __DIR__ . '/config/skin_effects.php';
  if (file_exists($effectsFile)) {
    $effectsMap = require $effectsFile;
  }

  $extraCss = '';
  foreach ($finalSkin['effects'] as $effect) {
    if (isset($effectsMap[$effect])) {
      $extraCss .= $effectsMap[$effect] . "\n";
    }
  }

  if (!empty($extraCss)) {
    $finalSkin['css'] = ($finalSkin['css'] ?? '') . "\n" . $extraCss;
  }
}

// Calculate derived values
if (!isset($finalSkin['is_dark'])) {
  $bg_rgb = grinds_normalize_color($finalSkin['colors']['bg'] ?? '#f8fafc');
  $finalSkin['is_dark'] = is_dark($bg_rgb);
}

if (!isset($finalSkin['is_sidebar_dark'])) {
  $sidebar_rgb = grinds_normalize_color($finalSkin['colors']['sidebar'] ?? $finalSkin['colors']['surface'] ?? '#1e293b');
  $finalSkin['is_sidebar_dark'] = is_dark($sidebar_rgb);
}

return $finalSkin;
