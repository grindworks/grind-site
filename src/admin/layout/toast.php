<?php

/**
 * toast.php
 *
 * Render success and error toast notifications.
 */
if (!defined('GRINDS_APP')) exit;

?>

<?php if (!empty($message)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (window.ToastManager) {
        window.ToastManager.show({
          message: <?= json_encode($message) ?>,
          type: 'success'
        });
      }
    });
  </script>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (window.ToastManager) {
        window.ToastManager.show({
          message: <?= json_encode($error) ?>,
          type: 'error'
        });
      }
    });
  </script>
<?php endif; ?>
