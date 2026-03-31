# GrindSite v1.2.2

**The simplest CMS on earth.**
**地球上で最もシンプルな CMS**

[![PHP Version](https://img.shields.io/badge/php-8.3%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-Commercial%2FFree-green.svg)](#-license--support-policy)
[![Version](https://img.shields.io/badge/version-1.2.2-blue.svg)](https://github.com/grindworks/grind-site/releases)

---

### 🌐 Language

- **[English](#english)**
- **[日本語 (Japanese)](#japanese)**

---

<a id="english"></a>

## 🇺🇸 English

GrindSite is a lightweight, high-performance flat-file CMS designed for absolute simplicity.
It requires **no database configuration (MySQL)**. Just upload the files to your server, and it works instantly. It features a modern admin panel built with Tailwind CSS + Alpine.js and an intuitive **Block Editor**.

### ✨ Key Features

- **Zero Configuration:** No database setup required. Runs on SQLite.
- **Block Editor:** Intuitive writing experience (Headings, Images, Cards, etc.).
- **Smart Paste (AI Import):** Copy-paste Markdown from ChatGPT/Claude directly into the editor.
- **AI & LLM Ready:** Built-in `llms.txt` and `llms-full.txt` (Markdown archive) generation for RAG and AI engines.
- **Multi-Theme Support:** Comes with **Tailwind CSS** (Default) and **Bootstrap 5** themes. Easy to customize or create your own.
- **Site Management:** Full control over Menus, Widgets, Banners, Categories, and Tags from the admin panel.
- **Headless API & Hooks:** Built-in REST API (`/api/`) and extensible Plugin/Hook system (`/plugins/`).
- **System Tools:** Built-in link checker, unused file detector, database optimizer, and migration exporter.
- **High Performance:** Lightweight core ensures blazing fast page loads.
- **Portable:** Easy migration between local (MAMP/XAMPP) and production servers.
- **Static Site Generator:** Export your site as pure HTML for ultra-fast hosting (Netlify, Vercel, etc.) or archiving.
- **Secure:** Admin panel protected by robust authentication and CSRF checks.

### 📦 Installation

1. Download the latest source code (ZIP) from the **[Releases](https://github.com/grindworks/grind-site/releases)** page or "Code > Download ZIP".
2. **Important:** Upload **only the contents of the `src` folder** to your web server's public directory.
   _(Do not upload the `tools` folder or `README.md` to your public server.)_
3. Access the site URL in your browser.
4. The installer will launch automatically. Follow the instructions to set up your admin account.

### ⚠️ License & Support Policy

GrindSite is available for free, but a **Commercial License** is required for professional use on public production servers.

### 💎 Commercial Licenses

| Feature              |   Free (Trial/Dev)   |  Early Bird ($99)   |     Pro ($149)      |   Agency ($399/yr)    |
| :------------------- | :------------------: | :-----------------: | :-----------------: | :-------------------: |
| **Usage**            |  Local / Evaluation  | Commercial / Public | Commercial / Public |  Commercial / Public  |
| **Domains**          |       1 Domain       |      1 Domain       |      1 Domain       | **Unlimited Domains** |
| **Admin Banner**     | "TRIAL MODE" Warning | **Hidden (Clean)**  | **Hidden (Clean)**  |  **Hidden (Clean)**   |
| **One-Click Update** |     ✅ Available     |    ✅ Available     |    ✅ Available     |     ✅ Available      |

🎉 **Special Launch Pricing!** (Limited to the first 1,000 users or 1st month)

- **[Early Bird License (Single Site)](https://buy.polar.sh/polar_cl_sdjC1GiTA68jw15Kn7hzVdaKqs8tbNqrgqUaC2elmqm)** - **$99** (No support included)
  **[Pro License (Single Site)](https://buy.polar.sh/polar_cl_7XKIWnwDDZUWZARYbaebRl4YoChYCygItP0GI1p6bGt)** - **$149** (No support included)
  **[Agency License (Unlimited)](https://buy.polar.sh/polar_cl_SI25C5psZM0pRO9NY700741wtL7XijaA0Whwd3P0n10)** - **$399/year** (For freelancers & web agencies, no support included)

#### 🚫 Support Policy (Please Read Before Opening an Issue)

> **⚠️ NO TECHNICAL SUPPORT is provided.**
> However, **Bug Reports are welcome!** We appreciate your help in making GrindSite stable.

- **🐛 Bug Reports:** Please use [GitHub Issues](https://github.com/grindworks/grind-site/issues).
- **💬 Questions & Community:** Please use [GitHub Discussions](https://github.com/grindworks/grind-site/discussions).

**Note:** Issues regarding feature requests, "how-to" questions, or server configuration help will be closed. Please use the Discussions forum for those topics.

**Before opening an Issue, you MUST confirm ALL of the following:**

1. You are using the **latest version** of GrindSite.
2. The issue is a **bug in GrindSite itself**, not a server configuration or hosting problem.
3. You can provide **exact steps to reproduce** the issue.
4. You have searched **existing Issues** for duplicates.

_This project is maintained by an independent developer. These boundaries exist to keep the project alive and the code quality high. Thank you for your understanding._

---

### 🔄 Workflow (Local ⇔ Production)

GrindSite is designed for easy synchronization.

1.  **Develop Locally:** Create content and customize themes on your local machine.
2.  **Deploy:** Upload the modified files and `data/data.db` to your production server via FTP.
3.  **Update:** Download the latest version from GitHub and overwrite the core files manually, or use the "One-Click Update" feature from the admin panel.

### 🔌 Completely Offline / Intranet Usage

For strict privacy compliance (GDPR) or closed intranet environments, GrindSite is designed to be fully functional offline.
The core Admin UI (Alpine.js, Flatpickr, and Chart.js) is pre-bundled and prioritized locally. Even if the "Disable External Assets" setting is not checked, the system will automatically use local assets if they are found in the vendor directory.

If you want heavier specialized features like **Math blocks** or **Code Highlighting** to work completely offline, you need to manually download and place the following files into the `src/assets/js/vendor/` and `src/assets/css/vendor/` directories:

- `katex.min.js`, `katex.min.css`, `auto-render.min.js` (KaTeX)
- `prism.min.js`, `prism-tomorrow.min.css`, `prism-autoloader.min.js` (Prism.js)

_(If these files are missing, the system will safely fallback to using CDN without any errors.)_

### 🚚 Server Migration & Directory Move

GrindSite works simply by moving files, but please follow these steps when migrating:

1. **Backup First (Crucial):** Before moving, always download a database backup from the "Settings > Backup" menu in the admin panel.
2. **Log In to Admin Panel:** After moving, access the admin login page (`/admin/login.php`) at the new URL. The system will detect the URL change and automatically clear old page caches.
3. **Check Dashboard Alerts:** If you see warnings like "URL Rewrite Error" on the dashboard, follow the instructions to run the auto-fix tool in the "Migration Checklist".
4. **For SSG Users:** If you use the Static Site Generator (SSG) for external hosting, you must run a "Full Export (Rebuild)" instead of a "Diff Export" to apply the new URL across all pages.
5. **Check Permissions:** Ensure that the `data/` and `assets/uploads/` directories retain their write permissions (e.g., 775 or 755) on the new server.

### 💻 CLI Tool (For Developers)

GrindSite includes a built-in command-line tool for local development or SSH environments.

```bash
# Show system status
php bin/grind.php status

# Clear page caches
php bin/grind.php cache:clear

# Reset admin password (Useful if locked out)
php bin/grind.php user:reset-password admin NewPassword123
```

### 🆘 Troubleshooting

**Q. Why do my `<iframe>` or `<script>` tags disappear when I save a post?**

**A.** For security (XSS prevention), GrindSite automatically removes `<iframe>` and `<script>` tags from untrusted domains. Services like YouTube, X, and Google Maps are allowed by default. To allow other external services, please add their domains to the **"Allowed iframe domains"** list in the admin Settings.

**Q. I don't use the API. Is it safe to leave it? How can I disable it?**

**A.** The default APIs are read-only and only expose public data, so they are safe. However, it's a security best practice to disable any unused features. You have two options:

1.  **Delete the `api` folder:** This is the simplest and most secure method if you are sure you won't use the API.
2.  **Block access via `.htaccess`:** If you want to keep the files but block access, open `src/api/.htaccess` and uncomment the `Require all denied` block.

**Q. Getting "500 Internal Server Error" or cannot use rescue tools?**

**A.** Some shared hosting servers do not allow the `Options` directive in `.htaccess`.
Try commenting out the following line in `.htaccess`:

```apache
# Options -MultiViews
```

**Q. Cannot access admin panel after moving folder (404/500 Error)?**

**A.** The `RewriteBase` in `.htaccess` might be mismatching.
Open `src/.htaccess` and uncomment the `RewriteBase` line.
_(During installation, the correct path is automatically detected and commented out for you.)_

```apache
# RewriteBase /grind-site/src/
# (e.g. if placed in /subdir/ -> RewriteBase /subdir/)
```

**Q. Common "Landmines" when migrating or operating on shared hosting?**

**A.** Here are 4 high-priority points to keep in mind:

1.  **`.htaccess` Options Prohibition (Common 500 Error):**
    - **Symptom:** Immediate "500 Internal Server Error" after upload.
    - **Cause:** Some shared hosting providers may prohibit the `Options` directive.
    - **Fix:** Comment out `Options -MultiViews` in `src/.htaccess`.
2.  **SQLite WAL Mode & Shared Drives (NFS):**
    - **Symptom:** "database is locked" error when saving articles.
    - **Cause:** WAL mode may fail on network shared drives (NFS) used by some shared hosts.
    - **Fix:** Set `ENABLE_WAL_MODE` to `false` in `config.php`.
3.  **WAF (Web Application Firewall) 403 Forbidden:**
    - **Symptom:** "403 Forbidden" when clicking "Save" in the admin panel.
    - **Cause:** WAF may misidentify tags like `<script>` or `<iframe>` as XSS attacks.
    - **Fix:** Temporarily disable WAF or exclude relevant signatures in your hosting control panel.
4.  **Memory Limits & Missing PHP Modules:**
    - **Symptom:** White screen when uploading large images or running SSG/Backup.
    - **Cause:** Low `memory_limit` (e.g., 128M) or missing extensions like `zip` or `dom`.
    - **Fix:** Increase `memory_limit` to 256M/512M and enable `zip`/`dom` modules in PHP settings.

### 🌐 Nginx Configuration

If you are using Nginx, please add the following rules to your server block to protect sensitive files and enable routing.
A sample configuration file is included in the `src` directory as `nginx.conf.sample`.

> [!IMPORTANT]
> You **must** adjust the `fastcgi_pass` path (e.g., `unix:/var/run/php/php8.3-fpm.sock`) to match your server's actual PHP-FPM configuration. Using the wrong path will result in a "502 Bad Gateway" error.

<details>
<summary><strong>Click to view Nginx Configuration Sample</strong></summary>

```nginx
server {
    index index.php index.html;
    client_max_body_size 50M;

    # Security Headers
    # Handled by PHP to avoid duplication
    # add_header X-Frame-Options "SAMEORIGIN" always;
    # add_header X-Content-Type-Options "nosniff" always;
    # add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Dynamic robots.txt/sitemap.xml/llms.txt
    location = /robots.txt {
        try_files $uri /robots.php;
        access_log off;
        log_not_found off;
    }
    location = /sitemap.xml {
        try_files $uri /sitemap.php;
        access_log off;
        log_not_found off;
    }
    location = /llms.txt {
        try_files $uri /llms.php;
        access_log off;
        log_not_found off;
    }
    location = /llms-full.txt {
        try_files $uri /llms-full.php;
        access_log off;
        log_not_found off;
    }

    # Routing
    location / {
        # NOTE: If installing in a subdirectory (e.g. /blog/), change the last parameter to /blog/index.php
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # Block Sensitive Files
    # [IMPORTANT] This rule must be placed BEFORE the "location ~ \.php$" block.
    location ~ (?:^|/)(\.git|\.env|\.vscode|composer\.json|package\.json|README\.md|LICENSE(\.txt)?|config\.php|nginx\.conf\.sample|\.htpasswd|\.nginx_confirmed) {
        deny all; return 404;
    }

    # [IMPORTANT] Block Database & Log Files
    location ~ \.(db|db-wal|db-shm|db-journal|sql|log|ini|bak|old|temp|swp)$ {
        deny all; return 404;
    }

    # Block System Directories
    location ^~ /data/ {
        deny all; return 404;
    }
    location ~ (?:^|/)(lib|admin/config|admin/skins|admin/views|theme/.+/parts)/ {
        deny all; return 404;
    }

    # Block direct access to theme PHP files
    location ~ (?:^|/)theme/.*\.php$ {
        deny all; return 404;
    }

    # Block direct access to PHP files in Plugins
    location ~ (?:^|/)plugins/.*\.php$ {
        deny all; return 404;
    }

    # [SECURITY] Prevent access to rescue tools (Safety net)
    location ~ (?:^|/)tools/.*\.php$ {
        deny all; return 404;
    }
    location ~ (?:^|/)tool_.*\.php$ {
        deny all; return 404;
    }

    # [SECURITY] Prevent PHP execution and XSS in uploads directory
    location ~ (?:^|/)assets/uploads/.*\.php$ {
        deny all; return 404;
    }
    location ~ (?:^|/)assets/uploads/.*\.(svg|html)$ {
        add_header Content-Security-Policy "script-src 'none'";
    }

    # PHP Handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;

        # ↓↓↓ Adjust to your environment / 環境に合わせて変更してください ↓↓↓
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        # ↑↑↑ Adjust to your environment / 環境に合わせて変更してください ↑↑↑

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

</details>

### 📂 Directory Structure

The structure of the GitHub repository is as follows. Please upload **only the contents of the `src` folder** to your server.

```text
grind-site/
├── src/               # [IMPORTANT] Upload ONLY this folder's contents to your server
│   ├── admin/         # Admin panel
│   ├── api/           # REST API endpoints
│   ├── assets/        # Static files (CSS/JS/Uploads)
│   ├── data/          # Database, logs, and cache
│   ├── lib/           # Core libraries
│   ├── plugins/       # Plugin and hook management
│   ├── theme/         # Frontend themes
│   ├── config.php     # Configuration file (auto-generated)
│   ├── index.php      # Front controller
│   ├── install.php    # Installer
│   ├── llms.php       # AI overview info (llms.txt)
│   ├── llms-full.php  # AI full markdown output (llms-full.txt)
│   ├── robots.php     # Dynamic robots.txt
│   ├── rss.php        # Dynamic RSS (feed.xml)
│   ├── sitemap.php    # Dynamic sitemap.xml
│   ├── preview_auth.php # Preview authentication file
│   ├── nginx.conf.sample # Sample Nginx configuration
│   └── LICENSE.txt    # License terms
│
├── tools/             # Emergency tools (DO NOT upload to server)
├── update.json        # Update info (for GitHub releases)
└── README.md          # This file (Top level)
```

<a id="japanese"></a>

## 🇯🇵 日本語

GrindSite は、データベース設定が一切不要な、超軽量・高機能フラットファイル CMS です。
サーバーにファイルをアップロードするだけで即座に動作します。モダンな管理画面と、直感的な **ブロックエディタ** により、WordPress のような快適な執筆環境を提供します。

### ✨ 主な特徴

- **設定不要:** MySQL などの DB 設定は一切不要。SQLite で動作します。
- **ブロックエディタ:** 見出し、画像、吹き出し、カードリンクなどを直感的に配置。
- **スマートペースト (AI取込):** ChatGPT や Claude の Markdown をそのままコピペしてブロックに変換できます。
- **AI・LLM 完全対応:** RAGやAIクローラー向けに、サイト全体をMarkdownで提供する `llms.txt` および `llms-full.txt` を動的生成。
- **マルチテーマ対応:** **Tailwind CSS**（デフォルト）と **Bootstrap 5** テーマを同梱。カスタマイズも容易です。
- **サイト管理:** メニュー、ウィジェット、バナー、カテゴリ、タグなどを管理画面から自由に設定可能。
- **API ＆ プラグイン:** ヘッドレスCMSとして使えるREST API (`/api/`) と、管理画面や機能を安全に拡張できるフック・プラグインシステムを搭載。
- **システムツール:** リンク切れチェック、不要ファイル検出、DB最適化、移行用パッケージ作成機能を搭載。
- **高速動作:** 余計な機能がないため、非常に高速にページが表示されます。
- **高い移植性:** ローカル（MAMP 等）で作って本番にアップロードするだけの簡単運用。
- **静的サイト書き出し:** サイト全体を純粋な HTML として書き出し可能。PHP が動かないサーバーや CDN での運用にも対応。

### 📦 インストール方法

1. GitHub の **[Releases](https://github.com/grindworks/grind-site/releases)** または「Code > Download ZIP」から最新版をダウンロードします。
2. **【重要】** `src` フォルダの中身**だけ**を、サーバーの公開ディレクトリにアップロードしてください。
   _（`tools` フォルダやこの `README.md` はサーバーに上げないでください）_
3. ブラウザでその URL にアクセスします。
4. インストーラーが起動するので、画面の指示に従って管理者アカウントを作成してください。

### ⚠️ ライセンスとサポートについて

本ソフトウェアは無償で利用可能ですが、**公開サーバーでの本格運用には「商用ライセンス」の購入を推奨します。**

### 💎 商用ライセンスの種類

| 機能                 |      Free (無料版)       | アーリーバード ($99)  |      Pro ($149)       | エージェンシー ($399/年) |
| :------------------- | :----------------------: | :-------------------: | :-------------------: | :----------------------: |
| **利用用途**         | ローカル / テスト / 個人 |  商用 / 公開サーバー  |  商用 / 公開サーバー  |   商用 / 公開サーバー    |
| **利用ドメイン数**   |        1ドメイン         |       1ドメイン       |       1ドメイン       |        **無制限**        |
| **管理画面バナー**   |  警告バナーあり (TRIAL)  | **非表示 (クリーン)** | **非表示 (クリーン)** |  **非表示 (クリーン)**   |
| **ワンクリック更新** |       ✅ 利用可能        |      ✅ 利用可能      |      ✅ 利用可能      |       ✅ 利用可能        |

🎉 **リリース記念キャンペーン！** (※最初の1ヶ月間、または先着1,000名様限定)

- **[アーリーバード版 (シングルサイト)](https://buy.polar.sh/polar_cl_sdjC1GiTA68jw15Kn7hzVdaKqs8tbNqrgqUaC2elmqm)** - **$99** (サポートなし)
  **[Pro版 (シングルサイト)](https://buy.polar.sh/polar_cl_7XKIWnwDDZUWZARYbaebRl4YoChYCygItP0GI1p6bGt)** - **$149** (サポートなし)
  **[エージェンシー版 (無制限ドメイン)](https://buy.polar.sh/polar_cl_SI25C5psZM0pRO9NY700741wtL7XijaA0Whwd3P0n10)** - **$399/年** (無制限ドメイン、管理画面のホワイトラベル化、公式パートナー掲載、サポートなし)

#### 🚫 サポートポリシー（Issue を立てる前に必ずお読みください）

> **⚠️ 技術サポートは提供しておりません。**
> ただし、**バグ報告は歓迎します！** GrindSiteの品質向上のため、ご協力をお願いいたします。

- **🐛 バグ報告:** [GitHub Issues](https://github.com/grindworks/grind-site/issues) をご利用ください。
- **💬 質問・雑談:** [GitHub Discussions](https://github.com/grindworks/grind-site/discussions)（フォーラム）をご利用ください。

**注意:** 「使い方がわからない」「サーバー設定を教えてほしい」といった内容は Issue では受け付けておりません。Discussions をご活用ください。

**Issue を立てる前に、以下の全てを確認してください：**

1. GrindSite の**最新版**を使用していること。
2. サーバー設定やホスティングの問題ではなく、**GrindSite 本体のバグ**であること。
3. **正確な再現手順**を記載できること。
4. **既存の Issue** に同じ報告がないこと。

_本プロジェクトは個人開発者によって維持されています。サポート範囲を厳格に限定することで、コードの品質と開発の継続性を確保しています。ご理解ください。_

---

### 🔄 運用フロー（推奨）

1.  **ローカルで開発:** MAMP や XAMPP などのローカル環境で記事作成やデザイン調整を行います。
2.  **アップロード:** 変更したファイルと `data/data.db` を FTP で本番サーバーへ上書きします。
3.  **アップデート:** 最新版の zip をダウンロードし手動でファイルを上書きするか、管理画面から「ワンクリック更新」を使用することでアップデート可能です。

### 🔌 完全なオフライン（イントラネット）環境での利用

GrindSiteは、プライバシー保護（GDPR対応）や外部接続が制限された環境（イントラネット）でも追加設定なしで動作するように設計されています。
管理画面の基本動作に必要なアセット（Alpine.js, Flatpickr, Chart.js）は標準で同梱され、ローカルファイルが優先的に読み込まれるようになっています。

さらに、**数式ブロック**や**コードハイライト**などの特殊な機能も完全にオフラインで動作させたい場合は、以下のファイルを公式サイトからダウンロードし、`src/assets/js/vendor/` および `src/assets/css/vendor/` ディレクトリに手動で配置してください。

- `katex.min.js`, `katex.min.css`, `auto-render.min.js` (KaTeX)
- `prism.min.js`, `prism-tomorrow.min.css`, `prism-autoloader.min.js` (Prism.js)

※ ファイルが配置されていない場合は、自動的にCDNへフォールバックして読み込まれます（エラーで停止することはありません）。

### 🚚 サーバー移転・ディレクトリ移動時の手順

GrindSite はファイルを移動するだけで動作しますが、移転時は以下の手順を行ってください。

1. **事前にバックアップを取得する（重要）:** 移転作業前に、必ず管理画面の「設定 > バックアップ」からデータベースのバックアップ（.dbファイル）をダウンロードしておいてください。
2. **管理画面へ一度ログインする:** 移転後は、新しいURLの管理画面（`/admin/login.php`）へ必ず一度アクセスしてください。URLの変更を検知し、古いページキャッシュを自動で消去します。
3. **ダッシュボードのアラートを確認する:** 管理画面のダッシュボードに「URLリライトエラー」などの警告が出た場合は、指示に従って「Migration Checklist」から `.htaccess` の自動修正を実行してください。
4. **SSG機能を使用している場合:** 外部ホスティング用に静的書き出し（SSG）を利用している場合、URL変更を全ページに反映させるため、「差分エクスポート」ではなく必ず「フルエクスポート（再構築）」を行ってください。
5. **パーミッションの確認:** FTP等でファイルを丸ごと移動させた場合、`data/` ディレクトリと `assets/uploads/` ディレクトリの書き込み権限（775や755など）が移動先でも保持されているか確認してください。

### 💻 CLI ツール (開発者向け)

GrindSiteには、ローカル開発やSSH環境で利用できるコマンドラインツールが組み込まれています。

```bash
# システムステータスの表示
php bin/grind.php status

# ページキャッシュのクリア
php bin/grind.php cache:clear

# 管理者パスワードのリセット (ロックアウト時に便利です)
php bin/grind.php user:reset-password admin NewPassword123
```

### 🆘 トラブルシューティング

**Q. カスタムHTMLブロック等に入力した `<iframe>` が、保存すると消えてしまいます**

**A.** セキュリティ保護（XSS対策）のため、GrindSiteはシステムが許可していないドメインの `<iframe>` 等を自動的に削除する仕様になっています。YouTube、X（Twitter）、Googleマップなどはデフォルトで許可されています。特定の外部サービスを埋め込みたい場合は、管理画面の「設定」内にある **「許可するiframeドメイン」** にそのドメインを追加してください。

**Q. API機能は使いませんが、そのままでも安全ですか？ 無効化する方法は？**

**A.** デフォルトのAPIは読み取り専用で、公開済みのデータしか返さないため安全です。しかし、使わない機能を無効化するのはセキュリティのベストプラクティスです。以下のいずれかの方法で無効化できます。

1.  **`api` フォルダごと削除する:** 最もシンプルで確実な方法です。
2.  **`.htaccess` でアクセスを遮断する:** ファイルを残したままアクセスを遮断するには、`src/api/.htaccess` を開き、`Require all denied` と書かれたブロックのコメントアウトを外してください。

**Q. 「500 Internal Server Error」が出る / 緊急ツールが使えない**

**A.** 一部のレンタルサーバー（Lolipop, Sakura など）では、`.htaccess` 内の `Options` 記述が禁止されている場合があります。
その場合、`.htaccess` ファイル内の以下の行を `#` でコメントアウトしてみてください。

```apache
# Options -MultiViews
```

**Q. フォルダ移動後に管理画面に入れない (404/500エラー)**

**A.** `.htaccess` の `RewriteBase` 設定が合っていない可能性があります。
`src/.htaccess` を開き、以下の行のコメントアウトを外し、現在のパスに合わせて書き換えてください。

```apache
# RewriteBase /grind-site/src/
# (例: /subdir/ に置く場合 → RewriteBase /subdir/)
```

**Q. サーバー移転や運用時に注意すべき「地雷ポイント」はありますか？**

**A.** プロの視点で、特にはまりやすい4つのポイントをリストアップしました。

1.  **`.htaccess` の `Options` 禁止 (500エラーの定番):**
    - **症状:** アップロード直後に「500 Internal Server Error」になる。
    - **原因:** さくらインターネットやロリポップ等では `.htaccess` での `Options` 設定が禁止されている場合があります。
    - **対策:** `src/.htaccess` 内の `Options -MultiViews` を `#` でコメントアウトしてください。
2.  **SQLiteの「WALモード」と共有ドライブの相性:**
    - **症状:** 閲覧はできるが、保存時に「database is locked」エラーになる。
    - **原因:** ネットワーク共有ドライブ (NFS) ではSQLiteのWALモードのロック機構が正常に働かないことがあります。
    - **対策:** `config.php` の `ENABLE_WAL_MODE` を `false` に設定してください。
3.  **WAF (ウェブアプリケーションファイアウォール) による403エラー:**
    - **症状:** 記事保存時に「403 Forbidden」エラーになる。
    - **原因:** エックスサーバーやロリポップ等のWAFが、HTMLタグを攻撃と誤検知して遮断しています。
    - **対策:** サーバーの管理画面からWAFを一時的に無効にするか、該当ルールを除外設定してください。
4.  **PHPのメモリ制限と拡張モジュール不足:**
    - **症状:** 画像アップロードやバックアップ実行時に画面が白くなる。
    - **原因:** `memory_limit` が低い（128M等）、または `zip` / `dom` モジュールが無効になっている。
    - **対策:** `memory_limit` を 256M 以上に引き上げ、必要なPHP拡張を有効化してください。

### 🌐 Nginx の設定

Nginx をご利用の場合は、以下の設定を server ブロックに追加して、セキュリティを確保してください。特に `assets/uploads` 以下での PHP 実行禁止は重要です。
設定例として、`src` ディレクトリ内に `nginx.conf.sample` を同梱しています。

> [!IMPORTANT]
> **重要:** `fastcgi_pass` のパス（例: `unix:/var/run/php/php8.3-fpm.sock`）は、ご利用のサーバー環境に合わせて必ず変更してください。設定が異なると「502 Bad Gateway」エラーが発生します。

```nginx
server {
    index index.php index.html;
    client_max_body_size 50M;

    # セキュリティヘッダー
    # PHP側で出力するためコメントアウト (重複回避)
    # add_header X-Frame-Options "SAMEORIGIN" always;
    # add_header X-Content-Type-Options "nosniff" always;
    # add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Dynamic robots.txt/sitemap.xml/llms.txt
    location = /robots.txt {
        try_files $uri /robots.php;
        access_log off;
        log_not_found off;
    }
    location = /sitemap.xml {
        try_files $uri /sitemap.php;
        access_log off;
        log_not_found off;
    }
    location = /llms.txt {
        try_files $uri /llms.php;
        access_log off;
        log_not_found off;
    }
    location = /llms-full.txt {
        try_files $uri /llms-full.php;
        access_log off;
        log_not_found off;
    }

    # Routing
    location / {
        # サブディレクトリにインストールする場合（例: /blog/ ）は、最後のパラメータを /blog/index.php に変更してください
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # Block Sensitive Files
    # [IMPORTANT] このルールは "location ~ \.php$" ブロックより前に配置する必要があります
    location ~ (?:^|/)(\.git|\.env|\.vscode|composer\.json|package\.json|README\.md|LICENSE(\.txt)?|config\.php|nginx\.conf\.sample|\.htpasswd|\.nginx_confirmed) {
        deny all; return 404;
    }

    # [IMPORTANT] Block Database & Log Files
    location ~ \.(db|db-wal|db-shm|db-journal|sql|log|ini|bak|old|temp|swp)$ {
        deny all; return 404;
    }

    # Block System Directories
    location ^~ /data/ {
        deny all; return 404;
    }
    location ~ (?:^|/)(lib|admin/config|admin/skins|admin/views|theme/.+/parts)/ {
        deny all; return 404;
    }

    # Block direct access to theme PHP files
    location ~ (?:^|/)theme/.*\.php$ {
        deny all; return 404;
    }

    # Block direct access to PHP files in Plugins
    location ~ (?:^|/)plugins/.*\.php$ {
        deny all; return 404;
    }

    # [SECURITY] Prevent access to rescue tools (Safety net)
    location ~ (?:^|/)tools/.*\.php$ {
        deny all; return 404;
    }
    location ~ (?:^|/)tool_.*\.php$ {
        deny all; return 404;
    }

    # [SECURITY] Prevent PHP execution and XSS in uploads directory
    location ~ (?:^|/)assets/uploads/.*\.php$ {
        deny all; return 404;
    }
    location ~ (?:^|/)assets/uploads/.*\.(svg|html)$ {
        add_header Content-Security-Policy "script-src 'none'";
    }

    # PHP Handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;

        # ↓↓↓ Adjust to your environment / 環境に合わせて変更してください ↓↓↓
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        # ↑↑↑ Adjust to your environment / 環境に合わせて変更してください ↑↑↑

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 📂 ディレクトリ構成

GitHub リポジトリの構成は以下のようになっています。サーバーには `src` の中身のみをアップロードしてください。

```text
grind-site/
├── src/               # 【重要】この中身をサーバーにアップロードします
│   ├── admin/         # 管理画面
│   ├── api/           # REST API エンドポイント
│   ├── assets/        # 静的ファイル (CSS/JS/Uploads)
│   ├── data/          # データベース・ログ・キャッシュ用
│   ├── lib/           # コアライブラリ
│   ├── plugins/       # プラグイン・フック管理
│   ├── theme/         # フロントエンドテーマ
│   ├── config.php     # 設定ファイル (自動生成)
│   ├── index.php      # フロントコントローラー
│   ├── install.php    # インストーラー
│   ├── llms.php       # AI向け概要情報 (llms.txt)
│   ├── llms-full.php  # AI向け全記事Markdown出力 (llms-full.txt)
│   ├── robots.php     # 動的 robots.txt
│   ├── rss.php        # 動的 RSS (feed.xml)
│   ├── sitemap.php    # 動的 sitemap.xml
│   ├── preview_auth.php # プレビュー用認証ファイル
│   ├── nginx.conf.sample # Nginx用設定サンプル
│   └── LICENSE.txt    # 利用規約
│
├── tools/             # 緊急用ツール (サーバーには上げないでください)
├── update.json        # アップデート情報 (GitHub配信用)
└── README.md          # このファイル (トップレベル)
```

## © Copyright

Developed by **Koji Udagawa (Grind Works Inc.)**
