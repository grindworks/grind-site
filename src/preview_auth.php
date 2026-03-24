<?php

/**
 * Render preview authentication page.
 */
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang ?? 'en', ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= htmlspecialchars($title ?? 'Preview Authentication', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg-color: #f8fafc;
            --text-color: #0f172a;
            --card-bg: #ffffff;
            --card-border: #f1f5f9;
            --icon-bg: #eff6ff;
            --icon-color: #3b82f6;
            --icon-border: #dbeafe;
            --text-muted: #64748b;
            --error-bg: #fef2f2;
            --error-text: #b91c1c;
            --error-border: #fecaca;
            --input-border: #cbd5e1;
            --input-bg: #ffffff;
            --input-focus-ring: rgba(59, 130, 246, 0.15);
            --btn-bg: #2563eb;
            --btn-hover: #1d4ed8;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0f172a;
                --text-color: #f8fafc;
                --card-bg: #1e293b;
                --card-border: #334155;
                --icon-bg: #1e3a8a;
                --icon-color: #60a5fa;
                --icon-border: #1e3a8a;
                --text-muted: #94a3b8;
                --error-bg: #450a0a;
                --error-text: #fca5a5;
                --error-border: #7f1d1d;
                --input-border: #475569;
                --input-bg: #0f172a;
                --input-focus-ring: rgba(96, 165, 250, 0.25);
                --btn-bg: #3b82f6;
                --btn-hover: #60a5fa;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            box-sizing: border-box;
            color: var(--text-color);
        }

        .card {
            background: var(--card-bg);
            max-width: 24rem;
            width: 100%;
            text-align: center;
            padding: 2.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid var(--card-border);
        }

        .icon-wrap {
            width: 3.5rem;
            height: 3.5rem;
            background: var(--icon-bg);
            color: var(--icon-color);
            border-radius: 50%;
            border: 1px solid var(--icon-border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-wrap svg {
            width: 1.75rem;
            height: 1.75rem;
            stroke-width: 2;
        }

        h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            letter-spacing: 0;
        }

        p.desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0 0 2rem;
            line-height: 1.6;
        }

        .error {
            background: var(--error-bg);
            color: var(--error-text);
            font-size: 0.875rem;
            padding: 0.875rem;
            border-radius: 0.5rem;
            border: 1px solid var(--error-border);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            text-align: left;
        }

        .error svg {
            width: 1.25rem;
            height: 1.25rem;
            stroke-width: 2;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            background: var(--input-bg);
            font-size: 1.125rem;
            text-align: center;
            letter-spacing: 0.15em;
            box-sizing: border-box;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
            outline: none;
            color: var(--text-color);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        input[type="password"]:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 4px var(--input-focus-ring);
        }

        input[type="password"]::placeholder {
            color: var(--text-muted);
            font-size: 0.875rem;
            letter-spacing: 0.1em;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        button {
            width: 100%;
            background: var(--btn-bg);
            color: #fff;
            font-weight: 600;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        button:hover {
            background: var(--btn-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        button:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h1><?= htmlspecialchars($title ?? 'Preview Authentication', ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="desc"><?= htmlspecialchars($desc ?? 'Please enter the password to view this preview.', ENT_QUOTES, 'UTF-8') ?></p>

        <?php if (!empty($error)): ?>
            <div class="error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="password" name="preview_pass" placeholder="<?= htmlspecialchars($ph ?? 'Password', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
            <button type="submit"><?= htmlspecialchars($btn ?? 'View', ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
</body>

</html>
