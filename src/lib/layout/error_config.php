<?php

/**
 * Render error page template
 * Display user-friendly error page for system failures.
 */
if (!defined('GRINDS_APP')) exit; ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?> | GrindSite</title>
    <style>
        /* Standalone CSS for Offline/Intranet Support */
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans JP", sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            background-color: #ffffff;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.4rem;
            width: 100%;
            max-width: 28rem;
            text-align: center;
        }

        .icon-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fef2f2;
            margin: 0 auto 1.5rem auto;
            border-radius: 9999px;
            box-shadow: 0 0 0 1px #fee2e2;
            width: 4rem;
            height: 4rem;
        }

        .icon-wrapper svg {
            width: 2rem;
            height: 2rem;
            color: #dc2626;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 700;
            color: #0f172a;
            font-size: 1.5rem;
            letter-spacing: -0.025em;
        }

        .status {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #dc2626;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .message-box {
            background-color: #fef2f2;
            margin-bottom: 2rem;
            padding: 1rem;
            border: 1px solid #fee2e2;
            border-radius: 0.4rem;
            text-align: left;
            display: flex;
            align-items: flex-start;
        }

        .message-box svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #f87171;
            flex-shrink: 0;
        }

        .message-content {
            margin-left: 0.75rem;
            width: 100%;
        }

        .message-text {
            color: #b91c1c;
            font-size: 0.875rem;
            line-height: 1.625;
            margin: 0;
        }

        .message-text code {
            background-color: #fca5a5;
            color: #7f1d1d;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-family: monospace;
        }

        .hint-box {
            background-color: #fefce8;
            margin-top: 1rem;
            padding: 0.75rem;
            border: 1px solid #fef08a;
            border-radius: 0.25rem;
            color: #92400e;
            font-size: 0.875rem;
        }

        .log-info {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(254, 226, 226, 0.6);
        }

        .log-info-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #991b1b;
            text-transform: uppercase;
            margin: 0 0 0.125rem 0;
        }

        .log-info-path {
            font-family: monospace;
            font-size: 10px;
            color: #dc2626;
            word-break: break-all;
            margin: 0;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-primary {
            background-color: #0f62fe;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 0.875rem 1rem;
            border-radius: 0.4rem;
            width: 100%;
            font-weight: 700;
            color: #ffffff;
            font-size: 0.875rem;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-primary:hover {
            background-color: #0353e9;
        }

        .btn-primary svg {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        .btn-secondary {
            display: block;
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 0.875rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.4rem;
            width: 100%;
            font-weight: 700;
            color: #334155;
            font-size: 0.875rem;
            transition: background-color 0.2s;
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
        }

        .links-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
        }

        .links-title {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin: 0 0 0.75rem 0;
        }

        .links-list {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 0.75rem;
            color: #0f62fe;
            font-weight: 500;
        }

        .links-list a {
            color: #0f62fe;
            text-decoration: none;
        }

        .links-list a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
        }

        .footer p {
            font-weight: 500;
            color: #94a3b8;
            font-size: 0.75rem;
            letter-spacing: 0.025em;
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="icon-wrapper">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>

        <h1><?= $t['heading'] ?? $t['title'] ?></h1>

        <p class="status"><?= $t['status'] ?></p>

        <div class="message-box">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div class="message-content">
                <div class="message-text">
                    <?= $t['message'] ?? $t['msg'] ?>
                </div>
                <?php if (isset($t['log_info'])): ?>
                    <div class="log-info">
                        <p class="log-info-title"><?= $t['log_info'] ?></p>
                        <p class="log-info-path">src/data/logs/error.log</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions">
            <button onclick="location.reload()" class="btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <?= $t['reload'] ?? 'Reload' ?>
            </button>

            <?php if (isset($t['btn_back'])): ?>
                <a href="javascript:history.back()" class="btn-secondary">
                    <?= $t['btn_back'] ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (isset($t['action_title']) && !empty($actions)): ?>
            <div class="links-section">
                <p class="links-title"><?= $t['action_title'] ?></p>
                <div class="links-list">
                    <?php foreach ($actions as $i => $action): ?>
                        <a href="<?= htmlspecialchars($action['url']) ?>"><?= htmlspecialchars($action['label']) ?></a>
                        <?php if ($i < count($actions) - 1): ?><span>&bull;</span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>GrindSite</p>
        </div>

    </div>

</body>

</html>
