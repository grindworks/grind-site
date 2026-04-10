<?php

/**
 * theme.php
 * Renders the theme settings interface.
 */
if (!defined('GRINDS_APP'))
  exit;

?>
<div class="space-y-6">

  <!-- ========== Section 1: Theme & Layout ========== -->
  <div class="bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
    <form method="post" class="warn-on-unsaved" x-data="{
      currentTheme: <?= htmlspecialchars(json_encode($opt['theme'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
      selectedTheme: <?= htmlspecialchars(json_encode($opt['theme'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
      selectedSkin: <?= htmlspecialchars(json_encode($opt['skin'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
      skinDirty: false
    }"
      @submit="if(currentTheme !== selectedTheme && !confirm(<?= htmlspecialchars(json_encode(_t('msg_theme_change_confirm')), ENT_QUOTES) ?>)) { $event.preventDefault(); } else { skinDirty = false; }">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <!-- Default action for implicit submission -->
      <button type="submit" name="action" value="update_theme" class="hidden" aria-hidden="true"></button>

      <div class="mb-8">
        <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
          <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-swatch"></use>
          </svg>
          <?= _t('tab_theme') ?>
        </h3>
        <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
          <?= _t('st_theme_desc') ?>
        </p>
      </div>

      <!-- Setup Selectors -->
      <div class="gap-6 grid grid-cols-1 md:grid-cols-3 mb-8">
        <label
          class="bg-theme-bg/50 hover:border-theme-primary/30 p-5 border border-theme-border/50 rounded-theme transition-all">
          <span class="flex items-center gap-2 mb-3 font-bold text-theme-text text-sm">
            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-globe-alt"></use>
            </svg>
            <?= _t('st_theme_site') ?>
          </span>
          <select name="site_theme" class="shadow-theme form-control" x-model="selectedTheme">
            <?php foreach ($available_site_themes as $dir => $name): ?>
              <option value="<?= h($dir) ?>">
                <?= h($name) ?>
              </option>
            <?php
            endforeach; ?>
          </select>
        </label>

        <label
          class="bg-theme-bg/50 hover:border-theme-primary/30 p-5 border border-theme-border/50 rounded-theme transition-all">
          <span class="flex items-center gap-2 mb-3 font-bold text-theme-text text-sm">
            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-2x2"></use>
            </svg>
            <?= _t('st_layout_admin') ?>
          </span>
          <select name="admin_layout" class="shadow-theme form-control">
            <?php foreach ($available_layouts as $key => $label): ?>
              <option value="<?= h($key) ?>" <?= get_option('admin_layout', 'sidebar') === $key ? 'selected' : '' ?>>
                <?= h($label) ?>
              </option>
            <?php
            endforeach; ?>
          </select>
        </label>

        <label
          class="bg-theme-bg/50 hover:border-theme-primary/30 p-5 border border-theme-border/50 rounded-theme transition-all">
          <span class="flex items-center gap-2 mb-3 font-bold text-theme-text text-sm">
            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-paint-brush"></use>
            </svg>
            <?= _t('st_skin_admin') ?>
          </span>
          <select name="admin_skin" x-model="selectedSkin" class="shadow-theme form-control">
            <?php foreach ($available_admin_skins as $key => $label): ?>
              <option value="<?= h($key) ?>">
                <?= h($label) ?>
              </option>
            <?php
            endforeach; ?>
          </select>
        </label>
      </div>

      <!-- Advanced / Environment Settings -->
      <div class="mb-8">
        <label
          class="group flex items-start gap-4 p-5 bg-theme-bg/40 border border-theme-border/50 rounded-theme cursor-pointer hover:bg-theme-bg/80 transition-all shadow-theme">
          <div class="pt-0.5">
            <input type="checkbox" name="disable_external_assets" value="1" <?= !empty($opt['disable_external_assets'])
                                                                              ? 'checked' : '' ?> class="bg-theme-surface border-theme-border rounded focus:ring-theme-primary w-5 h-5
            text-theme-primary form-checkbox shrink-0 transition-shadow">
          </div>
          <div class="flex-1">
            <span class="font-bold text-sm text-theme-text flex items-center gap-2">
              <svg class="w-4 h-4 opacity-70 group-hover:text-theme-primary transition-colors duration-300" fill="none"
                stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shield-check"></use>
              </svg>
              <?= _t('st_disable_external') ?>
            </span>
            <span class="block opacity-60 mt-1.5 text-xs leading-relaxed">
              <?= _t('st_disable_external_desc') ?>
            </span>
          </div>
        </label>
      </div>

      <!-- ========== Skin Editor ========== -->
      <div class="mt-6" x-data="{ openSection: '' }" @input.debounce.300ms="skinDirty = true" @change="skinDirty = true">
        <div class="bg-theme-bg p-4 sm:p-5 border border-theme-border rounded-theme">
          <h4 class="flex items-center gap-2 mb-4 font-bold text-theme-text text-sm">
            <svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-paint-brush"></use>
            </svg>
            <?= _t('st_skin_custom') ?>
          </h4>

          <!-- Accordion: Typography & Layout -->
          <div class="border border-theme-border rounded-theme overflow-hidden">
            <button type="button" @click="openSection = openSection === 'typography' ? '' : 'typography'"
              class="flex justify-between items-center bg-theme-surface hover:bg-theme-bg px-4 py-3 w-full text-left transition-colors">
              <span class="flex items-center gap-2 font-bold text-theme-text text-xs uppercase tracking-wider">
                <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="1.5">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-language"></use>
                </svg>
                <?= _t('st_sect_typography') ?>
              </span>
              <svg class="w-4 h-4 opacity-50 text-theme-text transition-transform"
                :class="openSection === 'typography' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
              </svg>
            </button>
            <div x-show="openSection === 'typography'" x-collapse>
              <div class="px-4 py-4 border-theme-border border-t">
                <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_font_url') ?>
                    </span>
                    <input type="text" name="custom_skin_font_url" value="<?= h($opt['c_font_url']) ?>"
                      placeholder="https://fonts.googleapis.com/css2?..." class="text-xs form-control">
                  </label>
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_font_family') ?>
                    </span>
                    <input type="text" name="custom_skin_font_family" value="<?= h($opt['c_font_family']) ?>"
                      placeholder="'Noto Sans JP', sans-serif" class="text-xs form-control">
                  </label>
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_font_heading') ?>
                    </span>
                    <input type="text" name="custom_skin_font_heading"
                      value="<?= h($opt['c_font_heading'] ?? 'inherit') ?>" class="text-xs form-control">
                  </label>
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_font_size_base') ?>
                    </span>
                    <input type="text" name="custom_skin_font_size_base"
                      value="<?= h($opt['c_font_size_base'] ?? '0.875rem') ?>" class="text-xs form-control">
                  </label>
                </div>
                <div class="gap-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 mt-4">
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_nav_style') ?>
                    </span>
                    <select name="custom_skin_nav_style" class="text-xs form-control">
                      <option value="pill" <?= ($opt['c_nav_style'] ?? 'pill') === 'pill' ? 'selected' : '' ?>>
                        <?= _t('st_nav_pill') ?>
                      </option>
                      <option value="underline" <?= ($opt['c_nav_style'] ?? '') === 'underline' ? 'selected' : '' ?>>
                        <?= _t('st_nav_underline') ?>
                      </option>
                      <option value="block" <?= ($opt['c_nav_style'] ?? '') === 'block' ? 'selected' : '' ?>>
                        <?= _t('st_nav_block') ?>
                      </option>
                      <option value="cyber" <?= ($opt['c_nav_style'] ?? '') === 'cyber' ? 'selected' : '' ?>>
                        <?= _t('st_nav_cyber') ?>
                      </option>
                      <option value="terminal" <?= ($opt['c_nav_style'] ?? '') === 'terminal' ? 'selected' : '' ?>>
                        <?= _t('st_nav_terminal') ?>
                      </option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_custom_skin_texture') ?>
                    </span>
                    <select name="custom_skin_texture" class="text-xs form-control">
                      <option value="" <?= empty($opt['c_texture']) ? 'selected' : '' ?>>
                        <?= _t('none') ?>
                      </option>
                      <?php if (!empty($available_textures)): ?>
                        <?php foreach ($available_textures as $key => $data): ?>
                          <option value="<?= h($key) ?>" <?= ($opt['c_texture'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= h(ucfirst(str_replace('_', ' ', $key))) ?>
                          </option>
                        <?php
                        endforeach; ?>
                      <?php
                      endif; ?>
                    </select>
                  </label>
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_media_bg_css') ?>
                    </span>
                    <select name="custom_skin_media_bg_css" class="text-xs form-control">
                      <option value="" <?= empty($opt['c_media_bg_css']) ? 'selected' : '' ?>><?= _t('default') ?: 'Default' ?></option>
                      <?php if (!empty($available_media_bgs)): ?>
                        <?php foreach ($available_media_bgs as $key => $val): if ($key === 'default') continue; ?>
                          <option value="<?= h($key) ?>" <?= ($opt['c_media_bg_css'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= h(ucfirst(str_replace('_', ' ', $key))) ?>
                          </option>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </select>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion: Dimensions & UI -->
          <div class="border border-theme-border rounded-theme overflow-hidden mt-2">
            <button type="button" @click="openSection = openSection === 'dimensions' ? '' : 'dimensions'"
              class="flex justify-between items-center bg-theme-surface hover:bg-theme-bg px-4 py-3 w-full text-left transition-colors">
              <span class="flex items-center gap-2 font-bold text-theme-text text-xs uppercase tracking-wider">
                <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="1.5">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cog-6-tooth"></use>
                </svg>
                <?= _t('st_sect_dimensions') ?>
              </span>
              <svg class="w-4 h-4 opacity-50 text-theme-text transition-transform"
                :class="openSection === 'dimensions' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
              </svg>
            </button>
            <div x-show="openSection === 'dimensions'" x-collapse>
              <div class="px-4 py-4 border-theme-border border-t">
                <div class="gap-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4">
                  <?php
                  $dims = [
                    'rounded' => _t('st_dim_rounded'),
                    'btn_radius' => _t('st_dim_btn_radius'),
                    'add_block_radius' => _t('st_dim_block_radius'),
                    'border_width' => _t('st_dim_border_width'),
                    'scrollbar_width' => _t('st_dim_scrollbar_width'),
                    'input_padding' => _t('st_dim_input_padding'),
                    'modal_overlay_opacity' => _t('st_dim_overlay_opacity'),
                  ];
                  foreach ($dims as $k => $l):
                  ?>
                    <label class="block">
                      <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                        <?= $l ?>
                      </span>
                      <input type="text" name="custom_skin_<?= $k ?>" value="<?= h($opt['c_' . $k] ?? '') ?>"
                        class="text-xs form-control">
                    </label>
                  <?php
                  endforeach; ?>
                  <label class="block">
                    <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text">
                      <?= _t('st_shadow') ?>
                    </span>
                    <input type="text" name="custom_skin_shadow" value="<?= h($opt['c_shadow'] ?? '') ?>"
                      class="text-xs form-control">
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion: Colors -->
          <div class="border border-theme-border rounded-theme overflow-hidden mt-2" x-data="{ openColorGroup: '' }">
            <button type="button" @click="openSection = openSection === 'colors' ? '' : 'colors'"
              class="flex justify-between items-center bg-theme-surface hover:bg-theme-bg px-4 py-3 w-full text-left transition-colors">
              <span class="flex items-center gap-2 font-bold text-theme-text text-xs uppercase tracking-wider">
                <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="1.5">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-swatch"></use>
                </svg>
                <?= _t('st_sect_colors') ?>
              </span>
              <svg class="w-4 h-4 opacity-50 text-theme-text transition-transform"
                :class="openSection === 'colors' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
              </svg>
            </button>
            <div x-show="openSection === 'colors'" x-collapse>
              <div class="px-4 py-4 border-theme-border border-t">
                <?php
                $allColors = $colorDefGroups;

                $skinDefaults = require ROOT_PATH . '/admin/config/skin_defaults.php';
                $colorDefaults = $skinDefaults['colors'] ?? [];

                $getColorVal = function ($k) use ($opt, $colorDefaults) {
                  $aK = str_replace('_', '-', $k);
                  return $opt['c_' . $k] ?? ($colorDefaults[$k] ?? ($colorDefaults[$aK] ?? '#000000'));
                };

                $groupIndex = 0;
                foreach ($allColors as $group => $keys):
                  $groupId = 'cg_' . $groupIndex;
                ?>
                  <div class="<?= $groupIndex > 0 ? 'mt-2' : '' ?>" x-data="{
                  groupColors: {
                    <?php
                    foreach ($keys as $k):
                      $v = $getColorVal($k);
                      echo "'" . h($k) . "': '" . h($v) . "', ";
                    endforeach;
                    ?>
                  }
                }">
                    <button type="button"
                      @click="openColorGroup = openColorGroup === '<?= $groupId ?>' ? '' : '<?= $groupId ?>'"
                      class="flex justify-between items-center bg-theme-surface/50 hover:bg-theme-surface px-3 py-2 border border-theme-border/50 rounded-theme w-full text-left transition-colors">
                      <span class="font-bold text-[11px] text-theme-text uppercase tracking-wider">
                        <?= $group ?>
                      </span>
                      <div class="flex items-center gap-2">
                        <div class="flex -space-x-1">
                          <?php foreach (array_slice($keys, 0, 5) as $previewKey):
                            $previewVal = $getColorVal($previewKey);
                          ?>
                            <span class="inline-block border border-white rounded-full w-4 h-4"
                              :style="`background: ${groupColors['<?= h($previewKey) ?>']}`" style="background:<?= h($previewVal) ?>"></span>
                          <?php
                          endforeach; ?>
                        </div>
                        <svg class="w-3 h-3 opacity-40 text-theme-text transition-transform"
                          :class="openColorGroup === '<?= $groupId ?>' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                          viewBox="0 0 24 24">
                          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
                        </svg>
                      </div>
                    </button>
                    <div x-show="openColorGroup === '<?= $groupId ?>'" x-collapse class="mt-2 px-1">
                      <div class="gap-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        <?php foreach ($keys as $key):
                          $val = $getColorVal($key); ?>
                          <div x-data="colorPicker('<?= h($val) ?>')" x-effect="groupColors['<?= h($key) ?>'] = val"
                            class="flex flex-col gap-1.5 bg-theme-surface/30 p-2 border border-theme-border/30 rounded-theme">
                            <div class="flex items-center gap-2">
                              <input type="color" x-model="hex" @input="updateVal"
                                class="p-0 border border-theme-border rounded-full w-7 h-7 overflow-hidden cursor-pointer shrink-0">
                              <div class="min-w-0 flex-1">
                                <label class="block opacity-70 mb-0.5 font-bold text-[10px] text-theme-text truncate"
                                  title="<?= h($key) ?>">
                                  <?= h(str_replace('_', ' ', $key)) ?>
                                </label>
                                <input type="text" name="custom_skin_<?= $key ?>" x-model="val" @change="parseVal"
                                  class="w-full text-[10px] form-control-sm px-1.5 py-0.5 h-6">
                              </div>
                            </div>
                            <div class="flex items-center gap-2 px-1">
                              <span class="opacity-50 text-[9px] font-bold text-theme-text" title="Opacity">OP</span>
                              <input type="range" x-model="alpha" @input="updateVal" min="0" max="1" step="0.01"
                                class="flex-1 h-1 bg-theme-border rounded-theme appearance-none cursor-pointer accent-theme-primary">
                              <span class="opacity-50 text-[9px] font-mono text-theme-text w-6 text-right"
                                x-text="Math.round(alpha * 100) + '%'"></span>
                            </div>
                          </div>
                        <?php
                        endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php
                  $groupIndex++;
                endforeach;
                ?>
              </div>
            </div>
          </div>

          <!-- Accordion: CRT Effects -->
          <div class="border border-theme-border rounded-theme overflow-hidden mt-2">
            <button type="button" @click="openSection = openSection === 'crt' ? '' : 'crt'"
              class="flex justify-between items-center bg-theme-surface hover:bg-theme-bg px-4 py-3 w-full text-left transition-colors">
              <span class="flex items-center gap-2 font-bold text-theme-text text-xs uppercase tracking-wider">
                <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="1.5">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-computer-desktop"></use>
                </svg>
                <?= _t('st_sect_crt') ?>
              </span>
              <svg class="w-4 h-4 opacity-50 text-theme-text transition-transform"
                :class="openSection === 'crt' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
              </svg>
            </button>
            <div x-show="openSection === 'crt'" x-collapse>
              <div class="px-4 py-4 border-theme-border border-t">
                <div class="gap-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6">
                  <?php
                  $crtDetails = [
                    'sidebar_crt_opacity',
                    'sidebar_crt_shadow_opacity',
                    'sidebar_crt_border_opacity',
                    'sidebar_crt_border_width',
                    'sidebar_crt_shadow_x',
                    'sidebar_crt_shadow_blur'
                  ];
                  foreach ($crtDetails as $key):
                    $val = $opt['c_' . $key] ?? '';
                    $labelKey = 'st_crt_' . str_replace('sidebar_crt_', '', $key);
                  ?>
                    <label class="block">
                      <span class="block opacity-70 mb-1 font-bold text-[11px] text-theme-text truncate"
                        title="<?= $key ?>">
                        <?= _t($labelKey, str_replace('sidebar_crt_', '', $key)) ?>
                      </span>
                      <input type="text" name="custom_skin_<?= $key ?>" value="<?= h($val) ?>" class="text-xs form-control">
                    </label>
                  <?php
                  endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Save Skin as File -->
          <div class="mt-4 p-4 rounded-theme transition-all duration-300"
            :class="skinDirty ? 'bg-theme-primary/5 border-2 border-theme-primary/40 ring-2 ring-theme-primary/20' : 'bg-theme-surface/50 border border-theme-border'">
            <label class="flex items-center gap-2 mb-2 font-bold text-xs transition-colors"
              :class="skinDirty ? 'text-theme-primary' : 'opacity-70 text-theme-text'">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
              </svg>
              <?= _t('st_save_skin_title') ?>
              <span x-show="skinDirty" x-transition
                class="bg-theme-primary/10 px-2 py-0.5 rounded-full text-[10px] text-theme-primary font-bold animate-pulse">
                <?= _t('st_unsaved_changes') ?>
              </span>
            </label>
            <div class="flex gap-2">
              <div class="relative flex-1">
                <span
                  class="top-1/2 left-3 absolute opacity-50 text-theme-text text-xs -translate-y-1/2 pointer-events-none">skins/</span>
                <input type="text" name="new_skin_name" class="pl-16 text-sm form-control" placeholder="my_new_skin"
                  pattern="[a-z0-9_-]+" title="Alphanumeric, underscore, and hyphen" @input.stop>
                <span
                  class="top-1/2 right-3 absolute opacity-50 text-theme-text text-xs -translate-y-1/2 pointer-events-none">.json</span>
              </div>
              <button type="submit" name="action" value="save_custom_skin"
                class="shadow-theme font-bold text-xs whitespace-nowrap transition-all"
                :class="skinDirty ? 'btn-primary' : 'btn-secondary hover:border-theme-primary hover:text-theme-primary'">
                <?= _t('st_btn_save_file') ?>
              </button>
            </div>
            <p class="opacity-50 mt-2 text-[10px] text-theme-text leading-tight">
              <?= _t('st_save_skin_desc') ?>
            </p>
          </div>

        </div>
      </div>

      <!-- Save Button -->
      <div class="flex flex-col sm:flex-row items-center gap-4 mt-8 pt-6 border-theme-border border-t">
        <div x-show="currentTheme !== selectedTheme" x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
          x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
          x-transition:leave-end="opacity-0 translate-y-2" x-cloak
          class="flex items-center gap-3 bg-theme-danger/5 px-4 py-2 border border-theme-danger/20 rounded-full shadow-theme text-theme-danger text-xs font-bold">
          <span class="relative flex w-2 h-2">
            <span
              class="absolute inline-flex w-full h-full bg-theme-danger rounded-full opacity-75 animate-ping"></span>
            <span class="relative inline-flex w-2 h-2 bg-theme-danger rounded-full"></span>
          </span>
          <?= _t('msg_rebuild_required') ?>
        </div>



        <button type="submit" name="action" value="update_theme"
          class="shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto sm:ml-auto font-bold text-sm transition-all btn-primary">
          <?= _t('btn_save_settings') ?>
        </button>
      </div>
    </form>
  </div>

  <!-- ========== Section 2: Duplicate Theme ========== -->
  <div class="bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme">
    <div class="mb-6">
      <h4 class="flex items-center gap-3 mb-2 font-bold text-theme-text text-lg">
        <div class="flex justify-center items-center shrink-0 bg-theme-info/10 rounded-theme w-10 h-10 text-theme-info">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-duplicate"></use>
          </svg>
        </div>
        <?= _t('st_duplicate_theme_title') ?>
      </h4>
      <p class="opacity-60 ml-0 sm:ml-[52px] mb-2 text-sm text-theme-text leading-relaxed">
        <?= _t('st_duplicate_note') ?>
      </p>
    </div>

    <form method="post"
      class="flex sm:flex-row flex-col items-end gap-5 bg-theme-bg/50 p-5 rounded-theme border border-theme-border/50 warn-on-unsaved"
      onsubmit="return confirm(<?= htmlspecialchars(json_encode(_t('st_confirm_duplicate')), ENT_QUOTES) ?>);">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <input type="hidden" name="action" value="duplicate_theme">

      <div class="flex-1 w-full">
        <label class="block">
          <span class="block opacity-80 mb-2 font-bold text-theme-text text-xs uppercase tracking-wider"><?= _t('st_source_theme') ?></span>
          <select name="source_theme" class="shadow-theme text-sm form-control">
            <?php foreach ($available_site_themes as $dir => $name): ?>
              <option value="<?= h($dir) ?>">
                <?= h($name) ?>
              </option>
            <?php
            endforeach; ?>
          </select>
        </label>
      </div>

      <div class="flex-1 w-full">
        <label class="block">
          <span class="block opacity-80 mb-2 font-bold text-theme-text text-xs uppercase tracking-wider"><?= _t('st_new_theme_name') ?></span>
          <div class="relative shadow-theme rounded-theme">
            <span
              class="top-1/2 left-3 absolute opacity-50 text-theme-text text-xs -translate-y-1/2 pointer-events-none font-mono">theme/</span>
            <input type="text" name="new_theme_name" class="pl-16 text-sm form-control" placeholder="my-custom-theme"
              pattern="[a-zA-Z0-9_-]+" required>
          </div>
        </label>
      </div>

      <button type="submit"
        class="shadow-theme w-full sm:w-auto font-bold text-sm whitespace-nowrap btn-secondary px-6 py-2.5 transition-all hover:bg-theme-info hover:text-white hover:border-theme-info">
        <?= _t('st_duplicate_btn') ?>
      </button>
    </form>
  </div>

</div>


<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('colorPicker', (initialValue) => ({
      val: initialValue,
      hex: '#000000',
      alpha: 1,

      init() {
        this.parseVal();
      },

      parseVal() {
        const v = String(this.val).trim();

        // Hex color (#abc or #aabbcc)
        if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v)) {
          if (v.length === 4) {
            this.hex = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
          } else {
            this.hex = v;
          }
          this.alpha = 1;
          return;
        }

        // rgba(r, g, b, a) or rgb(r, g, b)
        const m = v.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+))?\s*\)$/i);
        if (m) {
          this.hex = '#' +
            ('0' + parseInt(m[1]).toString(16)).slice(-2) +
            ('0' + parseInt(m[2]).toString(16)).slice(-2) +
            ('0' + parseInt(m[3]).toString(16)).slice(-2);
          this.alpha = m[4] !== undefined ? parseFloat(m[4]) : 1;
          return;
        }

        // transparent
        if (v === 'transparent') {
          this.hex = '#000000';
          this.alpha = 0;
        }
      },

      updateVal() {
        if (parseFloat(this.alpha) >= 1) {
          this.val = this.hex;
        } else {
          let r = parseInt(this.hex.slice(1, 3), 16);
          let g = parseInt(this.hex.slice(3, 5), 16);
          let b = parseInt(this.hex.slice(5, 7), 16);
          this.val = `rgba(${r}, ${g}, ${b}, ${this.alpha})`;
        }
      }
    }));
  });
</script>
