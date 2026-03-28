=========================================================================
 GrindSite API Directory
=========================================================================

[English]
This directory contains public API endpoints for GrindSite.

- posts.php:
  A read-only API that returns published posts in JSON format.
  Useful for Headless CMS architectures or external integrations.
  Parameters:
    - limit: Number of posts (default: 10, max: 100)
    - page: Page number (default: 1)
    - category: Filter by category slug
    - tag: Filter by tag slug
    - slug: Fetch a single post by slug
    - q: Search keyword
    - type: 'post' or 'page' (default: post)
  Usage: /api/posts.php?limit=10&page=1&category=news&q=hello

  Response Format:
  The API always returns a JSON object with the following structure:
  {
    "success": true,
    "data": [ ... ], // Array of posts, or a single post object if 'slug' was used
    "meta": { "total": 42, "limit": 10, "currentPage": 1, "totalPages": 5 }
  }

  Note on Dynamic Blocks:
  When using Headless API, the HTML returned by the `html` field does not include frontend JavaScript.
  If your posts contain dynamic blocks like 'math' (KaTeX), 'code' (Prism.js), or 'countdown',
  you must manually load the required libraries and initialize them on the client side.
  The API response includes a `features` object for each post to help you detect these blocks.

  - settings.php:
    Returns global site settings (like site name, description, and language).
    Useful for rendering headers, footers, and meta tags on the frontend.
    Usage: /api/settings.php

  - categories.php:
    Returns a list of all categories.
    Useful for building navigation menus or sidebar filters.
    Usage: /api/categories.php

  Cross-Origin Resource Sharing (CORS):
  The API automatically outputs CORS headers. By default, it allows all origins (`*`).
  You can restrict this by defining `API_ALLOWED_ORIGIN` in your config.

- Custom APIs:
  You can add your own PHP files here to create custom API endpoints.
  Ensure you handle security (CORS, authentication) appropriately within your scripts.

-------------------------------------------------------------------------

[Japanese]
このディレクトリは、GrindSiteの公開APIエンドポイントを配置する場所です。

- posts.php:
  公開済みの記事データをJSON形式で返す読み取り専用APIです。
  ヘッドレスCMSとしての利用や、外部サイトへの埋め込みに使用できます。
  パラメータ:
    - limit: 取得件数 (デフォルト: 10, 最大: 100)
    - page: ページ番号 (デフォルト: 1)
    - category: カテゴリスラッグで絞り込み
    - tag: タグスラッグで絞り込み
    - slug: 記事スラッグで個別取得
    - q: 検索キーワード
    - type: 'post' または 'page' (デフォルト: post)
  使用例: /api/posts.php?limit=10&page=1&category=news&q=hello

  レスポンスフォーマット:
  APIは常に以下の構造を持つJSONオブジェクトを返します:
  {
    "success": true,
    "data": [ ... ], // 記事の配列（'slug'を指定した場合は1つの記事オブジェクトまたはnull）
    "meta": { "total": 42, "limit": 10, "currentPage": 1, "totalPages": 5 }
  }

  動的ブロックに関する注意事項:
  ヘッドレスAPIを利用する場合、`html` フィールドで返されるHTMLにはフロントエンド実行用のJavaScriptが含まれていません。
  記事内に「数式 (math)」「コード (code)」「カウントダウン (countdown)」などの動的ブロックが含まれている場合、
  クライアント側（フロントエンド）で KaTeX や Prism.js などの必要なライブラリを読み込み、初期化する必要があります。
  各記事のレスポンスに含まれる `features` オブジェクトを参照して、該当ブロックの有無を判定できます。

  - settings.php:
    サイト全体の共通設定（サイト名、説明文、言語など）を返します。
    フロントエンドでのヘッダーやフッター、metaタグの描画に便利です。
    使用例: /api/settings.php

  - categories.php:
    カテゴリの一覧リストを返します。
    ナビゲーションメニューやサイドバーの絞り込みリンクを構築する際に使用します。
    使用例: /api/categories.php

  CORS (Cross-Origin Resource Sharing) について:
  APIは自動的にCORSヘッダーを出力します。デフォルトではすべてのオリジン（`*`）からのアクセスを許可します。
  設定で `API_ALLOWED_ORIGIN` 定数を定義することで、アクセス元を制限することが可能です。

- カスタムAPI:
  独自のPHPファイルを作成して、カスタムAPIエンドポイントを追加できます。
  セキュリティ（CORSや認証など）は各スクリプト内で適切に処理してください。
