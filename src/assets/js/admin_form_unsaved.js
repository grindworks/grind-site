/**
 * admin_form_unsaved.js
 *
 * Handles unsaved changes warnings on forms.
 * To use, add the class "warn-on-unsaved" to any <form> element.
 */
document.addEventListener('DOMContentLoaded', () => {
  const formsToWatch = document.querySelectorAll('form.warn-on-unsaved');
  if (formsToWatch.length === 0) return;

  let isDirty = false;
  let skipUnloadWarning = false;
  const originalTitle = document.title;

  // Global bypass flag for specific JS actions (e.g., moving post to trash)
  window.grindsBypassUnload = false;

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

  const setDirty = () => {
    if (!isDirty) {
      isDirty = true;
      document.title = '* ' + originalTitle;
    }
  };

  formsToWatch.forEach((form) => {
    // Listen for any changes that modify form data.
    form.addEventListener('input', setDirty);
    form.addEventListener('change', setDirty);

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
