# 🎨 Skin Creation Manual / オリジナルスキン作成マニュアル

[English](#english) | [日本語 (Japanese)](#japanese)

---

<a name="english"></a>

## 🇺🇸 English

By adding a JSON configuration file to this directory (`src/admin/skins/`), you can freely customize the design (skin) of the admin panel.

### 🚀 Quick Start

1. Copy the existing `default.json` and create a new JSON file (e.g., `my-dark-theme.json`).
2. Modify the `name`, `colors`, and other values in the file.
3. Go to the admin settings and select your new skin to apply it.

### 📄 Configuration Structure

Skin files are written in JSON format.

```json
{
  "name": "My Custom Skin",
  "description": "A custom dark theme",
  "font_url": "https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap",
  "font": "\"Inter\", sans-serif",
  "colors": {
    "bg": "#1e293b",
    "primary": "#3b82f6",
    "on_primary": "#ffffff"
  },
  "rounded": "0.5rem",
  "effects": ["btn_lift", "input_glow_modern"],
  "ai_paste": {
    "title": "AI Paste",
    "description": "Paste Markdown from Clipboard",
    "bg_start": "#1e293b",
    "bg_end": "#0f172a",
    "border": "#334155",
    "border_style": "dashed",
    "border_hover": "#3b82f6",
    "icon_color": "#3b82f6",
    "text_gradient_start": "#3b82f6",
    "text_gradient_end": "#60a5fa",
    "icon": "outline-sparkles"
  },
  "is_dark": true,
  "is_sidebar_dark": true
}
```

### Configuration Keys

#### Basic Settings

| Key           | Description                         | Example                    |
| :------------ | :---------------------------------- | :------------------------- |
| `name`        | Display name of the skin (Required) | `"Midnight Blue"`          |
| `description` | Description of the skin             | `"Eye-friendly dark mode"` |
| `font_url`    | CSS URL for Google Fonts, etc.      | `"https://fonts..."`       |
| `font`        | Value for CSS `font-family`         | `"sans-serif"`             |

#### Colors (Palette)

Define colors for various UI parts within the `colors` object.

| Key                                       | Description                               |
| :---------------------------------------- | :---------------------------------------- |
| **Base Colors**                           |                                           |
| `bg`                                      | Background color of the entire screen     |
| `surface`                                 | Background color for cards and panels     |
| `text`                                    | Primary text color                        |
| `border`                                  | Color of borders and dividers             |
| **Sidebar**                               |                                           |
| `sidebar`                                 | Sidebar background color                  |
| `sidebar_text`                            | Sidebar text color                        |
| `sidebar_active_bg`                       | Background color of the active menu item  |
| `sidebar_active_text`                     | Text color of the active menu item        |
| **Brand Colors**                          |                                           |
| `primary`                                 | Main color (Primary buttons, links)       |
| `on_primary`                              | Text color on top of the primary color    |
| `secondary`                               | Secondary color                           |
| `danger` / `warning` / `success` / `info` | Colors representing various status states |
| **Forms**                                 |                                           |
| `input_bg`                                | Background color of input fields          |
| `input_text`                              | Text color inside input fields            |
| `input_border`                            | Border color of input fields              |

#### UI Styles

| Key             | Description                 | Example                       |
| :-------------- | :-------------------------- | :---------------------------- |
| `rounded`       | Border radius size          | `"0.5rem"`, `"4px"`           |
| `border_width`  | Thickness of borders        | `"1px"`                       |
| `shadow`        | Box shadow                  | `"0 1px 3px rgba(0,0,0,0.1)"` |
| `input_padding` | Padding inside input fields | `"0.625rem 0.875rem"`         |
| `sidebar_mode`  | Sidebar color mode          | `"light"` or `"dark"`         |
| `nav_style`     | Navigation shape style      | `"pill"`, etc.                |

#### Advanced Settings (Optional)

| Key               | Description                                                      | Example                        |
| :---------------- | :--------------------------------------------------------------- | :----------------------------- |
| `is_dark`         | Force dark mode detection (Boolean). Auto-calculated if omitted. | `true`                         |
| `is_sidebar_dark` | Force sidebar dark mode (Boolean). Auto-calculated if omitted.   | `true`                         |
| `ai_paste`        | Custom styling for the "AI Smart Paste" upload area.             | `{"bg_start": "#1e293b", ...}` |

### Effects

By adding specific keys to the `effects` array, you can apply predefined CSS effects. Available effects are defined in `src/admin/config/skin_effects.php`.

#### ⚠️ Font-Dependent Effects

If you use the following effects, you must load the corresponding font via `font_url`.

| Effect Name          | Required Font         | Google Fonts URL Example         |
| :------------------- | :-------------------- | :------------------------------- |
| `input_mono`         | **Roboto Mono**       | `...family=Roboto+Mono...`       |
| `input_typewriter`   | **Courier Prime**     | `...family=Courier+Prime...`     |
| `heading_decorative` | **Cinzel Decorative** | `...family=Cinzel+Decorative...` |
| `text_luxury_serif`  | **Cinzel**            | `...family=Cinzel...`            |

### Tips

- **Color Contrast:** Ensure a high contrast between background and text colors (e.g., `primary` vs. `on_primary`) for better accessibility.
- **CSS Units:** Use valid CSS units such as `px`, `rem`, or `em` for size definitions.
- **JSON Syntax:** Be careful with syntax errors; trailing commas are **not** allowed in standard JSON files.

---

<a name="japanese"></a>

## 🇯🇵 日本語 (Japanese)

このディレクトリ（`src/admin/skins/`）にJSON設定ファイルを追加することで、管理画面のデザイン（スキン）を自由にカスタマイズできます。

### 🚀 クイックスタート

1. 既存の `default.json` をコピーして、新しいJSONファイル（例：`my-dark-theme.json`）を作成します。
2. ファイル内の `name` や `colors` などの値を変更します。
3. 管理画面の設定ページへ移動し、新しいスキンを選択して適用します。

### 📄 設定ファイルの構造

スキンファイルは JSON 形式で記述します。

```json
{
  "name": "My Custom Skin",
  "description": "A custom dark theme",
  "font_url": "https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap",
  "font": "\"Inter\", sans-serif",
  "colors": {
    "bg": "#1e293b",
    "primary": "#3b82f6",
    "on_primary": "#ffffff"
  },
  "rounded": "0.5rem",
  "effects": ["btn_lift", "input_glow_modern"],
  "ai_paste": {
    "title": "AI Paste",
    "description": "Paste Markdown from Clipboard",
    "bg_start": "#1e293b",
    "bg_end": "#0f172a",
    "border": "#334155",
    "border_style": "dashed",
    "border_hover": "#3b82f6",
    "icon_color": "#3b82f6",
    "text_gradient_start": "#3b82f6",
    "text_gradient_end": "#60a5fa",
    "icon": "outline-sparkles"
  },
  "is_dark": true,
  "is_sidebar_dark": true
}
```

### 設定項目一覧

#### 基本設定

| キー          | 説明                      | 例                         |
| :------------ | :------------------------ | :------------------------- |
| `name`        | スキンの表示名（必須）    | `"Midnight Blue"`          |
| `description` | スキンの説明              | `"目に優しいダークモード"` |
| `font_url`    | Google Fonts 等の CSS URL | `"https://fonts..."`       |
| `font`        | CSS `font-family` の値    | `"sans-serif"`             |

#### Colors (カラーパレット)

`colors` オブジェクト内で、UI 各部の色を定義します。

| キー                                      | 説明                           |
| :---------------------------------------- | :----------------------------- |
| **基本カラー**                            |                                |
| `bg`                                      | 画面全体の背景色               |
| `surface`                                 | カードやパネルの背景色         |
| `text`                                    | 基本の文字色                   |
| `border`                                  | 枠線の色                       |
| **サイドバー**                            |                                |
| `sidebar`                                 | サイドバー背景色               |
| `sidebar_text`                            | サイドバー文字色               |
| `sidebar_active_bg`                       | 選択中のメニュー背景色         |
| `sidebar_active_text`                     | 選択中のメニュー文字色         |
| **ブランドカラー**                        |                                |
| `primary`                                 | メインカラー（主要なボタン等） |
| `on_primary`                              | メインカラー上の文字色         |
| `secondary`                               | サブカラー                     |
| `danger` / `warning` / `success` / `info` | 各状態を表す色                 |
| **入力フォーム**                          |                                |
| `input_bg`                                | 入力欄の背景色                 |
| `input_text`                              | 入力欄の文字色                 |
| `input_border`                            | 入力欄の枠線色                 |

#### UI スタイル調整

| キー            | 説明                            | 例                            |
| :-------------- | :------------------------------ | :---------------------------- |
| `rounded`       | 角丸のサイズ (`border-radius`)  | `"0.5rem"`, `"4px"`           |
| `border_width`  | 枠線の太さ                      | `"1px"`                       |
| `shadow`        | ドロップシャドウ (`box-shadow`) | `"0 1px 3px rgba(0,0,0,0.1)"` |
| `input_padding` | 入力欄の内側余白                | `"0.625rem 0.875rem"`         |
| `sidebar_mode`  | サイドバーのモード              | `"light"` または `"dark"`     |
| `nav_style`     | ナビゲーションの形状            | `"pill"` (カプセル型) 等      |

#### 高度な設定（オプション）

| キー              | 説明                                                           | 例                             |
| :---------------- | :------------------------------------------------------------- | :----------------------------- |
| `is_dark`         | ダークモードの強制指定 (Boolean)。省略時は背景色から自動判定。 | `true`                         |
| `is_sidebar_dark` | サイドバーのダークモード強制指定 (Boolean)。自動判定あり。     | `true`                         |
| `ai_paste`        | 「スマートペースト (AI取込)」エリアの独自スタイル設定。        | `{"bg_start": "#1e293b", ...}` |

### Effects (エフェクト)

`effects` 配列にキーを追加することで、定義済みの CSS エフェクトを適用できます。利用可能なエフェクトは `src/admin/config/skin_effects.php` で確認できます。

#### ⚠️ フォント依存のあるエフェクト

以下のエフェクトを使用する場合は、`font_url` で対応するフォントを読み込む必要があります。

| エフェクト名         | 必要なフォント        | Google Fonts URL 例              |
| :------------------- | :-------------------- | :------------------------------- |
| `input_mono`         | **Roboto Mono**       | `...family=Roboto+Mono...`       |
| `input_typewriter`   | **Courier Prime**     | `...family=Courier+Prime...`     |
| `heading_decorative` | **Cinzel Decorative** | `...family=Cinzel+Decorative...` |
| `text_luxury_serif`  | **Cinzel**            | `...family=Cinzel...`            |

### ヒント

- **色のコントラスト**: `primary` と `on_primary` のように、背景色と文字色の組み合わせは視認性を考慮して設定してください。
- **CSS 単位**: サイズ指定には `px`, `rem`, `em` などの有効な CSS 単位を使用してください。
- **JSON 構文**: JSON ファイルでは末尾のカンマ（trailing comma）は許容されないため注意してください。
