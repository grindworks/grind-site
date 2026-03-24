<?php

/**
 * limit_selector.php
 * Renders the pagination limit selector dropdown.
 */
if (!defined('GRINDS_APP')) exit;

// Define limit options
$options = [10, 20, 50, 100];
?>
<div class="flex items-center text-sm whitespace-nowrap">
  <label class="mr-2 text-theme-text opacity-70 text-xs font-bold"><?= _t('lbl_items_per_page') ?></label>

  <select onchange="applyFilter('limit', this.value)" class="form-control-sm w-auto cursor-pointer bg-theme-bg border-theme-border text-theme-text">
    <?php foreach ($options as $opt): ?>
      <option value="<?= $opt ?>" <?= (isset($limit) && $limit == $opt) ? 'selected' : '' ?>><?= $opt ?> <?= _t('lbl_items') ?></option>
    <?php endforeach; ?>
  </select>
</div>
