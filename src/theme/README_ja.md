# テーマ開発ガイドライン

## 1. 基本ルール

> [!WARNING]
> **デフォルトテーマを直接編集しないでください**
> 同梱されているテーマ（`default` や `bootstrap` など）は、CMS のアップデート時に上書きされる可能性があります。
> カスタマイズする場合は、必ずフォルダを**複製**（例: `default` をコピーして `my-theme` にリネーム）してから編集してください。

### CSS ファイル

> [!IMPORTANT]
> **重要: 相対パスの徹底**
> CSS ファイル内で画像やフォントを参照する場合（`background-image` など）、必ず**「CSS ファイルからの相対パス」**を使用してください。
>
> - ✅ **正:** `url('../img/background.jpg')`
> - ❌ **誤:** `url('/theme/default/img/background.jpg')`
>
> **理由:** CMS がサブディレクトリ（例: `example.com/cms/`）にインストールされた場合、`/` で始まるルート相対パスはドメイン直下を参照してしまい、リンク切れが発生するためです。

- **相対パスの使用**: 外部リソース（画像、フォントなど）へのパスは、すべて**相対パス**（例: `../img/bg.jpg`）を使用してください。
- **絶対パスの禁止**: 絶対パス（例: `/assets/...`）は**禁止**です。これは、CMS がサブディレクトリにインストールされた場合でもテーマが正しく動作するようにするためです。

### 動的画像

- **インラインスタイル**: 記事のサムネイルやヒーロー画像、バナーなど、ユーザーが変更可能な画像は CSS ファイルに記述しないでください。
- **実装方法**: これらの画像は PHP テンプレート内の `style` 属性を使用して直接処理してください。

#### 実装例

**悪い例 (CSS):**

```css
.hero {
  background-image: url('/assets/uploads/hero.jpg'); /* これは避けてください */
}
```

<div class="hero" style="background-image: url('<?= h(resolve_url($post['thumbnail'])) ?>');">
    <h1><?= h($post['title']) ?></h1>
</div>
```

## 2. ファイル構成

テーマフォルダ内の主要なファイルは以下の通りです。

- `layout.php`: サイト全体のレイアウト（ヘッダー、フッターなど）。`$content` 変数に各ページの HTML が渡されます。
- `home.php`: トップページ用テンプレート。
- `single.php`: 個別記事ページ用テンプレート。
- `page.php`: 固定ページ用テンプレート。
- `archive.php`: カテゴリ・タグ・検索結果などの一覧ページ。
- `404.php`: ページが見つからない場合の表示。
- `functions.php`: テーマ固有の関数やブロックレンダラーの定義。
- `parts/`: 再利用可能なパーツ（ヘッダー、フッター、サイドバーなど）を格納するディレクトリ。

## 3. 必須関数・変数

### ヘッダー・フッターの出力

テーマの `layout.php` には、必ず以下の関数を含めてください。これにより、CMS が必要な CSS や JS を出力します。

- `</head>` の直前に `<?php grinds_head(); ?>`
- `</body>` の直前に `<?php grinds_footer(); ?>`

### エスケープ処理

XSS 対策のため、HTML 出力時は必ず `h()` 関数を使用してください。

```php
<h1><?= h($post['title']) ?></h1>
```

### URL の生成

内部リンクやアセットへのパスは `resolve_url()` を使用して、インストールディレクトリを考慮したパスを生成してください。

```php
<a href="<?= resolve_url('/contact') ?>">お問い合わせ</a>
<img src="<?= resolve_url('/theme/my-theme/img/logo.svg') ?>" alt="Logo">
```

### コンテンツの表示

記事本文（ブロックエディタのデータ）を表示するには `render_content()` を使用します。

```php
<div class="post-content">
    <?= render_content($post['content']) ?>
</div>
```

## 4. Tailwind CSS のビルド

この CMS は Tailwind CSS を使用しています。テーマごとのスタイルを変更する場合は、以下のコマンドでビルドしてください。

```bash
# まず、新しいテーマ用のビルドスクリプトを `package.json` に追加する必要があります。
# 例: 
# "build:theme:my-theme": "tailwindcss -c ./src/theme/my-theme/tailwind.config.js -i ./src/theme/my-theme/css/theme-input.css -o ./src/theme/my-theme/css/style.css --minify"
# "watch:theme:my-theme": "tailwindcss -c ./src/theme/my-theme/tailwind.config.js -i ./src/theme/my-theme/css/theme-input.css -o ./src/theme/my-theme/css/style.css --watch"

# 開発用（変更を監視して自動ビルド）
npm run watch:theme:my-theme

# 本番用ビルド（圧縮・最適化）
npm run build:theme:my-theme
```
