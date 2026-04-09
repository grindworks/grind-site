=========================================================================
 GrindSite - Developer CLI & Tool Directory
=========================================================================

[English]
This directory contains command-line tools for developers and server administrators.
These scripts are intended for use in a local development environment or via SSH on a server.

- grind.php:
  The powerful main CLI tool for GrindSite. It allows full management of the system
  without using the web interface.
  Key features:
  - Cache management (clear page caches and temporary files)
  - Maintenance mode (toggle site access for deployments)
  - System rescue (reset UI, disable Basic Auth, and clear login lockouts)
  - User management (list, create users, and reset passwords)
  - Database tools (optimization and search index rebuilding)
  - Backup & Recovery (create, list, and restore database snapshots)
  - Migration (generate full deployment packages)
  - Static Site Generation (build static HTML files via CLI)
  - Plugin & Theme management (list and toggle status)

  Usage: php bin/grind.php <command>
  For a full list of commands, run:
  $ php bin/grind.php help

  Examples:
  $ php bin/grind.php status               # Check system status
  $ php bin/grind.php cache:clear          # Clear all caches
  $ php bin/grind.php backup:create        # Create a database backup

  Important Note on Permissions:
  When running commands on a live server via SSH, generated files and caches will be owned by your SSH user.
  This may cause permission errors if the web server (e.g., www-data) tries to modify them later from the web interface.
  To prevent this, it is recommended to run the command as the web server user:
  $ sudo -u www-data php bin/grind.php ssg:build

  Using with Cron Jobs (Automation):
  When scheduling commands via cron, always use absolute paths for both the PHP executable and the script.
  Example: 0 2 * * * /usr/bin/php /var/www/html/bin/grind.php backup:create

  PHP CLI Version:
  This tool requires PHP 8.3.0 or higher. On some shared hosts, the default `php` command in the terminal
  may point to an older version than the web server. If you encounter version errors, try using a specific
  path like `/usr/bin/php8.3`.

-------------------------------------------------------------------------

[Japanese]
このディレクトリは、開発者およびサーバー管理者向けのコマンドラインツールを格納する場所です。
ローカル開発環境や、サーバーへSSH接続して使用することを想定しています。

- grind.php:
  GrindSiteの強力なメインCLIツールです。Web画面を開くことなく、サーバー運用に必要な
  ほとんどの操作を完結させることができます。
  主な機能:
  - キャッシュ管理（ページキャッシュや一時ファイルのクリア）
  - メンテナンスモード（デプロイ時のアクセス制限の切り替え）
  - システムレスキュー（UIリセット、Basic認証の無効化、ログインロックの解除）
  - ユーザー管理（一覧表示、新規作成、パスワードリセット）
  - データベースツール（最適化、検索インデックスの再構築）
  - バックアップと復元（スナップショットの作成、一覧、CLIからの復旧）
  - 移行（データベースとアップロードファイルを含めた一括パッケージ作成）
  - 静的サイト生成（CLIから静的HTMLファイルを一括ビルド）
  - プラグイン・テーマ管理（一覧表示、有効化・無効化の切り替え）

  使い方: php bin/grind.php <コマンド>
  利用可能なコマンドの一覧は、以下のコマンドで確認できます。
  $ php bin/grind.php help

  使用例:
  $ php bin/grind.php status               # システムのステータスを確認
  $ php bin/grind.php cache:clear          # キャッシュをクリア
  $ php bin/grind.php backup:create        # データベースのバックアップを作成

  パーミッションに関する重要な注意:
  SSH経由で本番サーバー上でコマンドを実行した場合、生成されたファイルの所有者はSSHユーザーになります。
  これにより、後からWeb画面経由でファイルやキャッシュを削除しようとした際に、権限エラーが発生する可能性があります。
  これを防ぐため、本番環境ではWebサーバーと同じユーザー権限でコマンドを実行することをおすすめします。
  （例： $ sudo -u www-data php bin/grind.php ssg:build）

  Cronジョブでの自動化について:
  cronを使用してバックアップなどを定期実行する場合は、相対パスが機能しないため、
  PHPコマンドとスクリプトの両方に必ず「絶対パス」を指定してください。
  例：0 2 * * * /usr/bin/php /var/www/html/bin/grind.php backup:create

  PHPのCLIバージョンについて:
  このツールはPHP 8.3.0以上が必要です。一部のレンタルサーバーでは、Web側のPHPバージョンと
  ターミナル（CLI）の `php` コマンドのバージョンが異なる場合があります。実行時にバージョンエラーが
  出る場合は、`/usr/bin/php8.3` のようにPHPのパスを明示的に指定して実行してください。
