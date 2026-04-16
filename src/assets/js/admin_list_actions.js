/**
 * admin_list_actions.js
 * Handle bulk actions and filtering.
 */

/**
 * Unified Toast Notification Manager
 */
window.ToastManager = {
  show: function (options) {
    const opts = Object.assign(
      {
        message: '',
        type: 'success', // 'success', 'error', 'warning', 'info'
        duration: 3000,
        position: 'top-4 sm:top-auto sm:bottom-4',
        customHtml: null,
      },
      typeof options === 'string' ? { message: options } : options
    );

    let container = document.getElementById('grinds-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'grinds-toast-container';
      container.className = `fixed right-4 ${opts.position} z-[100] flex flex-col sm:flex-col-reverse gap-2 pointer-events-none`;
      document.body.appendChild(container);
    }

    const div = document.createElement('div');

    let spriteUrl = (window.grindsBaseUrl || '').replace(/\/$/, '') + '/assets/img/sprite.svg';

    let borderColor = 'border-theme-success';
    let textColor = 'text-theme-success';
    let iconName = 'outline-check-circle';

    if (opts.type === 'error') {
      borderColor = 'border-theme-danger';
      textColor = 'text-theme-danger';
      iconName = 'outline-exclamation-circle';
    } else if (opts.type === 'warning') {
      borderColor = 'border-theme-warning';
      textColor = 'text-theme-warning';
      iconName = 'outline-exclamation-triangle';
    } else if (opts.type === 'info') {
      borderColor = 'border-theme-info';
      textColor = 'text-theme-info';
      iconName = 'outline-information-circle';
    }

    const iconHtml = `<svg class="h-6 w-6 ${textColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="${spriteUrl}#${iconName}"></use></svg>`;

    div.className = `pointer-events-auto w-max min-w-[300px] max-w-sm bg-theme-surface border-l-4 ${borderColor} shadow-theme rounded-r-theme p-4 flex items-start ring-1 ring-theme-border transition-all duration-500 transform -translate-y-4 sm:translate-y-4 opacity-0`;

    // Escape message to prevent DOM Based XSS
    const escapeHtml = (unsafe) => {
      return (unsafe || '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };
    const safeMessage = escapeHtml(opts.message);

    const contentHtml = opts.customHtml || `<p class="text-sm font-bold text-theme-text">${safeMessage}</p>`;

    div.innerHTML = `
        <div class="flex-shrink-0">${iconHtml}</div>
        <div class="ml-3 min-w-0 flex-1 pt-0.5">
            ${contentHtml}
        </div>
        <div class="ml-4 flex-shrink-0 flex">
            <button class="bg-transparent rounded-theme inline-flex text-theme-text opacity-40 hover:opacity-100 focus:outline-none" onclick="this.parentElement.parentElement.remove()"><span class="sr-only">Close</span><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="${spriteUrl}#outline-x-mark"></use></svg></button>
        </div>
    `;

    container.appendChild(div);

    requestAnimationFrame(() => {
      div.classList.remove('-translate-y-4', 'sm:translate-y-4', 'opacity-0');
      div.classList.add('translate-y-0', 'opacity-100');
    });

    setTimeout(() => {
      if (div.parentElement) {
        div.classList.remove('translate-y-0', 'opacity-100');
        div.classList.add('-translate-y-4', 'sm:translate-y-4', 'opacity-0');
        setTimeout(() => div.remove(), 500);
      }
    }, opts.duration);
  },
};

/**
 * Legacy wrapper for backward compatibility.
 * @param {HTMLInputElement} source
 */
window.showToast = function (message, type = 'success') {
  window.ToastManager.show({ message, type });
};

/**
 * Toggle all checkboxes.
 * @param {HTMLInputElement} source
 */
function toggleAll(source) {
  const checkboxes = document.querySelectorAll('input[name="ids[]"], input.item-checkbox, input.post-checkbox');
  checkboxes.forEach((cb) => {
    if (!cb.disabled) cb.checked = source.checked;
  });
}

/**
 * Apply filter to URL.
 * @param {string} key
 * @param {string} value
 */
function applyFilter(key, value) {
  const url = new URL(window.location.href);

  if (value === '' || value === null) {
    url.searchParams.delete(key);
  } else {
    url.searchParams.set(key, value);
  }

  // Reset pagination
  if (key !== 'page') {
    url.searchParams.delete('page');
  }

  window.location.href = url.toString();
}

/**
 * Execute action.
 * @param {string} actionOrSelector
 * @param {string|number} targetId
 */
function executeAction(actionOrSelector, targetId = null) {
  let action = '';
  let select = null;

  // Handle single item action
  if (targetId) {
    action = actionOrSelector;
  }
  // Handle bulk action string
  else if (typeof actionOrSelector === 'string' && !document.getElementById(actionOrSelector)) {
    action = actionOrSelector;
  }
  // Handle selector ID
  else {
    select =
      document.getElementById(actionOrSelector) ||
      document.getElementById('bulk-action-selector') ||
      document.querySelector('select[name="bulk_action"]');
    if (select) {
      action = select.value;
    }
  }

  if (!action) {
    window.showToast(window.grindsTranslations?.err_select_action || 'Please select an action.', 'warning');
    return;
  }

  // Validate bulk action
  if (!targetId) {
    const checkboxes = document.querySelectorAll(
      'input[name="ids[]"]:checked, input.item-checkbox:checked, input.post-checkbox:checked'
    );
    if (checkboxes.length === 0) {
      window.showToast(window.grindsTranslations?.no_items_selected || 'No items selected.', 'warning');
      return;
    }
  }

  // Confirm action
  if (['delete', 'trash', 'empty_trash'].includes(action)) {
    let msg = window.grindsTranslations?.confirm_delete || 'Are you sure?';

    if (action === 'trash') {
      msg = window.grindsTranslations?.confirm_delete_post || 'Move to trash?';
    } else if (action === 'delete') {
      msg = window.grindsTranslations?.confirm_delete_perm || 'Delete permanently? This cannot be undone.';
    } else if (action === 'empty_trash') {
      msg = window.grindsTranslations?.confirm_empty_trash || 'Empty trash? All items will be permanently deleted.';
    }

    if (!confirm(msg)) {
      return;
    }
  }

  submitActionForm(action, targetId);
}

/**
 * Submit action form.
 * @param {string} action
 * @param {string|number} targetId
 */
function submitActionForm(action, targetId) {
  const form = document.getElementById('unified-action-form');
  if (!form) return;

  const actionInput = document.getElementById('form-action-input');
  if (actionInput) actionInput.value = action;

  // Clear inputs
  form.querySelectorAll('.dynamic-input').forEach((el) => el.remove());

  // Handle category change
  if (action === 'change_category') {
    const catSelect = document.getElementById('bulk-category-selector');
    if (catSelect && catSelect.value) {
      const catInput = document.createElement('input');
      catInput.type = 'hidden';
      catInput.name = 'new_category_id';
      catInput.value = catSelect.value;
      catInput.className = 'dynamic-input';
      form.appendChild(catInput);
    } else {
      window.showToast(window.grindsTranslations?.err_select_category || 'Please select a category.', 'warning');
      return;
    }
  }

  if (targetId) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'target_id';
    input.value = targetId;
    input.className = 'dynamic-input';
    form.appendChild(input);
  } else {
    // Collect IDs
    const checkboxes = document.querySelectorAll(
      'input[name="ids[]"]:checked, input.item-checkbox:checked, input.post-checkbox:checked'
    );
    checkboxes.forEach((cb) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = cb.value;
      input.className = 'dynamic-input';
      form.appendChild(input);
    });
  }

  form.submit();
}

// Initialize listeners
document.addEventListener('DOMContentLoaded', () => {
  // Bind select all checkbox
  const selectAllCheckboxes = document.querySelectorAll('#select-all, #mobile-select-all');
  const checkboxes = document.querySelectorAll('input[name="ids[]"], input.item-checkbox, input.post-checkbox');

  if (selectAllCheckboxes.length > 0) {
    selectAllCheckboxes.forEach((selectAll) => {
      selectAll.addEventListener('change', function () {
        toggleAll(this);
        selectAllCheckboxes.forEach((cb) => (cb.checked = this.checked));
      });
    });

    // Sync checkboxes
    checkboxes.forEach((cb) => {
      cb.addEventListener('change', function () {
        const allChecked = Array.from(checkboxes).every((c) => c.checked);
        selectAllCheckboxes.forEach((selectAll) => {
          selectAll.checked = allChecked;
        });
      });
    });
  }

  // Bind apply button
  const bulkApply = document.getElementById('bulk-apply');
  if (bulkApply) {
    bulkApply.addEventListener('click', function (e) {
      e.preventDefault();
      executeAction();
    });
  }

  /**
   * Global Ctrl+S / Cmd+S save shortcut for forms
   */
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
      // Skip if in the post editor (handled by admin_editor.js)
      if (typeof window.grindsPostContent !== 'undefined') return;

      const form = document.querySelector('form.warn-on-unsaved');
      if (form) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitBtn);
          } else {
            submitBtn.click();
          }
        }
      }
    }

    // Global Tab key handler for monospace textareas (Code Editors)
    if (e.key === 'Tab' && e.target.tagName === 'TEXTAREA' && e.target.classList.contains('font-mono')) {
      // Skip global tab handling in post editor to prevent conflict with handleCodeIndent
      if (typeof window.grindsPostContent !== 'undefined') return;

      e.preventDefault();
      const el = e.target;
      const start = el.selectionStart;
      const end = el.selectionEnd;
      const val = el.value;

      // Insert 2 spaces
      el.value = val.substring(0, start) + '  ' + val.substring(end);
      el.selectionStart = el.selectionEnd = start + 2;

      // Trigger Alpine.js and unsaved changes detection
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
  });
});
