<?php

/**
 * hidden_action_form.php
 * Renders a hidden form for bulk actions.
 */
if (!defined('GRINDS_APP')) exit; ?>
<form id="unified-action-form" method="post" action="<?= h($formAction ?? '') ?>" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= h($csrf_token ?? '') ?>">
    <input type="hidden" name="bulk_action" id="form-action-input" value="">
    <?php if (isset($extra_inputs)) echo $extra_inputs; ?>
</form>
