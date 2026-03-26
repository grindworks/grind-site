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
  - `grinds_post_saved`: After a post is saved.
  - `grinds_post_trashed`: After a post is moved to the trash.
  - `grinds_post_restored`: After a post is restored from the trash.
  - `grinds_before_post_delete`: Right before a post is permanently deleted.
  - `grinds_post_deleted`: After a post is permanently deleted.
  - `grinds_trash_emptied`: After the trash is emptied.

  Usage Example:
  add_action('grinds_head', function() {
      echo '<meta name="custom" content="hello">';
  });

- Samples:
  Two sample files are included (disabled by default):

  1. `_custom_helpers.php`
     Useful functions for theme development (e.g., debug `dd()`, reading time).
     Rename to "custom_helpers.php" to enable.

  2. `_sample_hooks.php`
     Demonstrates how to use Action Hooks to inject code or handle events.
     Rename to "sample_hooks.php" to enable.

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
  - `grinds_post_saved`: 記事保存時。
  - `grinds_post_trashed`: 記事をゴミ箱へ移動した時。
  - `grinds_post_restored`: 記事をゴミ箱から復元した時。
  - `grinds_before_post_delete`: 記事が完全に削除される直前。
  - `grinds_post_deleted`: 記事が完全に削除された時。
  - `grinds_trash_emptied`: ゴミ箱が空にされた時。

  使用例:
  add_action('grinds_head', function() {
      echo '<meta name="custom" content="hello">';
  });

- サンプル:
  2つのサンプルファイルが同梱されています（デフォルトでは無効です）。

  1. `_custom_helpers.php`
     テーマ開発に便利な関数集（デバッグ用 `dd()` や読了時間表示など）。
     リネームして "custom_helpers.php"（アンダースコアを削除）にすると有効になります。

  2. `_sample_hooks.php`
     フックシステムを使ってコードを挿入したり、イベントを処理する方法の実演です。
     リネームして "sample_hooks.php" にすると有効になります。
