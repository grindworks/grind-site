<?php

/**
 * contact.php
 * Handle contact form.
 */
if (!defined('GRINDS_APP')) exit;
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
    'width' => 'w-100',
  ],
  'name' => [
    'type' => 'text',
    'label' => theme_t('Name'),
    'required' => true,
    'placeholder' => theme_t('John Doe'),
    'width' => 'w-100',
  ],
  'email' => [
    'type' => 'email',
    'label' => theme_t('Email Address'),
    'required' => true,
    'placeholder' => 'email@example.com',
    'width' => 'w-100',
  ],
  'tel' => [
    'type' => 'tel',
    'label' => theme_t('Phone Number'),
    'required' => false,
    'placeholder' => '03-1234-5678',
    'width' => 'w-100',
  ],
  'subject' => [
    'type' => 'select',
    'label' => theme_t('Subject'),
    'required' => true,
    'options' => $subjectOptions,
    'width' => 'w-100',
  ],
  'message' => [
    'type' => 'textarea',
    'label' => theme_t('Message'),
    'required' => true,
    'rows' => 6,
    'width' => 'w-100',
  ],
];

$error = '';
$success = '';
$formData = [];

$maintenanceMode = false;

if ($recipientEmail === '') {
  if (!empty($_SESSION['admin_logged_in'])) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$maintenanceMode) {
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $error = theme_t('Invalid Request.');
  }
  // Time trap (server-side strict check)
  elseif (isset($_SESSION['contact_form_init_time']) && (time() - $_SESSION['contact_form_init_time'] < 3)) {
    $success = nl2br(h($successMsgRaw)); // Fake success
  } elseif (!empty($_POST['website'])) {
    $success = nl2br(h($successMsgRaw)); // Fake success
  } else {
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

      if ($field['type'] === 'select' && $val !== '' && !in_array($val, $field['options'], true)) {
        $error = theme_t('Invalid Request.');
        $hasError = true;
      }

      $formData[$key] = strip_tags($val);
    }

    if (isset($_POST['privacy_check']) && empty($_POST['privacy'])) {
      $error = theme_t('You must agree to the privacy policy.');
      $hasError = true;
    }

    if (!$hasError) {
      try {
        $mailer = new SimpleMailer();
        $userName = $formData['name'] ?? 'Guest';
        $userEmail = $formData['email'] ?? '';

        $mailBody = "";
        $isJa = (function_exists('grinds_get_current_language') && grinds_get_current_language() === 'ja') || get_option('site_lang', 'en') === 'ja';
        $bracketOpen = $isJa ? '【' : '[';
        $bracketClose = $isJa ? '】' : ']';

        foreach ($formFields as $key => $field) {
          $label = $field['label'];
          $val = $formData[$key] ?? '';
          $mailBody .= "{$bracketOpen}{$label}{$bracketClose}\n{$val}\n\n";
        }

        $selectedSubject = $formData['subject'] ?? 'New Inquiry';
        $subject = sprintf("[%s] %s", $siteName, $selectedSubject);
        $body = theme_t('contact_admin_body') . $mailBody;

        if ($mailer->send($recipientEmail, $subject, $body, $userEmail)) {
          if ($userEmail) {
            try {
              $replySubject = str_replace('{site_name}', $siteName, $autoReplySubject);
              $replyBody = str_replace(
                ['{site_name}', '{name}', '{form_details}'],
                [$siteName, $userName, $mailBody],
                $autoReplyBody
              );
              $mailer->send($userEmail, $replySubject, $replyBody, $recipientEmail);
            } catch (Exception $e) {
            }
          }

          $success = nl2br(h($successMsgRaw));
          $formData = [];
          $_SESSION['contact_form_init_time'] = time();
        } else {
          $error = theme_t('Failed to send email. Please check system settings.');
        }
      } catch (Exception $e) {
        if (class_exists('GrindsLogger')) {
          GrindsLogger::log('Contact form error: ' . $e->getMessage(), 'ERROR');
        }
        $error = theme_t('Failed to send email. Please check system settings.');
      }
    }
  }
}
?>

<article class="bg-white shadow-sm mb-5 p-4 p-md-5 border rounded">
  <div class="container mt-4">
    <?= get_breadcrumb_html([
      'wrapper_class' => 'breadcrumb',
      'item_class'    => 'breadcrumb-item',
      'link_class'    => 'text-decoration-none',
      'active_class'  => 'active',
      'separator'     => ''
    ]) ?>
  </div>

  <header class="mb-5 text-center">
    <h1 class="mb-4 display-5 fw-bold"><?= h($pageData['post']['title']) ?></h1>
  </header>

  <div class="post-content mb-5">
    <?= render_content($pageData['post']['content']) ?>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success text-center py-5 shadow-sm">
      <h4 class="alert-heading mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= theme_t('Sent Successfully') ?></h4>
      <p class="lead mb-0"><?= $success ?></p>
      <hr class="my-4">
      <a href="<?= h(resolve_url('/')) ?>" class="btn btn-outline-success px-4 rounded-pill fw-bold"><?= theme_t('Back to Home') ?></a>
    </div>
  <?php else: ?>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= h($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" action="?" class="needs-validation" novalidate onsubmit="
      var btn = this.querySelector('button[type=submit]');
      if(btn) {
          btn.disabled = true;
          btn.innerHTML = '<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\' aria-hidden=\'true\'></span><?= theme_t('Processing...') ?>';
          btn.classList.add('disabled');
      }
    ">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

      <!-- Honeypot field for spam protection -->
      <div style="position: absolute; left: -9999px;" aria-hidden="true">
        <label for="website">Website <input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <?php foreach ($formFields as $key => $field): ?>
        <?php
        $val = h($formData[$key] ?? '');
        $reqLabel = !empty($field['required']) ? '<span class="text-danger ms-1">*</span>' : '';
        $ph = h($field['placeholder'] ?? '');
        ?>
        <div class="mb-3 <?= h($field['width'] ?? '') ?>">
          <label class="form-label fw-bold" for="field-<?= $key ?>">
            <?= h($field['label']) ?><?= $reqLabel ?>
          </label>

          <?php if ($field['type'] === 'textarea'): ?>
            <textarea name="<?= $key ?>" id="field-<?= $key ?>" rows="<?= $field['rows'] ?? 5 ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>" class="form-control"><?= $val ?></textarea>
          <?php elseif ($field['type'] === 'select'): ?>
            <select name="<?= $key ?>" id="field-<?= $key ?>" <?= !empty($field['required']) ? 'required' : '' ?> class="form-select">
              <option value=""><?= theme_t('Please select') ?></option>
              <?php foreach ($field['options'] as $opt): ?>
                <option value="<?= h($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="<?= h($field['type']) ?>" name="<?= $key ?>" id="field-<?= $key ?>" value="<?= $val ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>" class="form-control">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="form-check mb-4 mt-2">
        <input type="hidden" name="privacy_check" value="1">
        <?php $privacyChecked = !empty($_POST['privacy']) ? 'checked' : ''; ?>
        <input class="form-check-input" type="checkbox" name="privacy" value="1" id="privacyCheck" required <?= $privacyChecked ?>>
        <label class="form-check-label text-muted" for="privacyCheck">
          <?= theme_t('I agree to the <a href="%s" target="_blank" class="text-decoration-underline">Privacy Policy</a>', h(resolve_url('/privacy-policy'))) ?>
        </label>
      </div>

      <div class="d-grid gap-2 col-md-6 mx-auto">
        <?php if (empty($recipientEmail)): ?>
          <button type="button" disabled class="btn btn-secondary btn-lg disabled shadow-sm">
            <i class="bi bi-lock me-2"></i><?= $maintenanceMode ? theme_t('Maintenance') : theme_t('Setup Incomplete') ?>
          </button>
        <?php else: ?>
          <button type="submit" class="btn btn-danger btn-lg shadow">
            <i class="bi bi-send me-2"></i><?= theme_t('Send') ?>
          </button>
        <?php endif; ?>
      </div>
    </form>
  <?php endif; ?>
</article>
