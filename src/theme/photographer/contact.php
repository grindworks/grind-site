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
        $error = theme_t('Invalid request.');
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
                $error = theme_t('Invalid request.');
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
                    $error = theme_t('Failed to send message.');
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
    <?php get_template_part('parts/hero'); ?>

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
                <p class="text-gray-500 text-sm leading-relaxed"><?= $success ?></p>
                <a href="<?= site_url() ?>" class="inline-block mt-8 pb-0.5 border-black hover:border-gray-500 border-b hover:text-gray-500 text-xs uppercase tracking-widest transition">
                    <?= theme_t('Back to Home') ?>
                </a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="bg-red-50 mb-8 p-4 text-red-600 text-sm text-center">
                    <?php if (!$maintenanceMode && empty($recipientEmail)): ?><span class="font-bold">⚠️ <?= theme_t('Admin Preview') ?>:</span> <?php endif; ?>
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

                <?php foreach ($formFields as $key => $field): ?>
                    <?php
                    $val = h($formData[$key] ?? '');
                    $reqLabel = !empty($field['required']) ? '<span class="text-red-500 ml-1">*</span>' : '';
                    $ph = h($field['placeholder'] ?? '');
                    ?>
                    <div class="<?= h($field['width'] ?? 'w-full') ?>">
                        <label class="block mb-3 font-bold text-gray-400 text-xs uppercase tracking-widest" for="field-<?= $key ?>">
                            <?= h($field['label']) ?><?= $reqLabel ?>
                        </label>

                        <?php if ($field['type'] === 'textarea'): ?>
                            <textarea name="<?= $key ?>" id="field-<?= $key ?>" rows="<?= $field['rows'] ?? 5 ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>" class="bg-transparent py-2 border-gray-300 focus:border-black border-b outline-none w-full font-serif leading-relaxed transition-colors resize-none"><?= $val ?></textarea>
                        <?php elseif ($field['type'] === 'select'): ?>
                            <select name="<?= $key ?>" id="field-<?= $key ?>" <?= !empty($field['required']) ? 'required' : '' ?> class="bg-transparent py-2 border-gray-300 focus:border-black border-b outline-none w-full font-serif transition-colors appearance-none rounded-none cursor-pointer">
                                <option value=""><?= theme_t('Please select') ?></option>
                                <?php foreach ($field['options'] as $opt): ?>
                                    <option value="<?= h($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="<?= h($field['type']) ?>" name="<?= $key ?>" id="field-<?= $key ?>" value="<?= $val ?>" <?= !empty($field['required']) ? 'required' : '' ?> placeholder="<?= $ph ?>" class="bg-transparent py-2 border-gray-300 focus:border-black border-b outline-none w-full font-serif transition-colors rounded-none">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="pt-4 pb-2">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="hidden" name="privacy_check" value="1">
                        <input type="checkbox" name="privacy" value="1" required class="w-5 h-5 text-black border-gray-300 focus:ring-black transition rounded-none bg-transparent">
                        <span class="text-sm text-gray-500 font-serif">
                            <?= theme_t('I agree to the <a href="%s" target="_blank" class="text-black underline hover:no-underline">Privacy Policy</a>', h(resolve_url('/privacy-policy'))) ?>
                        </span>
                    </label>
                </div>

                <div class="pt-8 text-center">
                    <?php if (empty($recipientEmail)): ?>
                        <button type="button" disabled class="inline-block bg-gray-300 px-12 py-4 font-bold text-white text-xs uppercase tracking-widest cursor-not-allowed">
                            <?= $maintenanceMode ? theme_t('Maintenance') : theme_t('Setup Incomplete') ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" class="inline-block bg-black hover:bg-gray-800 px-12 py-4 font-bold text-white text-xs uppercase tracking-widest transition-colors">
                            <?= theme_t('Send Message') ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

        <?php endif; ?>
    </div>
</article>
