<?php

/**
 * contact.php
 * Handle contact form.
 */
if (!defined('GRINDS_APP')) exit;

require_once ROOT_PATH . '/lib/mail.php';

$error = '';
$success = '';
$name = '';
$email = '';
$messageBody = '';

// Handle submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF.
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $error = theme_t('contact_err_req');
  }
  // Check honeypot.
  elseif (!empty($_POST['website'])) {
    $success = theme_t('contact_spam');
  }
  // Process submission.
  else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $messageBody = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($messageBody)) {
      $error = theme_t('contact_fill');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = theme_t('contact_email');
    } else {
      try {
        $mailer = new SimpleMailer();
        $adminEmail = get_option('smtp_admin_email');
        $siteName = get_option('site_name');

        if (empty($adminEmail)) {
          $error = theme_t('contact_no_admin');
        } else {
          // Notify admin.
          $subject = theme_t('contact_admin_subj', $siteName, $name);
          $body = theme_t('contact_admin_body', $name, $email, $messageBody);

          $sent = $mailer->send($adminEmail, $subject, $body);

          if ($sent) {
            // Send auto-reply.
            try {
              $replySubject = theme_t('contact_reply_subj', $siteName);
              $replyBody = theme_t('contact_reply_body', $name, $messageBody);
              $mailer->send($email, $replySubject, $replyBody);
            } catch (Exception $e) {
              if (class_exists('GrindsLogger')) {
                GrindsLogger::log('Marketing contact form auto-reply error: ' . $e->getMessage(), 'WARNING');
              }
            }

            $success = theme_t('contact_sent');
            $name = '';
            $email = '';
            $messageBody = '';
          } else {
            $error = theme_t('contact_fail');
          }
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
      <div class="bg-green-50 mb-6 px-4 py-4 border border-green-200 rounded text-green-700 text-center">
        <p class="mb-1 font-bold text-lg"><?= theme_t('contact_complete') ?></p>
        <p><?= h($success) ?></p>
        <a href="<?= h(resolve_url('/')) ?>" class="inline-block mt-4 hover:text-green-800 text-sm underline"><?= theme_t('contact_back') ?></a>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="bg-red-50 mb-6 px-4 py-3 border border-red-200 rounded text-red-600">
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-6 mx-auto max-w-2xl">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

        <!-- Honeypot field. -->
        <div style="display:none;">
          <label>Website <input type="text" name="website"></label>
        </div>

        <div>
          <label class="block mb-2 font-bold text-slate-700 text-sm"><?= theme_t('contact_name') ?> <span class="text-red-500">*</span></label>
          <input type="text" name="name" value="<?= h($name) ?>" required
            class="px-4 py-3 border border-slate-300 focus:border-brand-500 rounded outline-none focus:ring-2 focus:ring-brand-200 w-full transition">
        </div>

        <div>
          <label class="block mb-2 font-bold text-slate-700 text-sm"><?= theme_t('contact_email_lbl') ?> <span class="text-red-500">*</span></label>
          <input type="email" name="email" value="<?= h($email) ?>" required
            class="px-4 py-3 border border-slate-300 focus:border-brand-500 rounded outline-none focus:ring-2 focus:ring-brand-200 w-full transition">
        </div>

        <div>
          <label class="block mb-2 font-bold text-slate-700 text-sm"><?= theme_t('contact_body') ?> <span class="text-red-500">*</span></label>
          <textarea name="message" rows="6" required
            class="px-4 py-3 border border-slate-300 focus:border-brand-500 rounded outline-none focus:ring-2 focus:ring-brand-200 w-full transition"><?= h(isset($messageBody) ? $messageBody : '') ?></textarea>
        </div>

        <div class="pt-4 text-center">
          <button type="submit" class="bg-brand-600 hover:bg-brand-700 shadow px-10 py-4 rounded font-bold text-white hover:scale-[1.02] transition transform">
            <?= theme_t('contact_btn') ?>
          </button>
        </div>
      </form>

    <?php endif; ?>
  </div>
</article>
