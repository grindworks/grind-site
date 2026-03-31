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
        $error = theme_t('Invalid request.');
    }
    // Check honeypot.
    elseif (!empty($_POST['website'])) {
        $success = theme_t('Spam detected.');
    }
    // Process submission.
    else {
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
                    $error = theme_t('Admin email not configured.');
                } else {
                    // Notify admin.
                    $subject = theme_t('[%s] Inquiry from %s', $siteName, $name);
                    $body = theme_t('contact_admin_body', $name, $email, $messageBody);

                    $sent = $mailer->send($adminEmail, $subject, $body);

                    if ($sent) {
                        // Send auto-reply.
                        try {
                            $replySubject = theme_t('[%s] Thank you for your inquiry', $siteName);
                            $replyBody = theme_t('contact_reply_body', $name, $messageBody);
                            $mailer->send($email, $replySubject, $replyBody);
                        } catch (Exception $e) {
                            if (class_exists('GrindsLogger')) {
                                GrindsLogger::log('Photographer contact form auto-reply error: ' . $e->getMessage(), 'WARNING');
                            }
                        }

                        $success = theme_t('Message sent successfully.');
                        $name = '';
                        $email = '';
                        $messageBody = '';
                    } else {
                        $error = theme_t('Failed to send message.');
                    }
                }
            } catch (Exception $e) {
                if (class_exists('GrindsLogger')) {
                    GrindsLogger::log('Contact form error: ' . $e->getMessage(), 'ERROR');
                }
                $error = theme_t('Failed to send message.');
            }
        }
    }
}

$post = $pageData['post'];
$heroSettings = json_decode($post['hero_settings'] ?? '{}', true);
$hasHero = !empty($post['hero_image']);
$hasHeroTitle = $hasHero && !empty($heroSettings['title']);
?>

<article class="mx-auto max-w-6xl animate-in duration-700 fade-in">

    <!-- Include hero. -->
    <?php include __DIR__ . '/parts/hero.php'; ?>

    <header class="mb-12 md:mb-16 text-center">
        <?php if (!$hasHeroTitle): ?>
            <h1 class="mb-6 font-serif font-medium text-3xl md:text-4xl italic">
                <?= h($post['title']) ?>
            </h1>
        <?php endif; ?>
    </header>

    <div class="mx-auto max-w-2xl">
        <!-- Render content. -->
        <div class="mb-12 font-light text-gray-800 text-center prose prose-lg">
            <?= render_content($post['content']) ?>
        </div>

        <?php if ($success): ?>
            <div class="bg-gray-50 p-10 text-center">
                <p class="mb-4 font-serif text-xl italic"><?= theme_t('Sent Successfully') ?></p>
                <p class="text-gray-500 text-sm"><?= h($success) ?></p>
                <a href="<?= site_url() ?>" class="inline-block mt-8 pb-0.5 border-black hover:border-gray-500 border-b hover:text-gray-500 text-xs uppercase tracking-widest transition">
                    <?= theme_t('Back to Home') ?>
                </a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="bg-red-50 mb-8 p-4 text-red-600 text-sm text-center">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-10" onsubmit="
              var btn = this.querySelector('button[type=submit]');
              if(btn) {
                  btn.disabled = true;
                  btn.innerHTML = '<?= theme_t('Processing...') ?>';
                  btn.classList.add('opacity-70', 'cursor-not-allowed');
              }
            ">
                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

                <!-- Honeypot. -->
                <div style="display:none;">
                    <label>Website <input type="text" name="website"></label>
                </div>

                <div class="gap-10 grid grid-cols-1 md:grid-cols-2">
                    <div>
                        <label class="block mb-3 font-bold text-gray-400 text-xs uppercase tracking-widest"><?= theme_t('Name') ?> *</label>
                        <input type="text" name="name" value="<?= h($name) ?>" required
                            class="bg-transparent py-2 border-gray-300 focus:border-black border-b outline-none w-full font-serif transition-colors">
                    </div>

                    <div>
                        <label class="block mb-3 font-bold text-gray-400 text-xs uppercase tracking-widest"><?= theme_t('Email') ?> *</label>
                        <input type="email" name="email" value="<?= h($email) ?>" required
                            class="bg-transparent py-2 border-gray-300 focus:border-black border-b outline-none w-full font-serif transition-colors">
                    </div>
                </div>

                <div>
                    <label class="block mb-3 font-bold text-gray-400 text-xs uppercase tracking-widest"><?= theme_t('Message') ?> *</label>
                    <textarea name="message" rows="6" required
                        class="bg-transparent py-2 border-gray-300 focus:border-black border-b outline-none w-full font-serif leading-relaxed transition-colors resize-none"><?= h(isset($messageBody) ? $messageBody : '') ?></textarea>
                </div>

                <div class="pt-8 text-center">
                    <button type="submit" class="inline-block bg-black hover:bg-gray-800 px-12 py-4 font-bold text-white text-xs uppercase tracking-widest transition-colors">
                        <?= theme_t('Send Message') ?>
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</article>
