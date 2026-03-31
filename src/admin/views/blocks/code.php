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
  <textarea x-model="block.data.code" :id="'block-' + block.id + '-code'" rows="8"
    class="w-full font-mono text-xs form-control-sm resize-y overflow-y-auto min-h-[10rem] max-h-[500px]"
    placeholder="<?= _t('ph_code') ?>"
    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'" @keydown.tab.prevent="
      const el = $el;
      const start = el.selectionStart;
      const end = el.selectionEnd;
      const value = block.data.code;

      const lineStart = value.lastIndexOf('\n', start - 1) + 1;
      const lineEnd = value.indexOf('\n', end - 1);
      const affectedEnd = lineEnd === -1 ? value.length : lineEnd;

      const selectedLinesText = value.substring(lineStart, affectedEnd);
      const lines = selectedLinesText.split('\n');
      let change = 0;

      if ($event.shiftKey) { // Un-indent
        const newLines = lines.map(line => {
          if (line.startsWith('  ')) {
            change -= 2;
            return line.substring(2);
          } else if (line.startsWith(' ')) {
            change -= 1;
            return line.substring(1);
          }
          return line;
        });
        block.data.code = value.substring(0, lineStart) + newLines.join('\n') + value.substring(affectedEnd);
        $nextTick(() => {
          el.selectionStart = Math.max(lineStart, start + (lines[0].startsWith('  ') ? -2 : (lines[0].startsWith(' ') ? -1 : 0)));
          el.selectionEnd = Math.max(el.selectionStart, end + change);
        });
      } else { // Indent
        if (start === end) { // No selection, just insert spaces
          block.data.code = value.substring(0, start) + '  ' + value.substring(end);
          $nextTick(() => { el.selectionStart = el.selectionEnd = start + 2; });
        } else { // Selection exists, indent all selected lines
          const newLines = lines.map(line => { change += 2; return '  ' + line; });
          block.data.code = value.substring(0, lineStart) + newLines.join('\n') + value.substring(affectedEnd);
          $nextTick(() => {
            el.selectionStart = start + 2;
            el.selectionEnd = end + change;
          });
        }
      }
    "></textarea>
</div>
