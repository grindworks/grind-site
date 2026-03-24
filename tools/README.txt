=========================================================================
 GrindSite - RESCUE TOOL KIT
=========================================================================

[WARNING]
These files are "Emergency Tools" for when you cannot log in to the admin panel.
DO NOT upload them to the server under normal circumstances.

-------------------------------------------------------------------------
 [1] Which tool should I use?
-------------------------------------------------------------------------

CASE 1: Forgot password / Reset email not configured
👉 Use "tool_reset_password.php"
   Forces a password overwrite for an existing account.

CASE 2: Locked out (Too many attempts) / White screen after settings change
👉 Use "tool_fix_settings.php"
   Unlocks login attempts and resets admin layout/debug settings to default.

CASE 3: All accounts deleted / Site taken over
👉 Use "tool_create_admin.php"
   Forces the creation of a new "Super Admin" account.

-------------------------------------------------------------------------
 [2] How to use
-------------------------------------------------------------------------

1. Select the tool file you need (e.g., tool_reset_password.php).

2. Upload the file to your server's "Root Directory" (where index.php is located) via FTP/SFTP.

3. [IMPORTANT] Unlock Security Restrictions
   Open the `.htaccess` file in the root directory, find the "# Block rescue tools & installer" section, and comment out (add #) the `Require all denied` or `Deny from all` lines to allow access to the tool.
   (Note: If you are using Nginx with the official `nginx.conf.sample`, you need to comment out the `location ~ (?:^|/)tool_.*\.php$` block and reload Nginx.)

   [Before]
   <FilesMatch "^(tool_.*|install)\.php$">
       Require all denied
   </FilesMatch>

   [After]
   <FilesMatch "^(tool_.*|install)\.php$">
       # Require all denied
   </FilesMatch>

4. Access the file via your browser.
   Example: https://example.com/tool_reset_password.php

5. Follow the on-screen instructions.

6. [Cleanup]
   The tool will try to delete itself automatically. If it remains, delete it manually.
   Finally, revert the changes in `.htaccess` (remove the #) to restore security.

   (Note: The `check/` directory in this folder contains internal developer/testing tools and is not meant for regular users.)

=========================================================================
 【日本語】 GrindSite レスキューツールキット
=========================================================================

[注意]
このフォルダに含まれるファイルは、管理画面にログインできなくなった場合の「緊急用ツール」です。
通常時はサーバーにアップロードしないでください。

-------------------------------------------------------------------------
 [1] 症状別・ツールの選び方
-------------------------------------------------------------------------

[ケース1] パスワードを忘れた / メール設定をしておらずリセットメールが届かない
👉 「tool_reset_password.php」 を使用してください。
   既存のアカウントのパスワードを強制的に上書きします。

[ケース2] ログインを何度も失敗してロックされた / 設定変更後に画面が真っ白になった
👉 「tool_fix_settings.php」 を使用してください。
   ログインロックの解除や、管理画面のレイアウト・デバッグ設定を初期化します。

[ケース3] 全員のアカウントを削除してしまった / 乗っ取られてログインできない
👉 「tool_create_admin.php」 を使用してください。
   新しい「スーパー管理者」アカウントを強制的に作成します。

-------------------------------------------------------------------------
 [2] 使用手順
-------------------------------------------------------------------------

1. 必要なツールファイル（例: tool_reset_password.php）を1つ選びます。

2. FTPソフト等で、サーバーの「ルートディレクトリ（index.phpがある場所）」にアップロードします。

3. 【重要】セキュリティ制限の解除
   ルートディレクトリにある `.htaccess` ファイルを開き、「# Block rescue tools & installer」セクションにある `Require all denied` または `Deny from all` の行の先頭に「#」をつけてコメントアウト（無効化）してください。
   これにより、ツールへのアクセスが可能になります。
   （※ Nginx環境で公式の nginx.conf.sample を使用している場合は、`location ~ (?:^|/)tool_.*\.php$` のブロックをコメントアウトしてNginxを再起動する必要があります）

   【変更前】
   <FilesMatch "^(tool_.*|install)\.php$">
       Require all denied
   </FilesMatch>

   【変更後】（# をつけて無効化する）
   <FilesMatch "^(tool_.*|install)\.php$">
       # Require all denied
   </FilesMatch>

4. ブラウザでそのファイルにアクセスします。
   例: https://example.com/tool_reset_password.php

5. 画面の指示に従って操作を行ってください。

6. 【完了後】
   ツールは自動的に削除されますが、もし残っている場合は必ず手動で削除してください。
   また、手順3で変更した `.htaccess` の「#」を外し、元の状態（セキュリティが有効な状態）に戻してください。

   （※ このフォルダ内にある `check/` ディレクトリは開発者向けの内部テストツール群であり、一般ユーザーが使用するものではありません）
