<?php

/**
 * _mail_otp_2fa.php
 *
 * [English]
 * Two-Factor Authentication (2FA) via Email OTP (One-Time Password).
 * Requires valid SMTP settings in GrindSite to function.
 * If SMTP is not configured, it safely falls back to standard login (Fail-safe).
 *
 * [Japanese]
 * メールによるワンタイムパスワード(OTP)を利用した2要素認証プラグインです。
 * GrindSiteのSMTP設定が正しく完了している場合のみ動作します。
 * SMTPが未設定の場合は、安全のため通常のログインへフォールバックします。
 */
if (!defined('GRINDS_APP')) exit;

add_action('grinds_init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAdminArea = str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/');

    if (!$isAdminArea) return;

    // Allow logout to prevent users from getting stuck.
    // ユーザーがスタックするのを防ぐため、ログアウトは許可します
    if (str_contains($requestUri, 'logout.php') || str_contains($scriptName, 'logout.php')) return;

    // Check if the user has passed the primary login.
    // ユーザーがプライマリログイン（ID/パスワード）を通過しているか確認
    if (empty($_SESSION['admin_logged_in'])) return;

    // Get Site Language for UI and Emails
    // UIとメール用のサイト言語を取得
    $lang = get_option('site_lang', 'en');
    $isJa = ($lang === 'ja');

    // Fail-safe: Check if SMTP is configured.
    // フェイルセーフ: SMTPが設定されているか確認
    $smtpHost = get_option('smtp_host', '');
    $smtpFrom = get_option('smtp_from', '');

    if (empty($smtpHost) || empty($smtpFrom)) {
        $_SESSION['grinds_2fa_verified'] = true;
        return;
    }

    // Check if OTP is already verified.
    // OTPがすでに検証済みか確認
    if (!empty($_SESSION['grinds_2fa_verified']) && $_SESSION['grinds_2fa_verified'] === true) return;

    // Block AJAX requests with a 401 status if OTP is not verified.
    // OTPが未検証の場合、AJAXリクエストを401ステータスでブロック
    if (function_exists('is_ajax_request') && is_ajax_request()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => $isJa ? '2段階認証が必要です。' : '2FA verification required.']));
    }

    $errorMsg = '';
    $userEmail = App::user()['email'] ?? '';

    // If the user has no email set, block them to ensure military-grade security.
    // ユーザーにメールアドレスが設定されていない場合、セキュリティ確保のためブロックして強制ログアウト
    if (empty($userEmail)) {
        grinds_logout();
        die($isJa ? "セキュリティエラー: 2FA用のメールアドレスが未設定です。管理者に連絡してください。" : "Security Error: Your account does not have an email address configured for 2FA.");
    }

    // Handle OTP Submission.
    // OTPの送信処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code'])) {
        if (!function_exists('validate_csrf_token') || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $errorMsg = $isJa ? '無効なセキュリティトークンです。' : 'Invalid security token.';
        } else {
            $inputCode = preg_replace('/[^0-9]/', '', $_POST['otp_code']);
            $savedCode = $_SESSION['grinds_2fa_code'] ?? '';
            $expiresAt = $_SESSION['grinds_2fa_expires_at'] ?? 0;
            $attempts  = $_SESSION['grinds_2fa_attempts'] ?? 0;

            if (time() > $expiresAt) {
                $errorMsg = $isJa ? '認証コードの有効期限が切れました。ページを再読み込みして再発行してください。' : 'The authentication code has expired. Please request a new one.';
                unset($_SESSION['grinds_2fa_code']);
            } elseif ($inputCode === $savedCode && !empty($savedCode)) {
                // Success: Mark session as verified.
                // 成功: セッションを検証済みとしてマーク
                $_SESSION['grinds_2fa_verified'] = true;
                unset($_SESSION['grinds_2fa_code'], $_SESSION['grinds_2fa_expires_at'], $_SESSION['grinds_2fa_attempts']);

                // Redirect to the originally requested page
                // 元々リクエストされていたページへリダイレクト
                $dest = $_SERVER['REQUEST_URI'];
                header("Location: {$dest}");
                exit;
            } else {
                // Failure: Increment attempts.
                // 失敗: 試行回数を増やす
                $_SESSION['grinds_2fa_attempts'] = $attempts + 1;
                if ($_SESSION['grinds_2fa_attempts'] >= 3) {
                    grinds_logout();
                    die($isJa ? "セキュリティエラー: 認証失敗が規定回数を超えました。強制ログアウトしました。" : "Security Error: Too many failed 2FA attempts. You have been logged out.");
                }
                $errorMsg = $isJa ? '認証コードが正しくありません。' : 'Incorrect authentication code.';
            }
        }
    }

    // Generate and Send OTP (If not exists or expired).
    // OTPの生成と送信（存在しないか、有効期限が切れている場合）
    if (empty($_SESSION['grinds_2fa_code']) || time() > ($_SESSION['grinds_2fa_expires_at'] ?? 0)) {
        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $_SESSION['grinds_2fa_code'] = $code;
        $_SESSION['grinds_2fa_expires_at'] = time() + (10 * 60); // Valid for 10 minutes
        $_SESSION['grinds_2fa_attempts'] = 0;

        // Send Email
        // メール送信
        require_once ROOT_PATH . '/lib/mail.php';
        try {
            $mailer = new SimpleMailer();
            $siteName = get_option('site_name', 'GrindSite');

            if ($isJa) {
                $subject = "[{$siteName}] ログイン認証コード";
                $body = "ログイン認証コード (OTP) をお知らせします：\n\n【 {$code} 】\n\nこのコードの有効期限は10分間です。\nもし心当たりがない場合は、直ちにパスワードを変更してください。\n";
            } else {
                $subject = "[{$siteName}] Login Authentication Code";
                $body = "Hello,\n\nYour login authentication code (OTP) is:\n\n{$code}\n\nThis code is valid for 10 minutes.\nIf you did not attempt to log in, please secure your account immediately.\n";
            }

            $mailer->send($userEmail, $subject, $body);
        } catch (Exception $e) {
            $errorMsg = $isJa ? '認証メールの送信に失敗しました。SMTP設定を確認してください。' : 'Failed to send authentication email. Please check SMTP settings.';
            error_log("OTP Mail Error: " . $e->getMessage());
        }
    }

    // Render OTP Input Interface.
    // OTP入力インターフェースの描画
    $csrfToken = generate_csrf_token();
    $cssUrl = resolve_url('assets/css/admin.css');
    $maskedEmail = preg_replace('/(?<=.).(?=.*@)/', '*', $userEmail);

    http_response_code(401);
?>
    <!DOCTYPE html>
    <html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" class="h-full antialiased">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title><?= $isJa ? '2段階認証' : '2FA Verification' ?> | GrindSite</title>
        <link href="<?= htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
        <style>
            :root {
                --color-bg: 248 250 252;
                --color-surface: 255 255 255;
                --color-text: 15 23 42;
                --color-primary: 37 99 235;
                --color-border: 226 232 240;
                --border-radius: 0.5rem;
                --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            body {
                background-color: rgb(var(--color-bg));
                color: rgb(var(--color-text));
            }

            .glass-panel {
                background: rgb(var(--color-surface));
                border: 1px solid rgb(var(--color-border));
                box-shadow: var(--box-shadow);
                border-radius: var(--border-radius);
            }
        </style>
    </head>

    <body class="flex justify-center items-center px-4 py-12 min-h-full">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="flex justify-center items-center bg-blue-500/10 mx-auto mb-4 border border-blue-500/20 rounded-full w-16 h-16 text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-lock-closed"></use>
                    </svg>
                </div>
                <h2 class="font-bold text-2xl tracking-tight"><?= $isJa ? '2段階認証' : 'Two-Factor Authentication' ?></h2>
                <p class="opacity-60 mt-2 text-sm"><?= $isJa ? '登録されたメールアドレスに6桁のコードを送信しました。' : 'A 6-digit code has been sent to your email.' ?></p>
                <p class="font-mono text-xs font-bold text-blue-600 mt-1"><?= htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="glass-panel p-8">
                <?php if ($errorMsg): ?>
                    <div class="flex items-start gap-3 bg-red-500/10 mb-6 p-4 border border-red-500/20 rounded-lg text-red-600 text-sm font-bold">
                        <svg class="flex-shrink-0 w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
                        </svg>
                        <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div>
                        <label class="block opacity-80 mb-2 font-bold text-sm uppercase tracking-wider"><?= $isJa ? '認証コード' : 'Authentication Code' ?></label>
                        <input type="text" name="otp_code" required autofocus autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="block px-4 py-3 w-full text-center text-2xl tracking-[0.5em] font-mono border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="------">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-lg shadow-md transition-colors flex justify-center items-center gap-2">
                        <?= $isJa ? '認証する' : 'Verify Code' ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-check"></use>
                        </svg>
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                    <form method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="text-sm text-gray-500 hover:text-red-600 font-bold hover:underline transition-colors"><?= $isJa ? 'キャンセルしてログアウト' : 'Cancel & Logout' ?></button>
                    </form>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
});
