=========================================================================
 GrindSite Plugins Directory
=========================================================================

[English]
This directory is for user-defined custom PHP code.
Any ".php" files placed here will be automatically loaded at system startup.

- Features:
  - Extend functionality without modifying core files.
  - Files in this directory are SAFE from GrindSite core updates.
  - Auto-Quarantine (Safe Mode): If a plugin causes a Fatal Error or crash, the system
    will automatically disable it by renaming it with an underscore "_"
    prefix to protect your site from going down.
    *(Note: This auto-quarantine feature requires the 'plugins' directory to have
    write permissions. If it is read-only, you must rename the file manually via FTP).*

- How to Use:
  1. Create a PHP file in this directory (e.g., "my-functions.php").
  2. Write your custom PHP code inside.

- How to Disable:
  To disable a plugin without deleting it, simply add an underscore "_"
  to the beginning of the filename (e.g., "_my-functions.php").
  Files starting with "_" are ignored by the system.

- Hook System (Action Hooks):
  GrindSite provides a lightweight hook system to execute code at specific moments.
  Available hooks include:
  - `grinds_init`: System startup (before output).
  - `grinds_head`: Inside the <head> tag.
  - `grinds_footer`: Before the closing </body> tag.
  - `grinds_admin_toolbar`: Inside the admin header toolbar (useful for settings buttons).
  - `grinds_post_login`: After a user successfully logs in.
  - `grinds_post_saved`: After a post is saved.
  - `grinds_post_trashed`: After a post is moved to the trash.
  - `grinds_post_restored`: After a post is restored from the trash.
  - `grinds_before_post_delete`: Right before a post is permanently deleted.
  - `grinds_post_deleted`: After a post is permanently deleted.
  - `grinds_trash_emptied`: After the trash is emptied.
  - `grinds_html_block_tools`: Inside the HTML block toolbar (Useful for shortcode buttons).

  Usage Example:
  add_action('grinds_head', function() {
      echo '<meta name="custom" content="hello">';
  });

- Filter System (Filter Hooks):
  Unlike Action Hooks, Filter Hooks allow you to modify data before it is rendered or saved.
  Available filters include:
  - `grinds_the_title`: Modifies the post title before rendering.
  - `grinds_the_content`: Modifies the post content before rendering.
  - `grinds_frontend_content`: Modifies the final HTML content before output on the frontend.

- Samples:
  Fifteen sample files are included (some are enabled by default):

  1. `_custom_helpers.php`
     Useful functions for theme development (e.g., debug `dd()`, reading time).
     Rename to "custom_helpers.php" to enable.

  2. `_sample_hooks.php`
     Demonstrates how to use Action Hooks to inject code or handle events.
     Rename to "sample_hooks.php" to enable.

  3. `_slack_notifier.php`
     Sends a notification to a specified Slack or Discord Webhook URL when a post is saved as "published".
     Rename to "slack_notifier.php" to enable.

  4. `_admin_ip_restrict.php`
     Restricts access to the admin area (/admin/) to specific IP addresses.
     Rename to "admin_ip_restrict.php" to enable.

  5. `_basic_auth.php`
     Adds Basic Authentication to the entire site or specific pages.
     Rename to "basic_auth.php" to enable.

  6. `_maintenance_mode.php`
     Displays a "Maintenance Mode" screen (503 status) to non-logged-in users.
     Rename to "maintenance_mode.php" to enable.

  7. `_sample_filters.php`
     Demonstrates how to use Filter Hooks (e.g., auto-linking keywords, custom shortcodes).
     Rename to "sample_filters.php" to enable.

  8. `_mail_otp_2fa.php`
     Two-Factor Authentication (2FA) via Email OTP (One-Time Password).
     Requires valid SMTP settings in GrindSite to function.
     Rename to "mail_otp_2fa.php" to enable.

  9. `_strict_session.php`
     Strict Session Management (Institutional Grade).
     Enforces IP and User-Agent binding to prevent hijacking and disallows concurrent logins.
     Rename to "strict_session.php" to enable.

  10. `_audit_logger.php`
     Audit Trail Logger (Institutional Grade).
     Records all critical administrative actions to `data/logs/audit.log`.
     Rename to "audit_logger.php" to enable.

  11. `_rate_limiter.php`
     Lightweight Rate Limiting Plugin.
     Protects API and Contact Forms from DoS attacks and spam by limiting
     requests per IP address using file-based storage.
     Rename to "rate_limiter.php" to enable.

  12. `amazon_affiliate.php`
     Amazon Affiliate Shortcode Plugin.
     Converts `[amazon id="ASIN" title="Product" region="com"]` shortcodes
     into beautiful Amazon affiliate product cards.
     Integrated directly into the admin toolbar.
     (Enabled by default)

  13. `_ebay_affiliate.php`
     eBay Affiliate Shortcode Plugin.
     Converts `[ebay url="URL" title="Product" image="IMG_URL"]` shortcodes
     into beautiful eBay affiliate product cards.
     (Disabled by default. Rename to "ebay_affiliate.php" to enable)

  14. `_rakuten_affiliate.php`
     Rakuten Affiliate Shortcode Plugin.
     Converts `[rakuten url="URL" title="Product" image="IMG_URL"]` shortcodes
     into beautiful Rakuten affiliate product cards.
     (Disabled by default. Rename to "rakuten_affiliate.php" to enable)

  15. `easter_egg.php`
     Displays an engineer-focused Easter egg (console log) in the admin footer.
     (Enabled by default. Rename to "_easter_egg.php" to disable.)

-------------------------------------------------------------------------

[Japanese]
このディレクトリは、ユーザー独自のカスタマイズコード（PHP）を置くための場所です。
ここにある ".php" ファイルは、システム起動時に自動的に読み込まれます。

- 特徴:
  - システムのコアファイルを変更せずに機能を拡張できます。
  - GrindSite本体をアップデートしても、このフォルダの中身は維持されます（上書きされません）。
  - 自動隔離（セーフモード）: 万が一自作プラグインが致命的なエラー（Fatal Error等）を
    引き起こしてしまった場合、システムが自動的にファイル名の先頭に "_" を付けて無効化し、
    サイト全体がエラーで閲覧不可になるのを防ぎます。
    ※注意: この自動隔離機能が働くには、`plugins` フォルダに書き込み権限が必要です。
    読み取り専用の場合は、FTP等を使って手動でファイル名を変更（または削除）してください。

- 使い方:
  1. このフォルダの中に好きな名前でPHPファイルを作成します。（例: my-functions.php）
  2. そのファイルの中にPHPコードを記述します。

- 無効化の方法:
  ファイル名の先頭に "_"（アンダースコア）を付けると、そのファイルは読み込まれません。
  一時的に機能を停止したい場合や、バックアップとして残す場合に便利です。
  (例: _my-functions.php)

- フックシステム (Action Hooks):
  特定のタイミングで処理を実行するための軽量なフック機構を備えています。
  利用可能なフック一覧:
  - `grinds_init`: システム起動直後（出力前）。
  - `grinds_head`: <head>タグ内。
  - `grinds_footer`: </body>タグの直前。
  - `grinds_admin_toolbar`: 管理画面ヘッダーのツールバー内（設定ボタンなどの追加に便利）。
  - `grinds_post_login`: ユーザーがログインに成功した直後。
  - `grinds_post_saved`: 記事保存時。
  - `grinds_post_trashed`: 記事をゴミ箱へ移動した時。
  - `grinds_post_restored`: 記事をゴミ箱から復元した時。
  - `grinds_before_post_delete`: 記事が完全に削除される直前。
  - `grinds_post_deleted`: 記事が完全に削除された時。
  - `grinds_trash_emptied`: ゴミ箱が空にされた時。
  - `grinds_html_block_tools`: HTMLブロックのツールバー内（ショートコード生成ボタンなどの追加に便利）。

  使用例:
  add_action('grinds_head', function() {
      echo '<meta name="custom" content="hello">';
  });

- フィルターシステム (Filter Hooks):
  アクションフックとは異なり、データが表示または保存される前にその内容を変更することができます。
  利用可能なフィルター一覧:
  - `grinds_the_title`: 記事のタイトルが表示される直前に内容を変更します。
  - `grinds_the_content`: 記事の本文が表示される直前に内容を変更します。
  - `grinds_frontend_content`: フロントエンドで最終的なHTMLとして出力される直前に内容を変更します。

- サンプル:
  15のサンプルファイルが同梱されています（一部はデフォルトで有効です）。

  1. `_custom_helpers.php`
     テーマ開発に便利な関数集（デバッグ用 `dd()` や読了時間表示など）。
     リネームして "custom_helpers.php"（アンダースコアを削除）にすると有効になります。

  2. `_sample_hooks.php`
     フックシステムを使ってコードを挿入したり、イベントを処理する方法の実演です。
     リネームして "sample_hooks.php" にすると有効になります。

  3. `_slack_notifier.php`
     記事が「公開」として保存された際に、指定したSlackやDiscordへ通知を送ります。
     リネームして "slack_notifier.php" にすると有効になります。

  4. `_admin_ip_restrict.php`
     管理画面（/admin/）へのアクセスを、特定のIPアドレスからのみに制限します。
     リネームして "admin_ip_restrict.php" にすると有効になります。

  5. `_basic_auth.php`
     サイト全体、または特定のページにBasic認証（簡易的なID/パスワード制限）を追加します。
     リネームして "basic_auth.php" にすると有効になります。

  6. `_maintenance_mode.php`
     ログインしていない一般ユーザーに対して「メンテナンス中」の画面（503ステータス）を表示します。
     リネームして "maintenance_mode.php" にすると有効になります。

  7. `_sample_filters.php`
     フィルターフックを利用して、表示直前の文字列を操作（キーワードの自動リンク化など）します。
     リネームして "sample_filters.php" にすると有効になります。

  8. `_mail_otp_2fa.php`
     メールによるワンタイムパスワード(OTP)を利用した2要素認証プラグインです。
     GrindSiteのSMTP設定が正しく完了している場合のみ動作します。
     リネームして "mail_otp_2fa.php" にすると有効になります。

  9. `_strict_session.php`
     厳格なセッション管理プラグイン（金融・公的機関レベル）。
     IPアドレスとブラウザ情報をセッションに紐づけ、ハイジャック防止や同時ログインの排除を行います。
     リネームして "strict_session.php" にすると有効になります。

  10. `_audit_logger.php`
     監査ログ記録プラグイン（金融・公的機関レベル）。
     すべての重要な管理者操作を `data/logs/audit.log` に記録します。
     リネームして "audit_logger.php" にすると有効になります。

  11. `_rate_limiter.php`
     APIやお問い合わせフォーム向けの軽量レートリミット（スロットリング）プラグインです。
     IPアドレスベースでリクエスト回数を制限し、簡易的なDoS攻撃やスパム送信を防御します。
     リネームして "rate_limiter.php" にすると有効になります。

  12. `amazon_affiliate.php`
     Amazonアフィリエイト用ショートコードプラグインです。
     `[amazon id="ASIN" title="商品名" region="co.jp"]` のショートコードを
     美しい商品カードに自動変換します。region指定で各国のAmazonに対応可能です。
     （デフォルトで有効です）

  13. `_ebay_affiliate.php`
     eBayアフィリエイト用ショートコードプラグインです。
     `[ebay url="商品URL" title="商品名" image="画像URL"]` のショートコードを
     美しい商品カードに自動変換します。
     （デフォルトでは無効です。有効にするには "ebay_affiliate.php" にリネームしてください）

  14. `_rakuten_affiliate.php`
     楽天アフィリエイト用ショートコードプラグインです。
     `[rakuten url="商品URL" title="商品名" image="画像URL"]` のショートコードを
     美しい商品カードに自動変換します。
     （デフォルトでは無効です。有効にするには "rakuten_affiliate.php" にリネームしてください）

  15. `easter_egg.php`
     管理画面のコンソールにエンジニア向けのイースターエッグ（システムメッセージ）を表示します。
     （デフォルトで有効です。無効化するには "_easter_egg.php" にリネームしてください）
