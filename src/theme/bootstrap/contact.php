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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $error = theme_t('Invalid Request.');
  } elseif (!empty($_POST['website'])) {
    $success = theme_t('Inquiry accepted. (Spam detected)');
  } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $messageBody = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($messageBody)) {
      $error = theme_t('Please fill in all fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = theme_t('Invalid email address.');
    } else {
      try {
        $mailer = new SimpleMailer();
        $adminEmail = get_option('smtp_admin_email');
        $siteName = get_option('site_name');

        if (empty($adminEmail)) {
          $error = theme_t('Cannot send because admin email is not configured.');
        } else {
          $subject = theme_t('[%s] Inquiry from %s', $siteName, $name);
          $body = theme_t('contact_admin_body', $name, $email, $messageBody);

          if ($mailer->send($adminEmail, $subject, $body)) {
            try {
              $replySubject = theme_t('[%s] Thank you for your inquiry', $siteName);
              $replyBody = theme_t('contact_reply_body', $name, $messageBody);
              $mailer->send($email, $replySubject, $replyBody);
            } catch (Exception $e) {
              if (class_exists('GrindsLogger')) {
                GrindsLogger::log('Contact form auto-reply error: ' . $e->getMessage(), 'WARNING');
              }
            }

            $success = theme_t('Your inquiry has been sent. Thank you.');
            $name = '';
            $email = '';
            $messageBody = '';
          } else {
            $error = theme_t('Failed to send email. Please check system settings.');
          }
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
    <div class="alert alert-success text-center py-5">
      <h4 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i><?= theme_t('Sent Successfully') ?></h4>
      <p><?= h($success) ?></p>
      <hr>
      <a href="<?= h(resolve_url('/')) ?>" class="btn btn-outline-success"><?= theme_t('Back to Home') ?></a>
    </div>
  <?php else: ?>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= h($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="post" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <!-- Honeypot field for spam protection -->
      <div style="position: absolute; left: -9999px;" aria-hidden="true">
        <label for="website">Website <input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <div class="mb-3">
        <label for="name" class="form-label fw-bold"><?= theme_t('Name') ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="<?= h($name) ?>" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label fw-bold"><?= theme_t('Email') ?> <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" value="<?= h($email) ?>" required>
      </div>

      <div class="mb-4">
        <label for="message" class="form-label fw-bold"><?= theme_t('Message') ?> <span class="text-danger">*</span></label>
        <textarea class="form-control" id="message" name="message" rows="6" required><?= h($messageBody) ?></textarea>
      </div>

      <div class="d-grid gap-2 col-md-6 mx-auto">
        <button type="submit" class="btn btn-danger btn-lg shadow">
          <i class="bi bi-send me-2"></i><?= theme_t('Send') ?>
        </button>
      </div>
    </form>
  <?php endif; ?>
</article>
