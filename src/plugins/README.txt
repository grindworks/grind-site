=========================================================================
 GrindSite Plugin Directory
=========================================================================

[English]
This directory is for custom plugins to extend GrindSite's functionality.
Drop your `.php` files here.

- Activation: You can enable or disable plugins directly from the Admin Panel. Alternatively, you can forcefully disable a plugin by prefixing its filename with an underscore `_` (e.g., `_plugin.php`).
- Troubleshooting: If a plugin causes an error, the details will be recorded in the system logs (`data/logs/`) and can be reviewed in the Admin Panel.

*** CRITICAL SECURITY GUIDELINES FOR DEVELOPERS ***
GrindSite utilizes a "Defense in Depth" architecture. However, custom plugins can bypass these protections if not written carefully. Always follow these rules:

1. Data Sanitization & Output:
   - ALWAYS use `h($string)` to escape data before outputting to HTML.
   - For rich text (HTML), use `grinds_sanitize_html($html)` to prevent XSS.
   - Never output raw user input directly.

2. Database Operations:
   - ALWAYS use Prepared Statements (`$pdo->prepare()`). Never interpolate variables directly into SQL queries.
   - Example: `$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$id]);`

3. Portability (Zero Config):
   - Never hardcode absolute URLs or physical server paths.
   - Before saving URLs to the DB, use `Routing::convertToDbUrl($url)`.
   - When outputting URLs, use `resolve_url($url)` or `grinds_url_to_view($url)`.

4. Interacting with Core (Hooks & DB):
   - Use the Hook System (`add_action('hook_name', callback)`) to execute custom code safely.
   - To perform database queries, ALWAYS retrieve the shared PDO instance using `$pdo = App::db();`. Do NOT establish new database connections.

-------------------------------------------------------------------------

[Japanese]
このディレクトリは、GrindSiteの機能を拡張するためのカスタムプラグインを配置する場所です。
`.php` ファイルをここに配置してください。

- 有効化 / 無効化: プラグインの有効・無効は「管理画面」から直接切り替えることができます。また、ファイル名の先頭にアンダースコア `_` を付けることでも強制的に無効化できます（例: `_plugin.php`）。
- トラブルシューティング: プラグイン内でエラーが発生した場合、エラー詳細はシステムログ（`data/logs/`）に記録され、管理画面からも確認することができます。

*** プラグイン開発者向けの重要なセキュリティ・ガイドライン ***
GrindSiteのコアシステムは「多層防御」を採用していますが、独自プラグインが直接データベースを操作したり出力を行うと、この強固なセキュリティをバイパスしてしまう危険性があります。プラグイン開発の際は以下のルールを必ず守ってください。

1. データの無害化と出力:
   - テキストをHTMLに出力する前には、必ず `h($string)` 関数を使用してエスケープしてください。
   - リッチテキスト（HTML）を扱う場合は、XSSを防ぐために必ず `grinds_sanitize_html($html)` を通してください。
   - ユーザー入力をそのまま（生の状態で）出力しないでください。

2. データベース操作:
   - 常に Prepared Statement (`$pdo->prepare()`) を使用してください。SQL文に変数を直接埋め込む（文字列結合する）ことは厳禁です。
   - 例: `$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$id]);`

3. ポータビリティ (環境非依存):
   - 絶対URLやサーバーの物理パスをハードコードしないでください。
   - DBにURLを保存する際は、必ず `Routing::convertToDbUrl($url)` を通してプレースホルダー (`{{CMS_URL}}`) に変換してください。
   - URLを出力する際は、`resolve_url($url)` または `grinds_url_to_view($url)` を使用してください。

4. コアシステムとの連携 (フックとDB接続):
   - 独自の処理を追加する際は、直接実行せずにフックシステム (`add_action('hook_name', callback)`) を使用して安全に拡張してください。
   - データベースにアクセスする際は、必ず `$pdo = App::db();` を使用してシステム共有のPDOインスタンスを取得してください。新しく独自のDB接続を作成しないでください。
