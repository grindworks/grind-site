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

// Handle submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$maintenanceMode) {
  // Validate CSRF.
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $error = theme_t('contact_err_req');
  }
  // Time trap (server-side strict check)
  elseif (isset($_SESSION['contact_form_init_time']) && (time() - $_SESSION['contact_form_init_time'] < 3)) {
    $success = nl2br(h($successMsgRaw)); // Fake success
  }
  // Check honeypot.
  elseif (!empty($_POST['website'])) {
    $success = nl2br(h($successMsgRaw)); // Fake success
  }
  // Process submission.
  else {
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
        $error = theme_t('contact_err_req');
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

        $sent = $mailer->send($recipientEmail, $subject, $body, $userEmail);

        if ($sent) {
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
          $error = theme_t('contact_fail');
        }
      } catch (Exception $e) {
        if (class_exists('GrindsLogger')) {
          GrindsLogger::log('Contact form error: ' . $e->getMessage(), 'ERROR');
        }
        $error = theme_t('contact_fail');
      }
    }
  }
}
?>

<article>
  <div class="max-w-4xl mt-8">
    <?= get_breadcrumb_html([
      'wrapper_class' => 'flex flex-wrap text-sm text-slate-500',
      'link_class'    => 'hover:text-brand-600 transition-colors',
      'active_class'  => 'font-bold text-slate-700',
      'separator'     => '<span class="mx-2 text-slate-300">/</span>'
    ]) ?>
  </div>

  <header class="max-w-4xl text-center mb-16 pt-12">
    <h1 class="text-3xl md:text-5xl font-black text-slate-900 leading-tight mb-8 font-heading">
      <?= h($pageData['post']['title']) ?>
    </h1>
  </header>

  <div class="mx-auto max-w-3xl">

    <!-- Render content. -->
    <div class="mx-auto mb-8 max-w-none text-slate-700 prose prose-lg">
      <?= render_content($pageData['post']['content']) ?>
    </div>

    <?php if ($success): ?>
      <div class="bg-green-50 mb-6 px-6 py-8 border border-green-200 rounded text-green-700 text-center shadow-inner">
        <div class="flex justify-center mb-4">
          <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="mb-4 font-bold text-lg"><?= theme_t('contact_complete') ?></p>
        <p class="text-base leading-relaxed"><?= $success ?></p>
        <a href="<?= h(resolve_url('/')) ?>" class="inline-block mt-6 hover:text-green-800 text-sm underline font-bold"><?= theme_t('contact_back') ?></a>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="bg-red-50 mb-6 px-4 py-3 border border-red-200 rounded text-red-600">
          <?php if (!$maintenanceMode && empty($recipientEmail)): ?><span class="font-bold">⚠️ <?= theme_t('Admin Preview') ?>:</span> <?php endif; ?>
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-6 mx-auto max-w-2xl" onsubmit="
        var btn = this.querySelector('button[type=submit]');
        if(btn) {
            btn.disabled = true;
            btn.innerHTML = '<?= theme_t('Processing...') ?>';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
        }
      ">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

        <!-- Honeypot field. -->
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
            <label class="block mb-2 font-bold text-slate-700 text-sm" for="field-<?= $key ?>">
              <?= h($field['label']) ?><?= $reqLabel ?>
            </label>

            <?php if ($field['type'] === 'textarea'): ?>
              <textarea name="<?= $key ?>" id="field-<?= $key ?>" rows="<?= $field['rows'] ?? 5 ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>" class="px-4 py-3 border border-slate-300 focus:border-brand-500 rounded outline-none focus:ring-2 focus:ring-brand-200 w-full transition bg-white"></textarea>
            <?php elseif ($field['type'] === 'select'): ?>
              <select name="<?= $key ?>" id="field-<?= $key ?>" <?= !empty($field['required']) ? 'required' : '' ?> class="px-4 py-3 border border-slate-300 focus:border-brand-500 rounded outline-none focus:ring-2 focus:ring-brand-200 w-full transition bg-white cursor-pointer">
                <option value=""><?= theme_t('Please select') ?></option>
                <?php foreach ($field['options'] as $opt): ?>
                  <option value="<?= h($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="<?= h($field['type']) ?>" name="<?= $key ?>" id="field-<?= $key ?>" value="<?= $val ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>" class="px-4 py-3 border border-slate-300 focus:border-brand-500 rounded outline-none focus:ring-2 focus:ring-brand-200 w-full transition bg-white">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="pt-4 pb-2">
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="hidden" name="privacy_check" value="1">
            <input type="checkbox" name="privacy" value="1" required class="w-5 h-5 text-brand-600 border-slate-300 rounded focus:ring-brand-500 transition bg-white">
            <span class="text-sm text-slate-700">
              <?= theme_t('I agree to the <a href="%s" target="_blank" class="text-brand-600 underline hover:no-underline">Privacy Policy</a>', h(resolve_url('/privacy-policy'))) ?>
            </span>
          </label>
        </div>

        <div class="pt-4 text-center">
          <?php if (empty($recipientEmail)): ?>
            <button type="button" disabled class="bg-slate-300 shadow px-10 py-4 rounded font-bold text-white cursor-not-allowed">
              <?= $maintenanceMode ? theme_t('Maintenance') : theme_t('Setup Incomplete') ?>
            </button>
          <?php else: ?>
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 shadow px-10 py-4 rounded font-bold text-white hover:scale-[1.02] transition transform">
              <?= theme_t('contact_btn') ?>
            </button>
          <?php endif; ?>
        </div>
      </form>

    <?php endif; ?>
  </div>
</article>
