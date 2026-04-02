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
    $error = theme_t('contact_err_req', 'Invalid request.');
  }
  // Check honeypot.
  elseif (!empty($_POST['website'])) {
    $success = theme_t('contact_spam', 'Spam detected.');
  }
  // Process submission.
  else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $messageBody = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($messageBody)) {
      $error = theme_t('contact_fill', 'Please fill in all fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = theme_t('contact_email', 'Invalid email address.');
    } else {
      try {
        $mailer = new SimpleMailer();
        $adminEmail = get_option('smtp_admin_email');
        $siteName = get_option('site_name');

        if (empty($adminEmail)) {
          $error = theme_t('contact_no_admin', 'Admin email not configured.');
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
                GrindsLogger::log('Corporate contact form auto-reply error: ' . $e->getMessage(), 'WARNING');
              }
            }

            $success = theme_t('contact_sent', 'Message sent successfully.');
            $name = '';
            $email = '';
            $messageBody = '';
          } else {
            $error = theme_t('contact_fail', 'Failed to send message.');
          }
        }
      } catch (Exception $e) {
        if (class_exists('GrindsLogger')) {
          GrindsLogger::log('Contact form error: ' . $e->getMessage(), 'ERROR');
        }
        $error = theme_t('contact_fail', 'Failed to send message.');
      }
    }
  }
}
?>

<article class="bg-white shadow-sm border border-corp-border rounded-lg overflow-hidden">

  <header class="p-8 border-gray-100 border-b">
    <h1 class="font-bold text-corp-main text-2xl md:text-3xl leading-tight">
      <?= h($pageData['post']['title']) ?>
    </h1>
  </header>

  <div class="p-8 md:p-12">

    <!-- Render page content -->
    <div class="mx-auto mb-8 max-w-none text-gray-700 prose prose-lg">
      <?= render_content($pageData['post']['content']) ?>
    </div>

    <?php if ($success): ?>
      <div class="bg-green-50 mb-6 px-4 py-4 border border-green-200 rounded text-green-700 text-center">
        <p class="mb-1 font-bold text-lg"><?= theme_t('contact_complete', 'Sent Successfully') ?></p>
        <p><?= h($success) ?></p>
        <a href="<?= h(resolve_url('/')) ?>" class="inline-block mt-4 hover:text-green-800 text-sm underline"><?= theme_t('contact_back', 'Back to Home') ?></a>
      </div>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="bg-red-50 mb-6 px-4 py-3 border border-red-200 rounded text-red-600">
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-6 max-w-2xl" onsubmit="
        var btn = this.querySelector('button[type=submit]');
        if(btn) {
            btn.disabled = true;
            btn.innerHTML = '<?= theme_t('Processing...') ?>';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
        }
      ">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

        <!-- Honeypot field for spam protection -->
        <div class="sr-only" aria-hidden="true">
          <label for="website">Website <input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div>
          <label class="block mb-2 font-bold text-gray-700 text-sm"><?= theme_t('contact_name', 'Name') ?> <span class="text-red-500">*</span></label>
          <input type="text" name="name" value="<?= h($name) ?>" required
            class="px-4 py-3 border border-gray-300 focus:border-corp-accent rounded outline-none focus:ring-1 focus:ring-corp-accent w-full transition">
        </div>

        <div>
          <label class="block mb-2 font-bold text-gray-700 text-sm"><?= theme_t('contact_email_lbl', 'Email') ?> <span class="text-red-500">*</span></label>
          <input type="email" name="email" value="<?= h($email) ?>" required
            class="px-4 py-3 border border-gray-300 focus:border-corp-accent rounded outline-none focus:ring-1 focus:ring-corp-accent w-full transition">
        </div>

        <div>
          <label class="block mb-2 font-bold text-gray-700 text-sm"><?= theme_t('contact_body', 'Message') ?> <span class="text-red-500">*</span></label>
          <textarea name="message" rows="6" required
            class="px-4 py-3 border border-gray-300 focus:border-corp-accent rounded outline-none focus:ring-1 focus:ring-corp-accent w-full transition"><?= h($messageBody) ?></textarea>
        </div>

        <div class="pt-4">
          <button type="submit" class="bg-corp-accent hover:opacity-90 shadow px-10 py-4 rounded font-bold text-white hover:scale-[1.02] transition transform">
            <?= theme_t('contact_btn', 'Send Message') ?>
          </button>
        </div>
      </form>

    <?php endif; ?>
  </div>
</article>
