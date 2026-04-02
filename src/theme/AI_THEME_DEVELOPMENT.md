# 🤖 AI-Driven Theme Development Guide

**For GrindSite**

[**English**](#english) | [**日本語 (Japanese)**](#japanese)

---

<a name="english"></a>

## 🇺🇸 English

GrindSite is incredibly AI-friendly because its frontend relies solely on pure PHP arrays and Tailwind CSS. You do not need to learn complex loop functions or directory hierarchies like WordPress.
Tools like **Cursor, Claude Code, GitHub Copilot, and ChatGPT** can generate a complete, working theme in one shot.

### 📋 1. System Prompt for AI (Copy & Paste)

Before asking the AI to create a theme, paste the following prompt to teach it the GrindSite rules.
_(This is a base template. Feel free to customize or add more specific rules for your project.)_

> **[COPY BELOW]**
> I want to create a new frontend theme for a custom CMS called "GrindSite".
> Please generate the required PHP files by strictly following these specifications and rules:
>
> **[Theme Structure]**
>
> - Build the theme using Tailwind CSS and Alpine.js.
> - Main files: `layout.php` (wrapper), `home.php` (lists), `single.php` (post detail), `page.php` (static page), `404.php`.
>
> **[Required Globals & Functions (NEVER use WordPress functions)]**
>
> 1. Layout Output (`layout.php`)
>
> - Call `<?php grinds_head(); ?>` just before `</head>`.
> - Call `<?php grinds_footer(); ?>` just before `</body>`.
> - Output the main content using `<?= $content ?>`.
>
> 2. Accessing Page Data
>
> - Page Type: `$pageType` ('home', 'single', 'category', etc.)
> - Page Title: `$pageTitle`
> - Post Data: `$pageData['post']` (Available in single/page)
>   - Title: `$pageData['post']['title']`
>   - Slug: `$pageData['post']['slug']`
>   - Date: `$pageData['post']['published_at']` or `created_at`
>   - Thumbnail: `$pageData['post']['thumbnail']`
>   - Content (JSON blocks): `$pageData['post']['content']`
> - Posts List: `$pageData['posts']` (Available in home/archive)
>   - Inside a `foreach ($pageData['posts'] as $post)` loop, use `$post['title']`, etc.
>
> 3. Required Helper Functions
>
> - XSS Escape: `<?= h($variable) ?>`
> - Resolve Theme Assets (CSS/JS/Img): `<?= h(grinds_theme_asset_url('img/logo.svg')) ?>`
> - Render Post Content: `<?= render_content($pageData['post']['content']) ?>`
> - Load Template Part: `<?php get_template_part('parts/header'); ?>`
> - Pagination: `<?php the_pagination(); ?>`
> - Translation: `<?= theme_t('Read More') ?>`
> - Image Tag (WebP support): `<?= get_image_html(resolve_url($pageData['post']['thumbnail']), ['class' => 'w-full object-cover']) ?>`
>
> 4.  Post Loop Example (`home.php` / `archive.php`)
>     <?php if (!empty($pageData['posts'])): ?>
>         <?php foreach ($pageData['posts'] as $post): ?>
>             <!-- Card design here -->
>         <?php endforeach; ?>
>     <?php endif; ?>
>
> **[CRITICAL RULE]**
> Do NOT use absolute paths (e.g., `/assets/img.jpg`) inside CSS files. You must use relative paths (e.g., `../img/bg.jpg`). Output dynamic images directly via inline `style` attributes in PHP.
> **[COPY END]**

### 🚀 2. AI Workflow (e.g., Cursor / Claude Code)

Once the AI reads the prompt above, give it a specific design request.
**Example Prompt:**

> "Using `src/theme/default/` as a reference, generate a new theme in `src/theme/corporate/`. Make it a 'clean and trustworthy corporate site' using Tailwind CSS. Use navy blue (`blue-900`) as the primary color. Generate `layout.php`, `home.php`, `single.php`, `parts/header.php`, and `parts/footer.php` all at once."

_Tip: This is just an example. Try experimenting with different styles and instructions to find what works best for you!_

### 🎨 3. Overriding Block HTML (Pro Feature)

You can completely override how the block editor outputs HTML (e.g., headings, quotes, buttons) to perfectly match your theme's design.
**Example Prompt:**

> "I want to customize the design of the 'quote' and 'header' blocks for this theme. Create `theme/corporate/blocks/quote.php` and `header.php` to output beautiful HTML using Tailwind. You can use variables like `$data['text']` and `$data['level']`."

### 🎨 4. Workflow: From Canva/Figma to Theme

You can generate a GrindSite theme directly from your design mockups.

**STEP 1: Export Design as Images**
Export your designs (Home, Article page, etc.) from Canva or Figma as **PNG or JPG images**.
_Note: Feeding a single long image to the AI often yields better results than sharing a PDF or Figma URL._

**STEP 2: Upload Images & Prompt to AI**
Upload the exported images to an AI tool that supports image analysis (e.g., Claude Web, Cursor Composer) along with the **System Prompt** mentioned above.

**Example Prompt:**

> (Paste the System Prompt from Section 1 here)
>
> **[Instruction]**
> The attached images are the design for a new website created in Figma.
> Please generate the GrindSite theme files (`layout.php`, `home.php`, `single.php`, `parts/header.php`, `parts/footer.php`) to perfectly reproduce this design.
> Use Tailwind CSS utility classes for all styling.

**STEP 3: Save Generated Files**
The AI will analyze the colors, layout, and spacing from the image, code it in Tailwind CSS, and embed GrindSite variables (like `<?= h($pageData['post']['title']) ?>`) in the appropriate places.
Save the output code to `src/theme/my-custom-theme/` to complete the theme skeleton.

**💡 Why this works so well with GrindSite**

1. **Tailwind CSS**: AI is much better at generating "HTML with Tailwind classes" from images than writing raw CSS files.
2. **Pure PHP Structure**: Unlike WordPress, GrindSite doesn't require complex loop functions. Simple `foreach` loops in HTML allow the AI to convert the design to a CMS theme without breaking the structure.

**⚠️ Limitations**

- **Pixel Perfection**: AI creates a "very close" reproduction, but it may not perfectly match font sizes or spacing values from Figma. Manual adjustments (tweaking Tailwind classes) will be required.
- **Mobile Responsiveness**: If you only provide PC design images, the AI will guess the mobile layout. For best results, provide mobile design images as well.

---

<a name="japanese"></a>

## 🇯🇵 日本語 (Japanese)

GrindSiteは「ピュアなPHP配列」と「Tailwind CSS」のみで構成されているため、WordPressのような独自の関数や複雑な階層構造を覚える必要がありません。
そのため、**Cursor, Claude Code, GitHub Copilot, ChatGPT などの AI を使って、一気に新しいテーマを作り上げること**に非常に適しています。

### 📋 1. AIへ渡すシステムプロンプト（コピペ用）

新しいテーマを作成する際、まずはAI（またはCursorの `rules` やClaudeの `System Prompt`）に以下の仕様を読み込ませてください。
_（これは基本テンプレートです。プロジェクトの要件に合わせて、自由に追加・調整してください。）_

> **[ここから下をコピーしてAIに送信してください]**
> これから「GrindSite」という独自のCMS用の新しいフロントエンドテーマを作成します。
> 以下の仕様とルールを厳守して、必要なPHPファイルを生成してください。
>
> 【テーマの基本構造】
> ・テーマは Tailwind CSS と Alpine.js を使用して構築します。
> ・主要ファイル: `layout.php` (大枠), `home.php` (一覧), `single.php` (詳細), `page.php` (固定ページ), `404.php`
>
> 【必須のグローバル変数と関数（絶対にWordPress関数は使わないこと）】
>
> 1. レイアウト出力 (`layout.php`)
>
> - `</head>` の直前で `<?php grinds_head(); ?>` を呼ぶ。
> - `</body>` の直前で `<?php grinds_footer(); ?>` を呼ぶ。
> - `layout.php` 内で各ページのメインコンテンツを出力する場所には `<?= $content ?>` を配置する。
>
> 2. ページ情報へのアクセス
>
> - ページタイプ: `$pageType` ('home', 'single', 'category' など)
> - ページタイトル: `$pageTitle`
> - 記事データ配列: `$pageData['post']` (singleやpageで使用)
>   - タイトル: `$pageData['post']['title']`
>   - URLスラッグ: `$pageData['post']['slug']`
>   - 日付: `$pageData['post']['published_at']` または `created_at`
>   - サムネイル: `$pageData['post']['thumbnail']`
>   - 本文(JSONブロック): `$pageData['post']['content']`
> - 記事一覧配列: `$pageData['posts']` (homeやarchiveで使用)
>   - `foreach ($pageData['posts'] as $post)` のループ内では `$post['title']` などを利用。
>
> 3. 必須のヘルパー関数
>
> - XSS対策: `<?= h($variable) ?>`
> - テーマ内アセット(画像/CSS/JS)のURL: `<?= h(grinds_theme_asset_url('img/logo.svg')) ?>`
> - 記事本文のHTML出力: `<?= render_content($pageData['post']['content']) ?>`
> - テンプレートパーツの読み込み: `<?php get_template_part('parts/header'); ?>`
> - ページネーション出力: `<?php the_pagination(); ?>`
> - 翻訳文字列: `<?= theme_t('Read More') ?>`
> - 画像タグ出力(WebP対応): `<?= get_image_html(resolve_url($pageData['post']['thumbnail']), ['class' => 'w-full object-cover']) ?>`
>
> 4.  記事一覧のループ処理 (home.php や archive.php)
>     <?php if (!empty($pageData['posts'])): ?>
>         <?php foreach ($pageData['posts'] as $post): ?>
>             <!-- ここにカードデザイン -->
>         <?php endforeach; ?>
>     <?php endif; ?>
>
> 【超重要ルール（絶対厳守）】
> CSSファイル内で背景画像などを指定する場合、絶対に絶対パス（/assets/..等）を使わず、CSSファイルからの相対パス（../img/bg.jpg 等）を使用してください。動的な画像はインラインの `style` 属性でPHPから出力してください。
> **[コピーここまで]**

### 🚀 2. ツールの特性に合わせた開発フロー

リポジトリ全体を直接編集できるエージェント(Cursor / Claude Code)を使用する場合は、最も速くテーマが完成します。
**指示の例:**

> 「`src/theme/default/` の構造を参考にして、`src/theme/corporate/` という新しいテーマを作成してください。デザインは Tailwind CSS を使った『クリーンで信頼感のある企業向けサイト』にしてください。テーマカラーはネイビー（blue-900）を使用し、`layout.php`, `home.php`, `single.php`, `parts/header.php`, `parts/footer.php` などを一気に生成して保存してください。」

_ヒント: これはあくまで一例です。あなたの感性で自由に指示を書き換えて、最高の設定を見つけてください！_

### 🎨 3. ブロック出力の完全オーバーライド（プロ向け機能）

GrindSite のエディタで作成した「見出し」や「引用」「ボタン」などのHTML出力は、テーマ側で**完全に上書き**することができます。AIにこのカスタマイズを行わせることで、記事の中身まで完璧にデザインされたテーマが完成します。

**AIへの指示例:**

> 「このテーマに合わせて、記事内の引用ブロック（quote）と見出し（header）のデザインもカスタマイズしたいです。`theme/corporate/blocks/quote.php` と `header.php` を作成し、Tailwindを使った美しいHTMLを出力するようにしてください。（変数 `$data['text']` や `$data['level']` などが利用可能です）」

### 🎨 4. Canva / Figma からテーマを作る最強ワークフロー

デザインツールで作ったカンプから、直接 GrindSite のテーマを生成する手順です。

**STEP 1: デザインを画像として書き出す**
CanvaやFigmaで作成したデザイン（トップページ、記事ページなど）を、それぞれ **PNG または JPG画像** としてエクスポート（ダウンロード）します。
_※PDFやFigmaのURLを渡すより、1枚の長い画像としてAIに読み込ませる方が精度が高くなります。_

**STEP 2: AI に画像を投げて指示を出す**
Claude のWeb版や、Cursor（Composer機能）などの画像読み込みができるAIに、**「エクスポートした画像」と「先ほどのGrindSiteのシステムプロンプト」**をセットで投げます。

**実際にAIに入力するプロンプトの例:**

> (ここにセクション1のシステムプロンプトをペースト)
>
> **【今回の指示】**
> 添付した画像は、Figmaで作った新しいWebサイトのデザインです。
> このデザインを完全に再現する形で、GrindSite用のテーマファイル（layout.php, home.php, single.php, parts/header.php, parts/footer.php）を一気に生成してください。
> CSSはすべて Tailwind CSS のユーティリティクラスで表現してください。

**STEP 3: 生成されたファイルを保存して完了**
AIが画像の色合い、レイアウト、余白を解析し、Tailwind CSSでコーディングしつつ、`<?= h($pageData['post']['title']) ?>` のようなGrindSiteの変数を適切な場所に埋め込んでくれます。
出力されたコードを `src/theme/my-custom-theme/` などに保存すれば、デザインのテーマ化がほぼ完了します。

**💡 この手法が上手くいく理由（GrindSiteの優位性）**

1. **Tailwind CSS の採用**: AIは「画像からCSSファイルを作る」よりも「画像からTailwindのクラス付きHTMLを作る」方が圧倒的に得意で、精度が高いです。
2. **ピュアなHTMLに近い構造**: GrindSiteのテーマは、WordPressのような複雑なループ関数が不要です。HTMLに `foreach` を少し書くだけなので、AIがHTML構造を崩さずにCMS化できます。

**⚠️ 注意点（AIにお任せする場合の限界）**

- **完全再現は難しい**: AIは見た目を「かなり近く」再現しますが、Figmaのフォントサイズや余白の数値を100%正確に読み取れるわけではありません。生成後、微調整（Tailwindのクラスの変更）は必要になります。
- **スマホ表示の補完**: PC版のデザイン画像しか渡さないと、AIがスマホ版のレイアウトを「良きに計らって」作ります。こだわりがある場合は、スマホ版の画像も一緒に渡すと完璧です。
