<?php

/**
 * contact.php
 * Handle contact form submission.
 */
// Prevent direct access
if (!defined('GRINDS_APP')) exit;

// Load mail library
require_once ROOT_PATH . '/lib/mail.php';

// Load settings
$siteName = get_option('site_name', 'GrindSite');
$adminEmail = trim((string)get_option('smtp_admin_email'));
$recipientEmail = trim((string)get_option('contact_recipient_email')) ?: $adminEmail;

$subjectsRaw = get_option('contact_subjects', "Product\nRecruitment\nOther");
$subjectOptions = array_filter(array_map('trim', explode("\n", $subjectsRaw)));

$successMsgRaw = get_option('contact_success_msg', theme_t('Message sent successfully.'));
$autoReplySubject = get_option('contact_autoreply_subject', '[{site_name}] Thank you for your inquiry');
$autoReplyBody = get_option('contact_autoreply_body', "Dear {name},\n\nThank you for your inquiry. We have received the following:\n\n{form_details}\n\nWe will get back to you shortly.");

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
    'options' => $subjectOptions,
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
$maintenanceMode = false;

if ($recipientEmail === '') {
  if (!empty($_SESSION['admin_logged_in'])) {
    // Allow admin to preview form even if email is not set.
    $error = theme_t('Warning: Admin email is not set. Preview only.');
  } else {
    $maintenanceMode = true;
    $error = theme_t('The contact form is currently under maintenance. Please try again later.');
  }
}

// 🛡️ Time trap: Record form generation time in session (tamper-proof)
if (session_status() === PHP_SESSION_NONE && function_exists('_safe_session_start')) {
  _safe_session_start();
}
if (!isset($_SESSION['contact_form_init_time']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['contact_form_init_time'] = time();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$maintenanceMode) {
  // Check CSRF token
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $error = theme_t('Invalid Request.');
  }
  // Time trap (server-side strict check)
  elseif (isset($_SESSION['contact_form_init_time']) && (time() - $_SESSION['contact_form_init_time'] < 3)) {
    $success = nl2br(h($successMsgRaw)); // Fake success
  }
  // Check honeypot for spam
  elseif (!empty($_POST['website'])) {
    $success = nl2br(h($successMsgRaw)); // Fake success
  }
  // Process form data
  else {
    // Validate and retrieve data.
    $hasError = false;
    foreach ($formFields as $key => $field) {
      $val = trim(Routing::getString($_POST, $key));
      $formData[$key] = $val;

      if (!empty($field['required']) && $val === '') {
        $error = theme_t('Please fill in required fields.');
        $hasError = true;
      }
      if ($field['type'] === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
        $error = theme_t('Invalid email address.');
        $hasError = true;
      }

      $formData[$key] = strip_tags($val);
    }

    // Check privacy policy agreement if applicable.
    if (isset($_POST['privacy_check']) && empty($_POST['privacy'])) {
      $error = theme_t('You must agree to the privacy policy.');
      $hasError = true;
    }

    if (!$hasError) {
      try {
        $mailer = new SimpleMailer();
        $userName = $formData['name'] ?? 'Guest';
        $userEmail = $formData['email'] ?? '';

        // Generate email body dynamically.
        $mailBody = "";
        foreach ($formFields as $key => $field) {
          $label = $field['label'];
          $val = $formData[$key] ?? '';
          $mailBody .= "【{$label}】\n{$val}\n\n";
        }

        // Send notification to admin
        $subject = theme_t('[%s] New Inquiry', $siteName);
        $body = theme_t('contact_admin_body') . $mailBody;

        $sent = $mailer->send($recipientEmail, $subject, $body, $userEmail);

        if ($sent) {
          if ($userEmail) {
            // Send auto-reply to user
            try {
              $replySubject = str_replace('{site_name}', $siteName, $autoReplySubject);
              $replyBody = str_replace(
                ['{site_name}', '{name}', '{form_details}'],
                [$siteName, $userName, $mailBody],
                $autoReplyBody
              );
              $mailer->send($userEmail, $replySubject, $replyBody);
            } catch (Exception $e) {
              if (class_exists('GrindsLogger')) {
                GrindsLogger::log('Neo-minimalist contact form auto-reply error: ' . $e->getMessage(), 'WARNING');
              }
            }
          }

          $success = nl2br(h($successMsgRaw));
          $formData = [];
          $_SESSION['contact_form_init_time'] = time();
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

<div class="bg-white border-2 border-slate-900 shadow-sharp overflow-hidden mb-16">

  <header class="p-8 md:p-12 pb-0 text-center border-b-2 border-slate-900 mb-10">
    <!-- Breadcrumbs -->
    <div class="mb-8 flex justify-center">
      <?= get_breadcrumb_html([
        'wrapper_class' => 'flex flex-wrap text-sm text-slate-500 font-bold uppercase tracking-widest',
        'item_class' => 'flex items-center',
        'link_class' => 'hover:text-brand-600 transition-colors',
        'separator' => '<span class="mx-3 text-slate-300">/</span>',
        'active_class' => 'text-slate-900 font-bold'
      ]) ?>
    </div>
    <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-extrabold leading-[1.1] tracking-tight mb-10 text-slate-900">
      <?= h($pageData['post']['title']) ?>
    </h1>
  </header>

  <div class="px-6 md:px-10 pb-10">

    <!-- Content -->
    <div class="mb-8 text-slate-800 leading-relaxed mx-auto max-w-4xl text-lg">
      <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
    </div>

    <?php if ($success): ?>
      <div class="bg-green-50 border-2 border-slate-900 shadow-sharp text-slate-900 px-6 py-10 mb-10 text-center">
        <div class="flex justify-center mb-4">
          <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-lg font-bold text-slate-800 leading-relaxed"><?= $success ?></p>
        <a href="<?= h(resolve_url('/')) ?>" class="inline-block mt-6 neo-btn neo-btn-secondary py-2 px-6"><?= theme_t('Back to Home') ?></a>
      </div>
    <?php else: ?>

      <div class="relative">
        <?php if ($error): ?>
          <div class="bg-red-50 border-2 border-slate-900 shadow-sharp text-red-700 px-6 py-4 mb-8 font-bold">
            <?php if (!$maintenanceMode && empty($recipientEmail)): ?><span>⚠️ <?= theme_t('Admin Preview') ?>:</span> <?php endif; ?>
            <?= h($error) ?>
          </div>
        <?php endif; ?>
        <form method="post" class="max-w-2xl mx-auto space-y-6" onsubmit="
          var btn = this.querySelector('button[type=submit]');
          if(btn) {
              btn.disabled = true;
              btn.innerHTML = '<?= theme_t('Processing...') ?>';
              btn.classList.add('opacity-70', 'cursor-not-allowed');
          }
        ">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

          <!-- Honeypot (Hidden via CSS) -->
          <div style="display:none;">
            <label>Website <input type="text" name="website"></label>
          </div>

          <?php foreach ($formFields as $key => $field): ?>
            <?php
            $val = h($formData[$key] ?? '');
            $reqLabel = !empty($field['required']) ? '<span class="text-red-500 ml-1">*</span>' : '';
            $ph = h($field['placeholder'] ?? '');
            ?>
            <div class="<?= h($field['width'] ?? 'w-full') ?>">
              <label class="block text-sm font-bold text-slate-900 uppercase tracking-widest mb-3" for="field-<?= $key ?>">
                <?= h($field['label']) ?><?= $reqLabel ?>
              </label>

              <?php if ($field['type'] === 'textarea'): ?>
                <textarea name="<?= $key ?>" id="field-<?= $key ?>" rows="<?= $field['rows'] ?? 5 ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>"
                  class="w-full px-5 py-4 border-2 border-slate-900 focus:shadow-[4px_4px_0_0_rgba(15,23,42,1)] rounded-none outline-none transition-shadow font-medium text-slate-900 placeholder-slate-400"></textarea>

              <?php elseif ($field['type'] === 'select'): ?>
                <select name="<?= $key ?>" id="field-<?= $key ?>" <?= !empty($field['required']) ? 'required' : '' ?>
                  class="w-full px-5 py-4 border-2 border-slate-900 focus:shadow-[4px_4px_0_0_rgba(15,23,42,1)] rounded-none outline-none transition-shadow font-medium text-slate-900 bg-white cursor-pointer appearance-none">
                  <option value=""><?= theme_t('Please select') ?></option>
                  <?php foreach ($field['options'] as $opt): ?>
                    <option value="<?= h($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                  <?php endforeach; ?>
                </select>

              <?php else: ?>
                <input type="<?= h($field['type']) ?>" name="<?= $key ?>" id="field-<?= $key ?>" value="<?= $val ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>"
                  class="w-full px-5 py-4 border-2 border-slate-900 focus:shadow-[4px_4px_0_0_rgba(15,23,42,1)] rounded-none outline-none transition-shadow font-medium text-slate-900 placeholder-slate-400">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <!-- Privacy policy agreement checkbox -->
          <div class="pt-4 pb-2">
            <label class="flex items-center gap-4 cursor-pointer group">
              <input type="hidden" name="privacy_check" value="1">
              <input type="checkbox" name="privacy" value="1" required class="w-6 h-6 text-brand-600 border-2 border-slate-900 rounded-none focus:ring-slate-900 focus:ring-2 focus:ring-offset-2 transition-all">
              <span class="text-base font-bold text-slate-900">
                <?= theme_t('I agree to the <a href="%s" target="_blank" class="text-brand-600 underline hover:no-underline">Privacy Policy</a>', h(resolve_url('/privacy-policy'))) ?>
              </span>
            </label>
          </div>

          <div class="text-center pt-8">
            <?php if (empty($recipientEmail)): ?>
              <button type="button" disabled class="neo-btn neo-btn-dark opacity-50 cursor-not-allowed w-full md:w-auto px-12 py-4 text-lg">
                <?= $maintenanceMode ? theme_t('Maintenance') : theme_t('Setup Incomplete') ?>
              </button>
            <?php else: ?>
              <button type="submit" class="neo-btn neo-btn-primary w-full md:w-auto px-12 py-4 text-lg">
                <?= theme_t('Send') ?>
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>

    <?php endif; ?>
  </div>
</div>
