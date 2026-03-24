<?php

/**
 * contact.php
 * Handle contact form submission.
 */
// Prevent direct access
if (!defined('GRINDS_APP')) exit;

// Load mail library
require_once ROOT_PATH . '/lib/mail.php';

// Configure form fields.
$formFields = [
  'company' => [
    'type' => 'text',
    'label' => theme_t('Company Name'),
    'required' => false,
    'placeholder' => theme_t('Example Inc.'),
    'width' => 'w-full',
  ],
  'name' => [
    'type' => 'text',
    'label' => theme_t('Name'),
    'required' => true,
    'placeholder' => theme_t('John Doe'),
    'width' => 'w-full',
  ],
  'email' => [
    'type' => 'email',
    'label' => theme_t('Email Address'),
    'required' => true,
    'placeholder' => 'email@example.com',
    'width' => 'w-full',
  ],
  'tel' => [
    'type' => 'tel',
    'label' => theme_t('Phone Number'),
    'required' => false,
    'placeholder' => '03-1234-5678',
    'width' => 'w-full',
  ],
  'subject' => [
    'type' => 'select',
    'label' => theme_t('Subject'),
    'required' => true,
    'options' => [theme_t('Product'), theme_t('Recruitment'), theme_t('Other')],
    'width' => 'w-full',
  ],
  'message' => [
    'type' => 'textarea',
    'label' => theme_t('Message'),
    'required' => true,
    'rows' => 6,
    'width' => 'w-full',
  ],
];

$error = '';
$success = '';
$formData = [];

// Check configuration and maintenance mode.
$adminEmail = trim((string)get_option('smtp_admin_email'));
$maintenanceMode = false;

if ($adminEmail === '') {
  if (!empty($_SESSION['admin_logged_in'])) {
    // Allow admin to preview form even if email is not set.
    $error = theme_t('Warning: Admin email is not set. Preview only.');
  } else {
    $maintenanceMode = true;
    $error = theme_t('The contact form is currently under maintenance. Please try again later.');
  }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$maintenanceMode) {
  // Check CSRF token
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $error = theme_t('Invalid Request.');
  }
  // Check honeypot for spam
  elseif (!empty($_POST['website'])) {
    $success = theme_t('Inquiry accepted. (Spam detected)');
  }
  // Process form data
  else {
    // Validate and retrieve data.
    $hasError = false;
    foreach ($formFields as $key => $field) {
      $val = trim($_POST[$key] ?? '');
      $formData[$key] = $val;

      if (!empty($field['required']) && $val === '') {
        $error = theme_t('Please fill in required fields.');
        $hasError = true;
      }
      if ($field['type'] === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
        $error = theme_t('Invalid email address.');
        $hasError = true;
      }
    }

    // Check privacy policy agreement if applicable.
    if (isset($_POST['privacy_check']) && empty($_POST['privacy'])) {
      $error = theme_t('You must agree to the privacy policy.');
      $hasError = true;
    }

    if (!$hasError) {
      try {
        $mailer = new SimpleMailer();
        $siteName = get_option('site_name');
        $userName = $formData['name'] ?? 'Guest';
        $userEmail = $formData['email'] ?? '';

        // Generate email body dynamically.
        $mailBody = "";
        foreach ($formFields as $key => $field) {
          $label = $field['label'];
          $val = $formData[$key] ?? '';
          $mailBody .= "[{$label}]\n{$val}\n\n";
        }

        // Send notification to admin
        $subject = theme_t('[%s] New Inquiry', $siteName);
        $body = theme_t('contact_admin_body') . $mailBody;

        $sent = $mailer->send($adminEmail, $subject, $body);

        if ($sent) {
          if ($userEmail) {
            // Send auto-reply to user
            try {
              $replySubject = theme_t('[%s] Thank you for your inquiry', $siteName);
              $replyBody = theme_t('contact_reply_body', $userName) . $mailBody;
              $mailer->send($userEmail, $replySubject, $replyBody);
            } catch (Exception $e) {
              if (class_exists('GrindsLogger')) {
                GrindsLogger::log('Auto-reply failed: ' . $e->getMessage(), 'WARNING');
              }
            }
          }

          $success = theme_t('Message sent successfully.');
          $formData = [];
        } else {
          $error = theme_t('Failed to send email. Please check server settings or try again later.');
        }
      } catch (Exception $e) {
        if (class_exists('GrindsLogger')) {
          GrindsLogger::log('Contact form submission failed: ' . $e->getMessage(), 'ERROR');
        }
        $error = theme_t('Failed to send email. Please check server settings or try again later.');
      }
    }
  }
}
?>

<div class="bg-white rounded-lg shadow-lg overflow-hidden mb-10 border border-gray-100">

  <header class="p-6 md:p-10 pb-0 text-center">
    <!-- Breadcrumbs -->
    <div class="mb-6 flex justify-center">
      <?= get_breadcrumb_html([
        'wrapper_class' => 'flex flex-wrap text-sm text-gray-500',
        'item_class'    => 'flex items-center',
        'link_class'    => 'hover:text-grinds-red transition-colors',
        'separator'     => '<svg class="w-3 h-3 mx-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . resolve_url('assets/img/sprite.svg') . '#outline-chevron-right"></use></svg>',
        'active_class'  => 'text-gray-800 font-bold'
      ]) ?>
    </div>
    <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-6 text-grinds-dark">
      <?= h($pageData['post']['title']) ?>
    </h1>
  </header>

  <div class="p-6 md:p-10">

    <!-- Content -->
    <div class="mb-8 prose prose-lg max-w-none text-gray-700 mx-auto">
      <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
    </div>

    <?php if ($success): ?>
      <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-4 rounded mb-6 text-center">
        <p class="font-bold text-lg mb-1"><?= theme_t('Sent Successfully') ?></p>
        <p><?= h($success) ?></p>
        <a href="<?= h(resolve_url('/')) ?>" class="inline-block mt-4 text-sm underline hover:text-green-800"><?= theme_t('Back to Home') ?></a>
      </div>
    <?php else: ?>

      <div class="relative">
        <?php if ($error): ?>
          <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded mb-6">
            <?php if (!$maintenanceMode && empty($adminEmail)): ?><span class="font-bold">⚠️ <?= theme_t('Admin Preview') ?>:</span> <?php endif; ?>
            <?= h($error) ?>
          </div>
        <?php endif; ?>

        <form method="post" class="max-w-2xl mx-auto space-y-6">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

          <!-- Honeypot (Hidden via CSS) -->
          <div class="sr-only" aria-hidden="true">
            <label for="website">Website <input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <?php foreach ($formFields as $key => $field): ?>
            <?php
            $val = h($formData[$key] ?? '');
            $reqLabel = !empty($field['required']) ? '<span class="text-red-500 ml-1">*</span>' : '';
            $ph = h($field['placeholder'] ?? '');
            ?>
            <div class="<?= h($field['width'] ?? 'w-full') ?>">
              <label class="block text-sm font-bold text-gray-700 mb-2" for="field-<?= $key ?>">
                <?= h($field['label']) ?><?= $reqLabel ?>
              </label>

              <?php if ($field['type'] === 'textarea'): ?>
                <textarea name="<?= $key ?>" id="field-<?= $key ?>" rows="<?= $field['rows'] ?? 5 ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>"
                  class="w-full px-4 py-3 rounded border border-gray-300 focus:border-grinds-red focus:ring-1 focus:ring-grinds-red outline-none transition"><?= $val ?></textarea>

              <?php elseif ($field['type'] === 'select'): ?>
                <select name="<?= $key ?>" id="field-<?= $key ?>" <?= !empty($field['required']) ? 'required' : '' ?>
                  class="w-full px-4 py-3 rounded border border-gray-300 focus:border-grinds-red focus:ring-1 focus:ring-grinds-red outline-none transition bg-white">
                  <option value=""><?= theme_t('Please select') ?></option>
                  <?php foreach ($field['options'] as $opt): ?>
                    <option value="<?= h($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                  <?php endforeach; ?>
                </select>

              <?php else: ?>
                <input type="<?= h($field['type']) ?>" name="<?= $key ?>" id="field-<?= $key ?>" value="<?= $val ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>"
                  class="w-full px-4 py-3 rounded border border-gray-300 focus:border-grinds-red focus:ring-1 focus:ring-grinds-red outline-none transition">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <!-- Privacy policy agreement checkbox -->
          <div class="pt-2">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="hidden" name="privacy_check" value="1">
              <input type="checkbox" name="privacy" value="1" required class="w-5 h-5 text-grinds-red border-gray-300 rounded focus:ring-grinds-red">
              <span class="text-sm text-gray-700">
                <?= theme_t('I agree to the <a href="%s" target="_blank" class="text-grinds-red underline hover:no-underline">Privacy Policy</a>', h(resolve_url('/privacy-policy'))) ?>
              </span>
            </label>
          </div>

          <div class="text-center pt-4">
            <?php if (empty($adminEmail)): ?>
              <button type="button" disabled class="bg-gray-400 text-white font-bold px-10 py-4 rounded shadow cursor-not-allowed">
                <?= $maintenanceMode ? theme_t('Maintenance') : theme_t('Setup Incomplete') ?>
              </button>
            <?php else: ?>
              <button type="submit" class="bg-grinds-red text-white font-bold px-10 py-4 rounded shadow hover:bg-red-700 transition transform hover:scale-[1.02]">
                <?= theme_t('Send') ?>
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>

    <?php endif; ?>
  </div>
</div>
