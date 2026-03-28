<?php

/** Code Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-2 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.language) block.data.language = 'plaintext'">
  <!-- Language selector -->
  <select x-model="block.data.language" class="w-auto text-xs form-control-sm">
    <option value="html">HTML</option>
    <option value="css">CSS</option>
    <option value="javascript">JavaScript</option>
    <option value="php">PHP</option>
    <option value="sql">SQL</option>
    <option value="bash">Bash</option>
    <option value="plaintext">Plain Text</option>
  </select>
  <!-- Code content -->
  <textarea x-model="block.data.code" rows="8"
    class="w-full font-mono text-xs form-control-sm resize-y overflow-hidden min-h-[10rem]"
    placeholder="<?= _t('ph_code') ?>"
    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"></textarea>
</div>
