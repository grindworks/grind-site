<?php

if (!defined('GRINDS_APP')) exit;

/**
 * button.php
 *
 * Renders a button block using Bootstrap CSS classes, overriding the default.
 */
// Prepare button data.
$text = h($text ?? 'Button');
$url = resolve_url($url ?? '#');
$target = !empty($external) ? 'target="_blank" rel="noopener"' : '';
$color = $color ?? 'primary';

// Map color to Bootstrap button class.
$btnClass = match ($color) {
  'primary' => 'btn-primary',
  'success' => 'btn-success',
  'danger'  => 'btn-danger',
  'dark'    => 'btn-dark',
  default   => 'btn-primary',
};
?>

<!-- Render button HTML -->
<div class="text-center my-4">
  <a href="<?= $url ?>" <?= $target ?> class="btn <?= $btnClass ?> btn-lg px-5 rounded-pill shadow-sm">
    <?= $text ?>
  </a>
</div>
