<?php

/**
 * Display system health status and diagnostic tools
 */
if (!defined('GRINDS_APP')) exit; ?>
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('searchIndexerOverride', (config) => ({
      processing: false,
      percentage: 0,
      statusMsg: '',

      async startRebuild(silent = false) {
        if (!silent && !confirm(config.trans.confirm)) return;
        this.processing = true;
        this.percentage = 0;
        this.statusMsg = config.trans.init;

        let offset = 0;
        let hasMore = true;
        try {
          while (hasMore) {
            const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/rebuild_index.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                csrf_token: config.csrfToken,
                offset: offset
              })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            this.percentage = data.percentage;
            offset = data.next_offset;
            hasMore = data.has_more;
          }
          this.statusMsg = config.trans.doneMsg;
          if (!silent) window.showToast(config.trans.done);
        } catch (e) {
          window.showToast(e.message, 'error');
        } finally {
          this.processing = false;
        }
      }
    }));
  });
</script>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">

  <div class="mb-6 sm:mb-8">
    <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
      <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cpu-chip"></use>
      </svg>
      <?= _t('tab_system') ?>
    </h3>
    <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed"><?= _t('st_system_desc') ?></p>
  </div>

  <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme">
    <h4 class="flex items-center gap-2 mb-6 font-bold text-theme-text text-lg">
      <svg class="w-5 h-5 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
      </svg>
      <?= _t('health_title') ?>
    </h4>

    <?php $health_checks = check_system_health(); ?>

    <div class="hidden md:block bg-theme-surface border border-theme-border rounded-theme overflow-x-auto">
      <table class="min-w-full leading-normal">
        <thead class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
          <tr>
            <th class="px-6 py-4 w-1/3 text-left"><?= _t('health_item') ?></th>
            <th class="px-6 py-4 w-24 text-center"><?= _t('health_status') ?></th>
            <th class="px-6 py-4 text-left"><?= _t('health_details') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border text-sm">
          <?php foreach ($health_checks as $chk):
            $badgeClass = 'bg-theme-bg text-theme-text';
            $badgeLabel = '-';
            if ($chk['status'] === 'ok') {
              $badgeClass = 'bg-theme-success/10 text-theme-success border border-theme-success/20';
              $badgeLabel = _t('health_ok');
            } elseif ($chk['status'] === 'warning') {
              $badgeClass = 'bg-theme-warning/10 text-theme-warning border border-theme-warning/20';
              $badgeLabel = _t('health_warn');
            } elseif ($chk['status'] === 'danger') {
              $badgeClass = 'bg-theme-danger/10 text-theme-danger border border-theme-danger/20 font-bold';
              $badgeLabel = _t('health_danger');
            }
          ?>
            <tr class="hover:bg-theme-bg/50 transition-colors">
              <td class="px-6 py-4 font-medium text-theme-text"><?= h($chk['label']) ?></td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded-theme text-xs <?= $badgeClass ?>">
                  <?= h($badgeLabel) ?>
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <span class="font-mono text-theme-text text-xs"><?= h($chk['value']) ?></span>
                  <?php if ($chk['msg']): ?>
                    <span class="mt-0.5 text-theme-danger text-xs break-words whitespace-normal"><?= $chk['msg'] ?></span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="md:hidden space-y-3">
      <?php foreach ($health_checks as $chk):
        $borderClass = 'border-theme-border';
        $icon = 'outline-check-circle';
        $iconColor = 'text-theme-success';

        if ($chk['status'] === 'warning') {
          $borderClass = 'border-theme-warning/50';
          $icon = 'outline-exclamation-triangle';
          $iconColor = 'text-theme-warning';
        } elseif ($chk['status'] === 'danger') {
          $borderClass = 'border-theme-danger/50';
          $icon = 'outline-exclamation-circle';
          $iconColor = 'text-theme-danger';
        }
      ?>
        <div class="bg-theme-surface border <?= $borderClass ?> rounded-theme p-3 flex items-start gap-3">
          <div class="mt-0.5 shrink-0">
            <svg class="w-5 h-5 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') . '#' . $icon ?>"></use>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start mb-1">
              <span class="font-bold text-theme-text text-sm"><?= h($chk['label']) ?></span>
              <span class="opacity-70 font-mono text-theme-text text-xs"><?= h($chk['value']) ?></span>
            </div>
            <?php if ($chk['msg']): ?>
              <p class="bg-theme-danger/5 mt-1 p-1.5 rounded-theme text-theme-danger text-xs"><?= $chk['msg'] ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (str_contains(strtolower($_SERVER['SERVER_SOFTWARE'] ?? ''), 'nginx')): ?>
    <div id="nginx-security-container" class="flex items-start gap-3 bg-theme-warning/10 p-4 border border-theme-warning/20 rounded-theme transition-colors duration-500">
      <svg id="nginx-security-icon" class="mt-0.5 w-5 h-5 text-theme-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
      </svg>
      <div class="flex-1">
        <h4 id="nginx-security-title" class="mb-1 font-bold text-theme-warning text-sm"><?= _t('nginx_detect_title') ?></h4>
        <p class="opacity-80 text-theme-text text-xs leading-relaxed">
          <?= _t('nginx_detect_desc') ?>
        </p>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', async function() {
        try {
          // Create test PHP file via API
          await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php?tab=system', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=create_security_test&csrf_token=<?= h(generate_csrf_token()) ?>'
          });

          // Access generated file directly
          const response = await fetch('<?= resolve_url('assets/uploads/security_test.php') ?>', {
            method: 'GET',
            cache: 'no-store'
          });

          // Flag as dangerous if response is not 403 or 404 (meaning PHP executed)
          if (response.status !== 403 && response.status !== 404) {
            document.getElementById('nginx-security-container').className = 'flex items-start gap-3 bg-theme-danger/10 p-4 border border-theme-danger/30 rounded-theme transition-colors duration-500';
            document.getElementById('nginx-security-icon').className = 'mt-0.5 w-5 h-5 text-theme-danger shrink-0';
            document.getElementById('nginx-security-title').innerHTML += ' <span class="bg-theme-danger text-white px-2 py-0.5 rounded-theme text-[10px] ml-2 animate-pulse uppercase shadow-theme">Action Required</span>';
          }
        } catch (e) {
          // Handle errors
        } finally {
          // Delete test PHP file
          fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php?tab=system', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=delete_security_test&csrf_token=<?= h(generate_csrf_token()) ?>'
          });
        }
      });
    </script>
  <?php endif; ?>

  <?php if (file_exists(ROOT_PATH . '/install.php')): ?>
    <div class="flex justify-between items-center gap-4 bg-theme-danger/10 p-4 border border-theme-danger/20 rounded-theme">
      <div class="flex items-center gap-3 text-theme-danger text-xs">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
        </svg>
        <span>
          <strong><?= _t('st_action_required') ?></strong> <?= _t('st_installer_present') ?>
        </span>
      </div>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_installer">
        <button type="submit" class="bg-theme-danger hover:opacity-90 shadow-theme px-3 py-1.5 rounded-theme font-bold text-white text-xs">
          <?= _t('btn_delete_now') ?>
        </button>
      </form>
    </div>
  <?php endif; ?>

  <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme">
    <h4 class="flex items-center gap-2 mb-6 font-bold text-theme-text text-lg">
      <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
      </svg>
      <?= _t('st_doctor_title') ?>
    </h4>

    <div class="gap-4 grid grid-cols-1 md:grid-cols-2">

      <div class="flex flex-col justify-between gap-4 bg-theme-surface p-5 border border-theme-border rounded-theme">
        <div>
          <h4 class="mb-1 font-bold text-theme-text text-sm"><?= _t('menu_migration_check') ?></h4>
          <p class="opacity-70 text-theme-text text-xs leading-relaxed">
            <?= _t('mig_check_desc') ?>
          </p>
        </div>
        <a href="migration_checklist.php" class="flex justify-center items-center gap-2 shadow-theme mt-auto px-4 py-2 rounded-theme w-full text-xs font-bold btn-secondary">
          <?= _t('mig_check_title') ?>
        </a>
      </div>

      <?php
      $searchIndexConfig = [
        'csrfToken' => generate_csrf_token(),
        'trans' => [
          'init' => _t('js_initializing'),
          'done' => _t('js_index_done'),
          'error' => _t('js_error'),
          'confirm' => _t('st_search_index_confirm'),
          'doneMsg' => _t('js_done')
        ]
      ];
      ?>
      <div class="flex flex-col justify-between gap-4 bg-theme-surface p-5 border border-theme-border rounded-theme" x-data="searchIndexerOverride(<?= htmlspecialchars(json_encode($searchIndexConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)" @trigger-search-rebuild.window="startRebuild(true)">
        <div>
          <h4 class="mb-1 font-bold text-theme-text text-sm"><?= _t('st_search_index_title') ?></h4>
          <p class="opacity-70 text-theme-text text-xs leading-relaxed">
            <?= _t('st_search_index_desc') ?>
          </p>
        </div>

        <div x-show="processing" class="w-full" x-cloak>
          <div class="flex justify-between opacity-70 mb-1 font-mono text-[10px] text-theme-text">
            <span x-text="statusMsg"></span>
            <span x-text="percentage + '%'"></span>
          </div>
          <div class="bg-theme-border rounded-full w-full h-1.5 overflow-hidden">
            <div class="bg-theme-success rounded-full h-1.5 transition-all duration-300" :style="'width: ' + percentage + '%'"></div>
          </div>
        </div>

        <button type="button" @click="startRebuild()" :disabled="processing" class="flex justify-center items-center gap-2 disabled:opacity-50 shadow-theme mt-auto px-4 py-2 rounded-theme w-full text-xs font-bold disabled:cursor-not-allowed btn-secondary">
          <span x-show="!processing"><?= _t('st_search_index_btn') ?></span>
          <svg x-show="processing" x-cloak class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
          <span x-show="processing" x-cloak><?= _t('js_loading') ?></span>
        </button>
      </div>

      <div class="flex flex-col justify-between gap-4 bg-theme-surface p-5 border border-theme-border rounded-theme">
        <div>
          <h4 class="mb-1 font-bold text-theme-text text-sm"><?= _t('st_opt_db_title') ?></h4>
          <p class="opacity-70 text-theme-text text-xs leading-relaxed">
            <?= _t('st_opt_db_desc') ?>
          </p>
        </div>
        <div x-data="{ optimizing: false }" class="mt-auto w-full">
          <button @click="if(!confirm(<?= htmlspecialchars(json_encode(_t('confirm_optimize_db')), ENT_QUOTES) ?>)) return; optimizing = true; fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php?tab=system', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=optimize_db&ajax_mode=1&csrf_token=<?= h(generate_csrf_token()) ?>' }).then(r=>r.json()).then(d=>{ optimizing = false; if(d.success) window.showToast(d.message); else window.showToast(d.error, 'error'); }).catch(e=>{ optimizing = false; window.showToast(e.message, 'error'); })" :disabled="optimizing" class="flex justify-center items-center gap-2 shadow-theme px-4 py-2 rounded-theme w-full text-xs font-bold btn-secondary">
            <span x-show="!optimizing"><?= _t('btn_optimize_db') ?></span>
            <span x-show="optimizing">...</span>
          </button>
        </div>
      </div>

      <div class="flex flex-col justify-between gap-4 bg-theme-surface p-5 border border-theme-border rounded-theme">
        <div>
          <h4 class="mb-1 font-bold text-theme-text text-sm"><?= _t('st_clear_cache_title') ?></h4>
          <p class="opacity-70 text-theme-text text-xs leading-relaxed">
            <?= _t('st_clear_cache_desc') ?>
          </p>
        </div>
        <div x-data="{ clearing: false }" class="mt-auto w-full">
          <button @click="clearing = true; fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/clear_cache.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({csrf_token: '<?= h(generate_csrf_token()) ?>'}) }).then(r=>r.json()).then(d=>{ clearing = false; if(d.success) window.showToast(<?= htmlspecialchars(json_encode(_t('msg_cache_cleared')), ENT_QUOTES) ?>); else window.showToast(d.error, 'error'); }).catch(e=>{ clearing = false; window.showToast(e.message, 'error'); })" :disabled="clearing" class="flex justify-center items-center gap-2 hover:bg-theme-warning/10 shadow-theme px-4 py-2 border-theme-warning/30 rounded-theme w-full text-theme-warning text-xs font-bold btn-secondary">
            <span x-show="!clearing"><?= _t('btn_clear_cache') ?></span>
            <span x-show="clearing">...</span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme" x-data="{ logs: [], loading: false, show: false }">
    <button @click="if(!show){ loading=true; show=true; fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php?tab=system', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'action=fetch_system_log&csrf_token=<?= h(generate_csrf_token()) ?>'
      }).then(r=>r.json()).then(d=>{ logs=d.logs; loading=false; }); } else { show=false; }"
      class="flex justify-between items-center w-full text-left font-bold text-theme-text hover:text-theme-primary text-lg transition-colors focus:outline-none">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
        </svg>
        <?= _t('st_log_viewer_title') ?>
      </div>
      <svg class="w-5 h-5 opacity-50 transition-transform duration-200" :class="show ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
      </svg>
    </button>

    <div x-show="show" x-collapse>
      <div class="mt-6 bg-theme-surface shadow-inner p-5 border border-theme-border rounded-theme h-64 overflow-y-auto font-mono text-theme-text text-xs">
        <div x-show="loading" class="opacity-50 py-10 text-center"><?= _t('js_loading_logs') ?></div>
        <template x-if="!loading && logs.length === 0">
          <div class="opacity-50 py-10 text-center"><?= _t('js_log_empty') ?></div>
        </template>
        <template x-for="line in logs">
          <div class="py-1 border-theme-border/50 border-b whitespace-pre-wrap" x-text="line"></div>
        </template>
      </div>
      <p class="opacity-50 mt-1 text-[10px] text-theme-text text-right"><?= _t('st_log_viewer_footer') ?></p>
    </div>
  </div>

  <!-- プロキシ（ネットワーク）設定ブロック -->
  <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme">
    <h4 class="flex items-center gap-2 mb-6 font-bold text-theme-text text-lg">
      <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-globe-alt"></use>
      </svg>
      <?= _t('st_proxy_title') ?>
    </h4>

    <form method="post" class="space-y-4 warn-on-unsaved" x-data="{
      enabled: <?= !empty($opt['trust_proxies']) ? 'true' : 'false' ?>,
      proxyIps: <?= htmlspecialchars(json_encode($opt['trusted_proxy_ips'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    }">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <input type="hidden" name="action" value="update_proxy_settings">

      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="trust_proxies" value="1" class="mt-1 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox" x-model="enabled">
        <div>
          <span class="block font-bold text-theme-text text-sm"><?= _t('st_trust_proxies') ?></span>
          <span class="block opacity-60 mt-1 text-theme-text text-xs leading-relaxed"><?= _t('st_trust_proxies_desc') ?></span>
        </div>
      </label>

      <div class="block pl-8" x-show="enabled" x-cloak>
        <label class="block">
          <span class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('st_trusted_ips') ?></span>
          <input type="text" name="trusted_proxy_ips" x-model="proxyIps" class="w-full text-sm form-control" placeholder="10.0.0.0/8, 172.16.0.0/12">
          <p class="opacity-50 mt-1 text-[10px] text-theme-text"><?= _t('st_trusted_ips_desc') ?></p>
        </label>

        <div x-show="proxyIps.trim() === ''" x-collapse x-cloak>
          <div class="flex items-start gap-3 bg-theme-danger/10 mt-3 p-3 border border-theme-danger/30 rounded-theme">
            <svg class="mt-0.5 w-4 h-4 text-theme-danger shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
            <div class="opacity-90 text-theme-danger text-[11px] leading-relaxed">
              <strong class="font-bold block mb-0.5"><?= _t('st_proxy_warning_title') ?></strong>
              <?= _t('st_proxy_warning_desc') ?>
            </div>
          </div>
        </div>
      </div>

      <div class="text-right">
        <button type="submit" class="shadow-theme px-4 py-2 rounded-theme text-xs font-bold btn-secondary"><?= _t('save') ?></button>
      </div>
    </form>
  </div>

  <!-- 一般システム設定ブロック -->
  <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme space-y-6">
    <h4 class="flex items-center gap-2 font-bold text-theme-text text-lg">
      <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cog-6-tooth"></use>
      </svg>
      <?= _t('menu_settings') ?>
    </h4>

    <form method="post" class="flex sm:flex-row flex-col justify-between sm:items-center gap-4 warn-on-unsaved"
      x-data="{ current: <?= !empty($opt['debug_mode']) ? 'true' : 'false' ?>, initial: <?= !empty($opt['debug_mode']) ? 'true' : 'false' ?> }">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <input type="hidden" name="action" value="update_debug_mode">

      <div class="flex-1">
        <label for="debug_toggle" class="flex items-center gap-2 font-bold text-theme-text text-sm cursor-pointer select-none">
          <svg class="w-5 h-5 text-theme-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bug-ant"></use>
          </svg>
          <?= _t('st_debug_mode') ?>
        </label>
        <p class="opacity-60 mt-1 text-theme-text text-xs leading-relaxed">
          <?= _t('st_debug_mode_desc') ?>
        </p>
      </div>

      <div class="flex justify-between sm:justify-start items-center gap-4 bg-theme-surface shadow-theme p-2 pl-4 border border-theme-border hover:border-theme-primary/30 rounded-theme w-full sm:w-auto transition-colors">
        <label class="inline-flex relative items-center cursor-pointer">
          <input type="checkbox" id="debug_toggle" name="debug_mode" value="1" class="sr-only peer" x-model="current">
          <div class="peer after:top-[2px] after:left-[2px] after:absolute after:bg-theme-surface peer-checked:bg-theme-primary shadow-inner bg-theme-border after:border after:border-theme-border rounded-full after:rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-theme-primary/20 w-9 after:w-4 h-5 after:h-4 after:content-[''] after:transition-all peer-checked:after:translate-x-full"></div>
        </label>

        <div class="flex items-center gap-2">
          <div class="bg-theme-border w-px h-4"></div>
          <button type="submit"
            :disabled="current === initial"
            :class="current !== initial ? 'text-theme-primary hover:bg-theme-primary hover:text-theme-on-primary' : 'text-theme-text opacity-30 cursor-not-allowed'"
            class="flex items-center px-3 py-1.5 rounded-theme font-bold text-xs transition-all">
            <?= _t('save') ?>
          </button>
        </div>
      </div>
    </form>

    <form method="post" class="warn-on-unsaved"
      x-data="{
      current: <?= file_exists(ROOT_PATH . '/.maintenance') ? 'true' : 'false' ?>,
      initial: <?= file_exists(ROOT_PATH . '/.maintenance') ? 'true' : 'false' ?>,
      title: <?= htmlspecialchars(json_encode(get_option('maintenance_title') ?: '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
      origTitle: <?= htmlspecialchars(json_encode(get_option('maintenance_title') ?: '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
      message: <?= htmlspecialchars(json_encode(get_option('maintenance_message') ?: '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
      origMessage: <?= htmlspecialchars(json_encode(get_option('maintenance_message') ?: '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>
    }">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <input type="hidden" name="action" value="toggle_maintenance">
      <input type="hidden" name="toggle_maintenance" value="1">

      <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4">
        <div class="flex-1">
          <label for="maintenance_toggle" class="flex items-center gap-2 font-bold text-theme-text text-sm cursor-pointer select-none">
            <svg class="w-5 h-5 text-theme-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
            </svg>
            <?= _t('st_maintenance_title') ?>
          </label>
          <p class="opacity-60 mt-1 text-theme-text text-xs leading-relaxed">
            <?= _t('st_maintenance_desc') ?>
          </p>

          <div class="space-y-3 mt-4" x-show="current" x-transition>
            <label class="block">
              <span class="block mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_title') ?></span>
              <input type="text" name="maintenance_title" x-model="title" class="w-full form-control-sm" placeholder="<?= _t('maintenance_title') ?>">
            </label>
            <label class="block">
              <span class="block mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_message') ?></span>
              <textarea name="maintenance_message" rows="3" x-model="message" class="w-full form-control-sm" placeholder="<?= _t('maintenance_message') ?>"></textarea>
            </label>
          </div>
        </div>

        <div class="flex justify-between sm:justify-start items-center self-start gap-4 bg-theme-surface shadow-theme p-2 pl-4 border border-theme-border hover:border-theme-primary/30 rounded-theme w-full sm:w-auto transition-colors">
          <label class="inline-flex relative items-center cursor-pointer">
            <input type="checkbox" id="maintenance_toggle" name="maintenance_mode" value="1" class="sr-only peer" x-model="current">
            <div class="peer after:top-[2px] after:left-[2px] after:absolute after:bg-theme-surface peer-checked:bg-theme-primary shadow-inner bg-theme-border after:border after:border-theme-border rounded-full after:rounded-full peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-theme-primary/20 w-9 after:w-4 h-5 after:h-4 after:content-[''] after:transition-all peer-checked:after:translate-x-full"></div>
          </label>

          <div class="flex items-center gap-2">
            <div class="bg-theme-border w-px h-4"></div>
            <button type="submit"
              :disabled="current === initial && title === origTitle && message === origMessage"
              :class="(current !== initial || title !== origTitle || message !== origMessage) ? 'text-theme-primary hover:bg-theme-primary hover:text-theme-on-primary' : 'text-theme-text opacity-30 cursor-not-allowed'"
              class="flex items-center px-3 py-1.5 rounded-theme font-bold text-xs transition-all">
              <?= _t('save') ?>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="bg-theme-danger/5 p-6 border border-theme-danger/20 rounded-theme">
    <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-danger text-lg">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
      </svg>
      <?= _t('st_dangerous') ?>
    </h4>

    <div class="space-y-2 mt-4">
      <!-- Install sample data -->
      <div class="flex sm:flex-row flex-col justify-between items-start sm:items-center gap-4 pb-4 border-b border-theme-danger/20">
        <div>
          <h5 class="mb-1 font-bold text-theme-danger text-sm"><?= _t('st_sample_data') ?></h5>
          <p class="opacity-80 max-w-xl text-theme-danger text-xs leading-relaxed"><?= _t('desc_templates') ?></p>
        </div>
        <form method="post" class="shrink-0 w-full sm:w-auto">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
          <input type="hidden" name="action" value="install_sample_templates">
          <button type="submit" onclick="return confirm(<?= htmlspecialchars(json_encode(_t('confirm_install_tpl')), ENT_QUOTES) ?>);" class="bg-theme-surface hover:bg-theme-danger shadow-theme px-6 py-2.5 border border-theme-danger rounded-theme w-full font-bold text-theme-danger hover:text-white text-sm whitespace-nowrap transition-colors">
            <?= _t('btn_install_tpl') ?>
          </button>
        </form>
      </div>

      <!-- Reset settings -->
      <div class="flex sm:flex-row flex-col justify-between items-start sm:items-center gap-4 pt-2">
        <p class="opacity-80 max-w-xl text-theme-danger text-xs leading-relaxed"><?= _t('st_reset_desc') ?></p>
        <form method="post" class="shrink-0 w-full sm:w-auto">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
          <input type="hidden" name="action" value="reset_settings">
          <button type="submit" onclick="return confirm(<?= htmlspecialchars(json_encode(_t('confirm_delete')), ENT_QUOTES) ?>);" class="bg-theme-surface hover:bg-theme-danger shadow-theme px-6 py-2.5 border border-theme-danger rounded-theme w-full font-bold text-theme-danger hover:text-white text-sm whitespace-nowrap transition-colors">
            <?= _t('st_reset_all') ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
