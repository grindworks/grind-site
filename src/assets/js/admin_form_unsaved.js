/**
 * admin_form_unsaved.js
 *
 * Handles unsaved changes warnings on forms.
 * To use, add the class "warn-on-unsaved" to any <form> element.
 *
 * A form counts as changed only when the values of its named fields differ
 * from the values it had when the page loaded. Events alone (programmatic
 * events, editing a value and then restoring it) do not mark the page as
 * changed. Username, email and password fields count only after the user
 * typed, pasted or dropped into them, because browsers and password managers
 * fill them on page load.
 */
document.addEventListener('DOMContentLoaded', () => {
  const formsToWatch = Array.from(document.querySelectorAll('form.warn-on-unsaved'));
  if (formsToWatch.length === 0) return;

  let isDirty = false;
  let skipUnloadWarning = false;
  const originalTitle = document.title;
  const baselines = new Map();

  // Global bypass flag for specific JS actions (e.g., moving post to trash)
  window.grindsBypassUnload = false;

  // Fields that browsers and password managers autofill on page load
  const isCredentialField = (el) =>
    el.type === 'password' ||
    el.hasAttribute(':type') ||
    ['username', 'email', 'current-password', 'new-password'].includes(el.getAttribute('autocomplete'));

  // Credential fields keep their initial value until the user types, pastes or drops into them
  const touched = new WeakSet();
  const initialValues = new WeakMap();
  const markTouched = (event) => {
    if (event.isTrusted && event.target instanceof Element) touched.add(event.target);
  };

  // Serialize the submittable state of a form (named fields only, CSRF token excluded)
  const serializeForm = (form) => {
    const parts = [];
    for (const el of form.elements) {
      if (!el.name || el.disabled || el.name === 'csrf_token') continue;
      if (['button', 'submit', 'reset', 'image'].includes(el.type)) continue;
      if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) continue;
      if (el.type === 'file') {
        for (const f of el.files) parts.push(`${el.name}=file:${f.name}:${f.size}`);
      } else if (el.type === 'select-multiple') {
        for (const o of el.selectedOptions) parts.push(`${el.name}=${o.value}`);
      } else if (isCredentialField(el) && !touched.has(el)) {
        if (initialValues.has(el)) parts.push(`${el.name}=${initialValues.get(el)}`);
      } else {
        parts.push(`${el.name}=${el.value}`);
      }
    }
    return parts.join('\u0000');
  };

  const captureBaselines = () => {
    formsToWatch.forEach((form) => {
      for (const el of form.elements) {
        if (el.name && isCredentialField(el)) initialValues.set(el, el.value);
      }
      baselines.set(form, serializeForm(form));
    });
  };

  const updateDirty = () => {
    const changed = formsToWatch.some((form) => baselines.has(form) && serializeForm(form) !== baselines.get(form));
    if (changed === isDirty) return;
    isDirty = changed;
    document.title = isDirty ? '* ' + originalTitle : originalTitle;
  };

  // Take the baseline after Alpine.js and other components have set their initial values
  requestAnimationFrame(() => setTimeout(captureBaselines, 0));

  const beforeUnloadHandler = (event) => {
    if (window.grindsBypassUnload) {
      return; // Completely bypass
    }

    if (isDirty && !skipUnloadWarning) {
      // Prevent navigation. Most modern browsers show a generic confirmation dialog.
      event.preventDefault();
      // Required for some browsers (like Chrome).
      event.returnValue = '';
      return ''; // For legacy browsers.
    }
  };

  formsToWatch.forEach((form) => {
    ['keydown', 'paste', 'drop', 'cut'].forEach((type) => form.addEventListener(type, markTouched, true));

    // Re-check whenever a field may have changed.
    form.addEventListener('input', updateDirty);
    form.addEventListener('change', updateDirty);

    // When the form is submitted, we no longer need the warning.
    form.addEventListener('submit', () => {
      skipUnloadWarning = true;
      document.title = originalTitle;
    });
  });

  // Add event listeners to all elements that should skip the warning
  document.querySelectorAll('.js-skip-warning').forEach((element) => {
    element.addEventListener('click', () => {
      skipUnloadWarning = true;
    });
  });

  // Diagnostic: list the fields that currently count as changed
  window.grindsUnsavedFields = () => {
    const changed = [];
    formsToWatch.forEach((form) => {
      const base = new Set((baselines.get(form) || '').split('\u0000'));
      serializeForm(form).split('\u0000').forEach((pair) => {
        if (pair && !base.has(pair)) changed.push(pair.split('=')[0]);
      });
    });
    return changed;
  };

  window.addEventListener('beforeunload', beforeUnloadHandler);
});
