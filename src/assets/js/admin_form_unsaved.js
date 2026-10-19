/**
 * admin_form_unsaved.js
 *
 * Handles unsaved changes warnings on forms.
 * To use, add the class "warn-on-unsaved" to any <form> element.
 *
 * A form counts as changed only when the values of its named fields differ
 * from the values it had when the page loaded. Events alone (browser autofill
 * into unnamed decoy fields, programmatic events, editing a value and then
 * restoring it) do not mark the page as changed.
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

  // Serialize the submittable state of a form (named fields only, CSRF token excluded)
  const serializeForm = (form) => {
    const parts = [];
    for (const [name, value] of new FormData(form)) {
      if (name === 'csrf_token') continue;
      const v = value instanceof File ? `file:${value.name}:${value.size}` : value;
      parts.push(`${name}=${v}`);
    }
    return parts.join('\u0000');
  };

  const captureBaselines = () => {
    formsToWatch.forEach((form) => baselines.set(form, serializeForm(form)));
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

  window.addEventListener('beforeunload', beforeUnloadHandler);
});
