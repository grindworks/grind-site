# 🌍 How to add a new language

GrindSite officially supports English (`en.php`) and Japanese (`ja.php`).
However, you can easily create and add your own language file using AI tools (ChatGPT, Claude, Gemini, etc.).

## Steps

1. Copy `en.php` and rename it to your target language code (e.g., `de.php` for German, `es.php` for Spanish, `fr.php` for French).
2. Open the new file and translate the string values on the right side of the array.
3. Select the new language from the system settings in the admin dashboard.

### 🤖 AI Prompt Example

You can copy the entire content of `en.php` and paste it into an AI with the following prompt to get a perfect translation in seconds:

```text
Please translate the following PHP localization array into [Your Target Language].

Rules:
- ONLY translate the string values on the right side of the `=>`.
- DO NOT modify the keys on the left side.
- Keep all HTML tags, placeholders (like `%s`, `%d`), and newline characters (`\n`) exactly as they are.
- Maintain a professional and natural tone for a web application dashboard.
- Output ONLY the complete PHP code block.

[Paste the contents of en.php here]
```

---

# 🌍 新しい言語の追加方法

現在、GrindSiteは英語と日本語を公式にサポートしていますが、AIツールを活用することで、ユーザー自身で簡単にお好きな言語を追加できます。

## 追加手順

1. `en.php` をコピーし、追加したい言語コードのファイル名（例: ドイツ語なら `de.php`）に変更します。
2. ファイルを開き、配列の右側（値）のみを翻訳します。
3. 管理画面のシステム設定から、追加した言語を選択します。

上記の「AI Prompt Example（AIプロンプト例）」をコピーして、ChatGPT等に `en.php` の中身と一緒に貼り付けるだけで、システムフォーマットを崩さずに一括翻訳が可能です。
